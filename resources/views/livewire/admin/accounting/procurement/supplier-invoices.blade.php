<?php

use App\Models\PurchaseOrder;
use App\Models\SupplierInvoice;
use App\Services\ProcurementService;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithFileUploads;
    use WithPagination;

    public $statusFilter = '';

    public $i_purchase_order_id = null;
    public $i_invoice_number = '';
    public $i_amount = null;
    public $i_invoice_date = '';
    public $i_due_date = '';
    public $i_document = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-supplier-invoices'), 403);
        $this->i_invoice_date = now()->toDateString();
    }

    public function with(): array
    {
        return [
            'invoices' => SupplierInvoice::query()
                ->with(['supplier', 'purchaseOrder'])
                ->when(filled($this->statusFilter), fn ($q) => $q->where('status', $this->statusFilter))
                ->orderByDesc('id')
                ->paginate(20),
            'receivedPurchaseOrders' => PurchaseOrder::where('status', 'received')
                ->whereDoesntHave('invoice')
                ->with('supplier')
                ->orderBy('po_number')
                ->get(),
        ];
    }

    public function updatedIPurchaseOrderId(): void
    {
        if ($this->i_purchase_order_id) {
            $this->i_amount = PurchaseOrder::find($this->i_purchase_order_id)?->amount;
        }
    }

    public function openModal(): void
    {
        abort_unless(auth()->user()?->can('record-supplier-invoices'), 403);
        $this->resetForm();
        $this->dispatch('show-invoice-modal');
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('record-supplier-invoices'), 403);

        $this->validate([
            'i_purchase_order_id' => 'required|exists:purchase_orders,id',
            'i_invoice_number' => 'required|string|max:255',
            'i_amount' => 'required|numeric|min:0.01',
            'i_invoice_date' => 'required|date',
            'i_due_date' => 'nullable|date',
            'i_document' => 'nullable|file|max:5120',
        ]);

        try {
            $documentPath = null;
            $documentOriginalName = null;

            if ($this->i_document) {
                $documentPath = $this->i_document->store('supplier-invoices', 'public');
                $documentOriginalName = $this->i_document->getClientOriginalName();
            }

            app(ProcurementService::class)->recordSupplierInvoice([
                'purchase_order_id' => $this->i_purchase_order_id,
                'invoice_number' => $this->i_invoice_number,
                'amount' => $this->i_amount,
                'invoice_date' => $this->i_invoice_date,
                'due_date' => $this->i_due_date ?: null,
                'invoice_document_path' => $documentPath,
                'invoice_document_original_name' => $documentOriginalName,
                'recorded_by' => auth()->id(),
            ]);

            $this->resetForm();
            $this->dispatch('hide-invoice-modal');

            LivewireAlert::text('Supplier invoice recorded and posted to the GL.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Supplier invoice recording failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    protected function resetForm(): void
    {
        $this->i_purchase_order_id = null;
        $this->i_invoice_number = '';
        $this->i_amount = null;
        $this->i_invoice_date = now()->toDateString();
        $this->i_due_date = '';
        $this->i_document = null;
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Supplier Invoices</h4>
            <p class="text-muted mb-0">Recording an invoice posts the accrual entry (DR expense / CR Accounts Payable) immediately.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.procurement.supplier-payments') }}" class="btn btn-outline-secondary btn-sm">Supplier Payments</a>
            @can('record-supplier-invoices')
                <button class="btn btn-primary btn-sm" wire:click="openModal" @disabled($receivedPurchaseOrders->isEmpty())>
                    <i class="ti ti-plus me-1"></i> Record Invoice
                </button>
            @endcan
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="partially_paid">Partially Paid</option>
                <option value="paid">Paid</option>
            </select>
        </div>
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>PO #</th>
                        <th>Supplier</th>
                        <th>Invoice Date</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Outstanding</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td class="small text-muted">{{ $invoice->purchaseOrder->po_number }}</td>
                            <td>{{ $invoice->supplier->name }}</td>
                            <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                            <td class="text-end">{{ number_format($invoice->amount, 2) }}</td>
                            <td class="text-end">{{ number_format($invoice->amount_paid, 2) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($invoice->outstanding_balance, 2) }}</td>
                            <td>
                                @php
                                    $statusClasses = match ($invoice->status) {
                                        'paid' => 'bg-success-subtle text-success',
                                        'partially_paid' => 'bg-warning-subtle text-warning',
                                        'pending' => 'bg-secondary-subtle text-secondary',
                                        default => 'bg-danger-subtle text-danger',
                                    };
                                @endphp
                                <span class="badge {{ $statusClasses }}">{{ str_replace('_', ' ', ucfirst($invoice->status)) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">No supplier invoices yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $invoices->links() }}
        </div>
    </div>

    <div class="modal fade" id="invoiceModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Supplier Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="save">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Purchase Order (received, not yet invoiced)</label>
                            <select class="form-select" wire:model.live="i_purchase_order_id">
                                <option value="">Select PO</option>
                                @foreach ($receivedPurchaseOrders as $po)
                                    <option value="{{ $po->id }}">{{ $po->po_number }} — {{ $po->supplier->name }}</option>
                                @endforeach
                            </select>
                            @error('i_purchase_order_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Supplier's Invoice Number</label>
                            <input type="text" class="form-control" wire:model="i_invoice_number">
                            @error('i_invoice_number') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Invoice Date</label>
                                <input type="date" class="form-control" wire:model="i_invoice_date">
                                @error('i_invoice_date') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Due Date</label>
                                <input type="date" class="form-control" wire:model="i_due_date">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model="i_amount">
                            @error('i_amount') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Invoice Document</label>
                            <input type="file" class="form-control" wire:model="i_document">
                            @error('i_document') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Record Invoice</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@script
    <script>
        window.addEventListener('show-invoice-modal', () => new bootstrap.Modal(document.getElementById('invoiceModal')).show());
        window.addEventListener('hide-invoice-modal', () => bootstrap.Modal.getInstance(document.getElementById('invoiceModal'))?.hide());
    </script>
@endscript
