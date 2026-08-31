<?php

namespace App\Services;

use App\Models\GoodsReceivedNote;
use App\Models\PaymentMethodAccountMap;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;

/**
 * Phase 4 of the Finance module: Procurement & Supplier Payments.
 *
 * Workflow: Requisition -> Department Approval -> Finance Approval ->
 * Purchase Order -> Goods Received -> Supplier Invoice -> Payment.
 *
 * "Department Approval" has no org-unit routing behind it (no Department
 * model exists in this codebase) — it is simply a second permission-gated
 * status transition on the requisition, i.e. "two people must sign off,"
 * not "the requester's department approves." See the Phase 4 plan for the
 * reasoning.
 *
 * GL accounting is accrual-based, using the two journal entries below and
 * finally putting the "2010 Accounts Payable" account (seeded but unused
 * since Phase 1) to work:
 * - recordSupplierInvoice(): DR <vote head's expense account> / CR Accounts
 *   Payable — the expense is recognized when incurred, not when paid.
 * - recordSupplierPayment(): DR Accounts Payable / CR <cash/bank account,
 *   resolved via PaymentMethodAccountMap> — settles the liability. Supports
 *   partial payments.
 *
 * No GL entry is posted at Purchase Order stage — a PO is a commitment, not
 * yet a recognized expense.
 *
 * Deliberate asymmetry in error handling: recordSupplierInvoice() posts its
 * GL entry inside the SAME transaction as the invoice — a GL failure rolls
 * back the whole invoice, since nothing irreversible has happened yet.
 * recordSupplierPayment() posts its GL entry AFTER its transaction commits
 * and only logs a warning on failure, matching PaymentPostingService/
 * RefundService/PettyCashService — because real money has already left the
 * bank by the time this runs.
 */
class ProcurementService
{
    public function __construct(
        private JournalPostingService $journalPostingService
    ) {}

    public function submitRequisition(array $data): PurchaseRequisition
    {
        $amount = (float) ($data['estimated_amount'] ?? 0);

        if ($amount <= 0) {
            throw new LogicException('Estimated amount must be greater than zero.');
        }

        return PurchaseRequisition::create([
            'requisition_number' => $this->nextNumber(PurchaseRequisition::class, 'requisition_number', 'PR'),
            'vote_head_id' => $data['vote_head_id'],
            'sub_vote_head_id' => $data['sub_vote_head_id'] ?? null,
            'requested_by' => $data['requested_by'] ?? null,
            'description' => $data['description'],
            'estimated_amount' => $amount,
            'needed_by_date' => $data['needed_by_date'] ?? null,
            'status' => 'submitted',
        ]);
    }

    public function approveRequisitionByDepartment(PurchaseRequisition $requisition, int $approverId): PurchaseRequisition
    {
        if ($requisition->status !== 'submitted') {
            throw new LogicException("Purchase requisition #{$requisition->id} is not submitted (status: {$requisition->status}).");
        }

        $requisition->update([
            'status' => 'dept_approved',
            'dept_approved_by' => $approverId,
            'dept_approved_at' => now(),
        ]);

        return $requisition->fresh();
    }

    public function approveRequisitionByFinance(PurchaseRequisition $requisition, int $approverId): PurchaseRequisition
    {
        if ($requisition->status !== 'dept_approved') {
            throw new LogicException("Purchase requisition #{$requisition->id} has not been department-approved yet (status: {$requisition->status}).");
        }

        $requisition->update([
            'status' => 'finance_approved',
            'finance_approved_by' => $approverId,
            'finance_approved_at' => now(),
        ]);

        return $requisition->fresh();
    }

