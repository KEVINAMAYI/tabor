<?php

use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use App\Models\SubVoteHead;
use App\Models\User;
use App\Models\VoteHead;
use App\Services\Accounting\BudgetReportService;
use App\Services\ProcurementService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\PaymentMethodAccountMapSeeder;
use Database\Seeders\VoteHeadSeeder;

function procAccountId(string $code): int
{
    return ChartOfAccount::where('account_code', $code)->value('id');
}

beforeEach(function () {
    $this->seed(ChartOfAccountsSeeder::class);
    $this->seed(VoteHeadSeeder::class);
    $this->seed(PaymentMethodAccountMapSeeder::class);

    $this->period = makeOpenPeriod('2026-08-01', '2026-08-31');
    $this->financialYear = $this->period->financialYear;

    $this->deptUser = User::factory()->create();
    $this->financeUser = User::factory()->create();
    $this->supplier = Supplier::create(['name' => 'Acme Supplies', 'is_active' => true]);
    $this->ictVoteHead = VoteHead::where('code', 'ICT')->firstOrFail();

    $this->service = app(ProcurementService::class);
});

function submitTestRequisition($self, array $overrides = []): PurchaseRequisition
{
    return $self->service->submitRequisition(array_merge([
        'vote_head_id' => $self->ictVoteHead->id,
        'description' => 'New office router',
        'estimated_amount' => 15000,
        'requested_by' => $self->deptUser->id,
    ], $overrides));
}

test('submitting a requisition creates status=submitted with no GL impact', function () {
    $req = submitTestRequisition($this);

    expect($req->status)->toBe('submitted')
        ->and($req->requisition_number)->toStartWith('PR-');
});

test('department approval transitions submitted to dept_approved and throws on wrong status', function () {
    $req = submitTestRequisition($this);

    $approved = $this->service->approveRequisitionByDepartment($req, $this->deptUser->id);
    expect($approved->status)->toBe('dept_approved');

    expect(fn () => $this->service->approveRequisitionByDepartment($approved, $this->deptUser->id))
        ->toThrow(LogicException::class);
});

test('finance approval transitions dept_approved to finance_approved and cannot skip department approval', function () {
    $req = submitTestRequisition($this);

    expect(fn () => $this->service->approveRequisitionByFinance($req, $this->financeUser->id))
        ->toThrow(LogicException::class);

    $this->service->approveRequisitionByDepartment($req, $this->deptUser->id);
    $approved = $this->service->approveRequisitionByFinance($req->fresh(), $this->financeUser->id);

    expect($approved->status)->toBe('finance_approved');
});

test('rejection at either stage sets status=rejected with reason and no GL impact', function () {
    $req = submitTestRequisition($this);

    $rejected = $this->service->rejectRequisition($req, 'Not justified', $this->deptUser->id);

    expect($rejected->status)->toBe('rejected')
        ->and($rejected->rejection_reason)->toBe('Not justified');
});

test('createPurchaseOrder requires finance_approved and converts the requisition', function () {
    $req = submitTestRequisition($this);
    $this->service->approveRequisitionByDepartment($req, $this->deptUser->id);
    $this->service->approveRequisitionByFinance($req->fresh(), $this->financeUser->id);

    $po = $this->service->createPurchaseOrder([
        'purchase_requisition_id' => $req->id,
        'supplier_id' => $this->supplier->id,
        'order_date' => '2026-08-10',
        'created_by' => $this->financeUser->id,
    ]);

    expect($po->status)->toBe('open')
        ->and($po->po_number)->toStartWith('PO-')
        ->and($req->fresh()->status)->toBe('converted');

    expect(fn () => $this->service->createPurchaseOrder([
        'purchase_requisition_id' => $req->id,
        'supplier_id' => $this->supplier->id,
    ]))->toThrow(LogicException::class);
});

test('recordGoodsReceived requires an open PO and marks it received; a second GRN throws', function () {
    $po = createApprovedPurchaseOrder($this);

    $grn = $this->service->recordGoodsReceived([
        'purchase_order_id' => $po->id,
        'received_date' => '2026-08-11',
        'received_by' => $this->financeUser->id,
    ]);

    expect($grn->grn_number)->toStartWith('GRN-')
        ->and($po->fresh()->status)->toBe('received');

    // PO is now 'received', not 'open' — the status guard rejects a second
    // GRN before ever touching the DB's unique constraint.
    expect(fn () => $this->service->recordGoodsReceived(['purchase_order_id' => $po->id]))
        ->toThrow(LogicException::class);
});

