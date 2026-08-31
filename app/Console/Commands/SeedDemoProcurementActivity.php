<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use App\Models\User;
use App\Models\VoteHead;
use App\Services\ProcurementService;
use Illuminate\Console\Command;

/**
 * Walks 2-3 real requisitions all the way through the Procurement workflow
 * (submit -> department approval -> finance approval -> PO -> GRN -> invoice
 * -> payment) against real vote heads, so the Phase 4 UI has realistic data
 * to review. Everything created here is tagged [DEMO] and uses clearly
 * fictitious supplier names, so it's trivially identifiable. Guarded to run
 * once. Mirrors `accounting:seed-demo-activity` from Phase 2.
 */
class SeedDemoProcurementActivity extends Command
{
    protected $signature = 'accounting:seed-demo-procurement';

    protected $description = 'Walk a few demo purchase requisitions through the full Procurement workflow to populate the GL for review';

    public function handle(ProcurementService $service): int
    {
        if (Supplier::where('name', 'like', '[DEMO]%')->exists()) {
            $this->error('Demo procurement activity already exists (a Supplier named "[DEMO]..." was found). This command is meant to run once — nothing was created.');
            return self::FAILURE;
        }

        $admin = User::where('email', 'super@demo.com')->first();

        if (!$admin) {
            $this->error('super@demo.com not found — cannot attribute demo activity.');
            return self::FAILURE;
        }

        $supplier = Supplier::create([
            'name' => '[DEMO] Sunrise Office Supplies Ltd',
            'contact_person' => 'Jane Wanjiru',
            'phone' => '0700000000',
            'email' => 'sales@sunrise-demo.example',
            'kra_pin' => 'P000000000D',
            'payment_terms' => 'Net 30',
            'is_active' => true,
        ]);

        $this->info("Created demo supplier #{$supplier->id}.");

        $scenarios = [
            ['vote_head' => 'ICT', 'description' => '[DEMO] 5x office routers', 'amount' => 45000, 'payment' => 'full'],
            ['vote_head' => 'ADMIN', 'description' => '[DEMO] Stationery restock', 'amount' => 18000, 'payment' => 'partial'],
            ['vote_head' => 'MAINT', 'description' => '[DEMO] Plumbing repairs — staffroom', 'amount' => 12000, 'payment' => 'none'],
        ];

        foreach ($scenarios as $i => $scenario) {
            $voteHead = VoteHead::where('code', $scenario['vote_head'])->first();

            if (!$voteHead) {
                $this->warn("Vote head {$scenario['vote_head']} not found — skipping.");
                continue;
            }

            $req = $service->submitRequisition([
                'vote_head_id' => $voteHead->id,
                'description' => $scenario['description'],
                'estimated_amount' => $scenario['amount'],
                'requested_by' => $admin->id,
            ]);

            $service->approveRequisitionByDepartment($req, $admin->id);
            $req = $service->approveRequisitionByFinance($req->fresh(), $admin->id);

            $po = $service->createPurchaseOrder([
                'purchase_requisition_id' => $req->id,
                'supplier_id' => $supplier->id,
                'order_date' => now()->toDateString(),
                'description' => $scenario['description'],
                'amount' => $scenario['amount'],
                'created_by' => $admin->id,
            ]);

            $service->recordGoodsReceived([
                'purchase_order_id' => $po->id,
                'received_date' => now()->toDateString(),
                'received_by' => $admin->id,
                'notes' => '[DEMO] delivery confirmed',
            ]);

            $invoice = $service->recordSupplierInvoice([
                'purchase_order_id' => $po->id,
                'invoice_number' => 'DEMO-INV-' . ($i + 1),
                'amount' => $scenario['amount'],
                'invoice_date' => now()->toDateString(),
                'recorded_by' => $admin->id,
            ]);

            $this->info("[{$scenario['vote_head']}] Requisition {$req->requisition_number} -> PO {$po->po_number} -> Invoice {$invoice->invoice_number} (KES {$scenario['amount']}) — accrual posted.");

            if ($scenario['payment'] === 'full') {
                $service->recordSupplierPayment([
                    'supplier_invoice_id' => $invoice->id,
                    'amount' => $scenario['amount'],
                    'payment_date' => now()->toDateString(),
                    'method' => 'bank',
                    'reference' => 'DEMO-PAY-' . ($i + 1),
                    'paid_by' => $admin->id,
                ]);
                $this->info('  -> Paid in full.');
            } elseif ($scenario['payment'] === 'partial') {
                $partial = round($scenario['amount'] * 0.4, -2);
                $service->recordSupplierPayment([
                    'supplier_invoice_id' => $invoice->id,
                    'amount' => $partial,
                    'payment_date' => now()->toDateString(),
                    'method' => 'mpesa',
                    'reference' => 'DEMO-PAY-' . ($i + 1),
                    'paid_by' => $admin->id,
                ]);
                $this->info("  -> Partially paid (KES {$partial} of {$scenario['amount']}).");
            } else {
                $this->info('  -> Left unpaid (still pending).');
            }
        }

        $this->newLine();
        $this->info('Demo procurement activity created. Review it under Accounting > Suppliers/Requisitions/Purchase Orders/Supplier Invoices/Payments.');

        return self::SUCCESS;
    }
}