    public function rejectRequisition(PurchaseRequisition $requisition, string $reason, int $rejectedBy): PurchaseRequisition
    {
        if (!in_array($requisition->status, ['submitted', 'dept_approved'], true)) {
            throw new LogicException("Purchase requisition #{$requisition->id} cannot be rejected from its current status ({$requisition->status}).");
        }

        $requisition->update([
            'status' => 'rejected',
            'rejected_by' => $rejectedBy,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $requisition->fresh();
    }

    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        $requisition = PurchaseRequisition::findOrFail($data['purchase_requisition_id']);

        if ($requisition->status !== 'finance_approved') {
            throw new LogicException("Purchase requisition #{$requisition->id} is not finance-approved (status: {$requisition->status}).");
        }

        return DB::transaction(function () use ($requisition, $data) {
            $po = PurchaseOrder::create([
                'po_number' => $this->nextNumber(PurchaseOrder::class, 'po_number', 'PO'),
                'purchase_requisition_id' => $requisition->id,
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'description' => $data['description'] ?? $requisition->description,
                'amount' => $data['amount'] ?? $requisition->estimated_amount,
                'status' => 'open',
                'created_by' => $data['created_by'] ?? null,
                'document_path' => $data['document_path'] ?? null,
                'document_original_name' => $data['document_original_name'] ?? null,
            ]);

            $requisition->update(['status' => 'converted']);

            return $po;
        });
    }

    public function cancelPurchaseOrder(PurchaseOrder $po, int $userId): PurchaseOrder
    {
        if ($po->status !== 'open') {
            throw new LogicException("Purchase order #{$po->id} can only be cancelled while open (status: {$po->status}).");
        }

        $po->update(['status' => 'cancelled']);

        return $po->fresh();
    }

    public function recordGoodsReceived(array $data): GoodsReceivedNote
    {
        $po = PurchaseOrder::findOrFail($data['purchase_order_id']);

        if ($po->status !== 'open') {
            throw new LogicException("Purchase order #{$po->id} is not open (status: {$po->status}).");
        }

        return DB::transaction(function () use ($po, $data) {
            $grn = GoodsReceivedNote::create([
                'grn_number' => $this->nextNumber(GoodsReceivedNote::class, 'grn_number', 'GRN'),
                'purchase_order_id' => $po->id,
                'received_date' => $data['received_date'] ?? now()->toDateString(),
                'received_by' => $data['received_by'] ?? null,
                'notes' => $data['notes'] ?? null,
                'delivery_note_path' => $data['delivery_note_path'] ?? null,
                'delivery_note_original_name' => $data['delivery_note_original_name'] ?? null,
            ]);

            $po->update(['status' => 'received']);

            return $grn;
        });
    }

    public function recordSupplierInvoice(array $data): SupplierInvoice
    {
        $po = PurchaseOrder::with(['purchaseRequisition.voteHead.expenseAccount', 'purchaseRequisition.subVoteHead', 'supplier'])
            ->findOrFail($data['purchase_order_id']);

        if ($po->status !== 'received') {
            throw new LogicException("Purchase order #{$po->id} has not been marked received yet (status: {$po->status}).");
        }

        $amount = (float) ($data['amount'] ?? 0);

        if ($amount <= 0) {
            throw new LogicException('Invoice amount must be greater than zero.');
        }

        return DB::transaction(function () use ($po, $data, $amount) {
            $invoice = SupplierInvoice::create([
                'invoice_number' => $data['invoice_number'],
                'purchase_order_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'amount' => $amount,
                'amount_paid' => 0,
                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'invoice_document_path' => $data['invoice_document_path'] ?? null,
                'invoice_document_original_name' => $data['invoice_document_original_name'] ?? null,
                'status' => 'pending',
                'recorded_by' => $data['recorded_by'] ?? null,
            ]);

            $requisition = $po->purchaseRequisition;

            $entry = $this->journalPostingService->post([
                'entry_date' => $invoice->invoice_date,
                'description' => "Supplier invoice {$invoice->invoice_number} — {$po->supplier->name} (PO {$po->po_number})",
                'reference' => $invoice->invoice_number,
                'source_type' => SupplierInvoice::class,
                'source_id' => $invoice->id,
                'created_by' => $data['recorded_by'] ?? null,
                'lines' => [
                    [
                        'account_code' => $requisition->voteHead->expenseAccount->account_code,
                        'debit' => $amount,
                        'cost_centre' => $requisition->subVoteHead?->name ?? $requisition->voteHead->name,
                        'description' => $po->description,
                    ],
                    [
                        'account_code' => config('accounting.accounts_payable_account_code'),
                        'credit' => $amount,
                    ],
                ],
            ]);

            $invoice->update(['journal_entry_id' => $entry->id]);

            return $invoice->fresh();
        });
    }

    public function recordSupplierPayment(array $data): SupplierPayment
    {
        $amount = (float) ($data['amount'] ?? 0);

        if ($amount <= 0) {
            throw new LogicException('Payment amount must be greater than zero.');
        }

        $payment = DB::transaction(function () use ($data, $amount) {
            $invoice = SupplierInvoice::where('id', $data['supplier_invoice_id'])->lockForUpdate()->firstOrFail();

            if (!in_array($invoice->status, ['pending', 'partially_paid'], true)) {
                throw new LogicException("Supplier invoice #{$invoice->id} is not payable (status: {$invoice->status}).");
            }

            $outstanding = round((float) $invoice->amount - (float) $invoice->amount_paid, 2);

            if ($amount > $outstanding) {
                throw new LogicException("Payment amount ({$amount}) exceeds the outstanding balance ({$outstanding}) for invoice #{$invoice->id}.");
            }

            $payment = SupplierPayment::create([
                'supplier_invoice_id' => $invoice->id,
                'supplier_id' => $invoice->supplier_id,
                'amount' => $amount,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'paid_by' => $data['paid_by'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $newAmountPaid = round((float) $invoice->amount_paid + $amount, 2);

            $invoice->update([
                'amount_paid' => $newAmountPaid,
                'status' => $newAmountPaid >= (float) $invoice->amount ? 'paid' : 'partially_paid',
            ]);

            return $payment;
        });

        $this->postPaymentToLedger($payment);

        return $payment->fresh();
    }

    protected function postPaymentToLedger(SupplierPayment $payment): void
    {
        try {
            $payment->loadMissing('supplierInvoice.supplier');
            $invoice = $payment->supplierInvoice;

            $entry = $this->journalPostingService->post([
                'entry_date' => $payment->payment_date,
                'description' => "Payment to {$invoice->supplier->name} — invoice {$invoice->invoice_number}",
                'reference' => $payment->reference,
                'source_type' => SupplierPayment::class,
                'source_id' => $payment->id,
                'created_by' => $payment->paid_by,
                'lines' => [
                    ['account_code' => config('accounting.accounts_payable_account_code'), 'debit' => $payment->amount],
                    ['account_code' => $this->resolveMethodAccountCode($payment->method), 'credit' => $payment->amount],
                ],
            ]);

            $payment->update(['journal_entry_id' => $entry->id]);
        } catch (LogicException $e) {
            Log::warning("GL posting failed for SupplierPayment #{$payment->id}: {$e->getMessage()}");
        }
    }

    protected function resolveMethodAccountCode(?string $method): string
    {
        $map = $method
            ? PaymentMethodAccountMap::active()->where('method', $method)->first()
            : null;

        return $map?->account->account_code ?? config('accounting.default_cash_account_code');
    }

    protected function nextNumber(string $modelClass, string $column, string $prefix): string
    {
        return DB::transaction(function () use ($modelClass, $column, $prefix) {
            $year = now()->year;

            $count = $modelClass::where($column, 'like', "{$prefix}-{$year}-%")
                ->lockForUpdate()
                ->count();

            return sprintf('%s-%d-%04d', $prefix, $year, $count + 1);
        });
    }
}