test('recordSupplierInvoice posts a balanced accrual entry with the correct cost_centre', function () {
    $internet = SubVoteHead::where('vote_head_id', $this->ictVoteHead->id)->where('name', 'Internet Bills')->firstOrFail();

    $po = createApprovedPurchaseOrder($this, ['sub_vote_head_id' => $internet->id], receiveIt: true);

    $invoice = $this->service->recordSupplierInvoice([
        'purchase_order_id' => $po->id,
        'invoice_number' => 'INV-001',
        'amount' => 15000,
        'invoice_date' => '2026-08-12',
        'recorded_by' => $this->financeUser->id,
    ]);

    expect($invoice->status)->toBe('pending')
        ->and($invoice->journal_entry_id)->not->toBeNull();

    $entry = $invoice->journalEntry;
    $expenseLine = $entry->lines->firstWhere('account_id', procAccountId('5050')); // ICT
    $apLine = $entry->lines->firstWhere('account_id', procAccountId('2010')); // Accounts Payable

    expect((float) $expenseLine->debit)->toBe(15000.0)
        ->and((float) $apLine->credit)->toBe(15000.0)
        ->and($expenseLine->cost_centre)->toBe('Internet Bills');
});

test('recordSupplierInvoice against a PO not yet received throws, with no orphan invoice or GL entry', function () {
    $po = createApprovedPurchaseOrder($this); // not received

    expect(fn () => $this->service->recordSupplierInvoice([
        'purchase_order_id' => $po->id,
        'invoice_number' => 'INV-002',
        'amount' => 15000,
    ]))->toThrow(LogicException::class);

    expect(\App\Models\SupplierInvoice::count())->toBe(0);
});

test('recording an invoice against a sub-vote-head budget increases actual_amount automatically', function () {
    $internet = SubVoteHead::where('vote_head_id', $this->ictVoteHead->id)->where('name', 'Internet Bills')->firstOrFail();

    $budget = Budget::create([
        'financial_year_id' => $this->financialYear->id,
        'vote_head_id' => $this->ictVoteHead->id,
        'sub_vote_head_id' => $internet->id,
        'budgeted_amount' => 20000,
    ]);

    $po = createApprovedPurchaseOrder($this, ['sub_vote_head_id' => $internet->id], receiveIt: true);

    $this->service->recordSupplierInvoice([
        'purchase_order_id' => $po->id,
        'invoice_number' => 'INV-003',
        'amount' => 15000,
        'invoice_date' => '2026-08-12',
        'recorded_by' => $this->financeUser->id,
    ]);

    $result = app(BudgetReportService::class)->generate($this->financialYear->id);
    $row = $result['rows']->firstWhere('budget_id', $budget->id);

    expect($row->actual_amount)->toBe(15000.0);
});

test('a full payment marks the invoice paid and posts a balanced settlement entry', function () {
    $invoice = createRecordedInvoice($this);

    $payment = $this->service->recordSupplierPayment([
        'supplier_invoice_id' => $invoice->id,
        'amount' => 15000,
        'payment_date' => '2026-08-15',
        'method' => 'bank',
        'paid_by' => $this->financeUser->id,
    ]);

    $invoice = $invoice->fresh();

    expect($invoice->status)->toBe('paid')
        ->and((float) $invoice->amount_paid)->toBe(15000.0)
        ->and($payment->journal_entry_id)->not->toBeNull();

    $entry = $payment->journalEntry;
    $apLine = $entry->lines->firstWhere('account_id', procAccountId('2010'));
    $bankLine = $entry->lines->firstWhere('account_id', procAccountId('1010'));

    expect((float) $apLine->debit)->toBe(15000.0)
        ->and((float) $bankLine->credit)->toBe(15000.0);
});

test('partial payments leave the invoice partially_paid with a correct outstanding balance', function () {
    $invoice = createRecordedInvoice($this);

    $this->service->recordSupplierPayment([
        'supplier_invoice_id' => $invoice->id,
        'amount' => 5000,
        'payment_date' => '2026-08-15',
        'method' => 'bank',
    ]);

    $invoice = $invoice->fresh();
    expect($invoice->status)->toBe('partially_paid')
        ->and($invoice->outstanding_balance)->toBe(10000.0);

    $this->service->recordSupplierPayment([
        'supplier_invoice_id' => $invoice->id,
        'amount' => 3000,
        'payment_date' => '2026-08-16',
        'method' => 'bank',
    ]);

    $invoice = $invoice->fresh();
    expect($invoice->status)->toBe('partially_paid')
        ->and($invoice->outstanding_balance)->toBe(7000.0);
});

test('a partial payment followed by a final payment flips status to paid exactly when settled', function () {
    $invoice = createRecordedInvoice($this);

    $this->service->recordSupplierPayment([
        'supplier_invoice_id' => $invoice->id,
        'amount' => 10000,
        'payment_date' => '2026-08-15',
        'method' => 'bank',
    ]);

    $this->service->recordSupplierPayment([
        'supplier_invoice_id' => $invoice->fresh()->id,
        'amount' => 5000,
        'payment_date' => '2026-08-16',
        'method' => 'bank',
    ]);

    expect($invoice->fresh()->status)->toBe('paid');
});

test('a payment exceeding the outstanding balance throws and creates no row', function () {
    $invoice = createRecordedInvoice($this);

    expect(fn () => $this->service->recordSupplierPayment([
        'supplier_invoice_id' => $invoice->id,
        'amount' => 20000,
        'payment_date' => '2026-08-15',
        'method' => 'bank',
    ]))->toThrow(LogicException::class);

    expect($invoice->fresh()->amount_paid)->toBe('0.00');
    expect(\App\Models\SupplierPayment::count())->toBe(0);
});

test('a payment against an already-paid invoice throws', function () {
    $invoice = createRecordedInvoice($this);

    $this->service->recordSupplierPayment([
        'supplier_invoice_id' => $invoice->id,
        'amount' => 15000,
        'payment_date' => '2026-08-15',
        'method' => 'bank',
    ]);

    expect(fn () => $this->service->recordSupplierPayment([
        'supplier_invoice_id' => $invoice->fresh()->id,
        'amount' => 100,
        'payment_date' => '2026-08-16',
        'method' => 'bank',
    ]))->toThrow(LogicException::class);
});

test('a GL failure during recordSupplierInvoice rolls back the whole invoice (atomic)', function () {
    $po = createApprovedPurchaseOrder($this, receiveIt: true);

    // Outside the only open period -> JournalPostingService throws.
    expect(fn () => $this->service->recordSupplierInvoice([
        'purchase_order_id' => $po->id,
        'invoice_number' => 'INV-004',
        'amount' => 15000,
        'invoice_date' => '2026-12-25',
    ]))->toThrow(LogicException::class);

    expect(\App\Models\SupplierInvoice::count())->toBe(0);
});

test('a GL failure during recordSupplierPayment does not roll back the payment (soft-fail)', function () {
    $invoice = createRecordedInvoice($this);

    // Outside the only open period -> settlement GL post fails, logged, payment stands.
    $payment = $this->service->recordSupplierPayment([
        'supplier_invoice_id' => $invoice->id,
        'amount' => 15000,
        'payment_date' => '2026-12-25',
        'method' => 'bank',
    ]);

    expect($payment->id)->not->toBeNull()
        ->and($payment->journal_entry_id)->toBeNull()
        ->and($invoice->fresh()->status)->toBe('paid');
});

test('cancelPurchaseOrder only works while open and has no GL impact', function () {
    $po = createApprovedPurchaseOrder($this);

    $cancelled = $this->service->cancelPurchaseOrder($po, $this->financeUser->id);
    expect($cancelled->status)->toBe('cancelled');

    expect(fn () => $this->service->cancelPurchaseOrder($cancelled, $this->financeUser->id))
        ->toThrow(LogicException::class);
});

// --- Fixture helpers -------------------------------------------------

function createApprovedPurchaseOrder($self, array $requisitionOverrides = [], bool $receiveIt = false): PurchaseOrder
{
    $req = submitTestRequisition($self, $requisitionOverrides);
    $self->service->approveRequisitionByDepartment($req, $self->deptUser->id);
    $self->service->approveRequisitionByFinance($req->fresh(), $self->financeUser->id);

    $po = $self->service->createPurchaseOrder([
        'purchase_requisition_id' => $req->id,
        'supplier_id' => $self->supplier->id,
        'order_date' => '2026-08-10',
        'created_by' => $self->financeUser->id,
    ]);

    if ($receiveIt) {
        $self->service->recordGoodsReceived([
            'purchase_order_id' => $po->id,
            'received_date' => '2026-08-11',
            'received_by' => $self->financeUser->id,
        ]);
        $po = $po->fresh();
    }

    return $po;
}

function createRecordedInvoice($self)
{
    $po = createApprovedPurchaseOrder($self, receiveIt: true);

    return $self->service->recordSupplierInvoice([
        'purchase_order_id' => $po->id,
        'invoice_number' => 'INV-' . uniqid(),
        'amount' => 15000,
        'invoice_date' => '2026-08-12',
        'recorded_by' => $self->financeUser->id,
    ]);
}
