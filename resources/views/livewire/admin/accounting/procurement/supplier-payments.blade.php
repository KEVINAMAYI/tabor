<?php

use App\Models\PaymentMethodAccountMap;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Services\ProcurementService;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $p_supplier_invoice_id = null;
    public $p_amount = null;
    public $p_payment_date = '';
    public $p_method = '';
    public $p_reference = '';
    public $p_notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-supplier-invoices'), 403);
        $this->p_payment_date = now()->toDateString();
    }

    public function with(): array
    {
        return [
            'payments' => SupplierPayment::with(['supplierInvoice', 'supplier'])->orderByDesc('id')->paginate(20),
            'payableInvoices' => SupplierInvoice::whereIn('status', ['pending', 'partially_paid'])
                ->with('supplier')
                ->orderByDesc('id')
                ->get(),
            'methods' => PaymentMethodAccountMap::active()->pluck('method'),
            'selectedInvoice' => $this->p_supplier_invoice_id ? SupplierInvoice::find($this->p_supplier_invoice_id) : null,
        ];
    }

    public function openModal(): void
    {
        abort_unless(auth()->user()?->can('create-supplier-payments'), 403);
        $this->resetForm();
        $this->dispatch('show-payment-modal');
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('create-supplier-payments'), 403);

        $this->validate([
            'p_supplier_invoice_id' => 'required|exists:supplier_invoices,id',
            'p_amount' => 'required|numeric|min:0.01',
            'p_payment_date' => 'required|date',
            'p_method' => 'required|string',
        ]);

        try {
            app(ProcurementService::class)->recordSupplierPayment([
                'supplier_invoice_id' => $this->p_supplier_invoice_id,
                'amount' => $this->p_amount,
                'payment_date' => $this->p_payment_date,
                'method' => $this->p_method,
                'reference' => $this->p_reference,
                'notes' => $this->p_notes,
                'paid_by' => auth()->id(),
            ]);

            $this->resetForm();
            $this->dispatch('hide-payment-modal');

            LivewireAlert::text('Supplier payment recorded and posted to the GL.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Supplier payment recording failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    protected function resetForm(): void
    {
        $this->p_supplier_invoice_id = null;
        $this->p_amount = null;
        $this->p_payment_date = now()->toDateString();
        $this->p_method = '';
        $this->p_reference = '';
        $this->p_notes = '';
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Supplier Payments</h4>
            <p class="text-muted mb-0">Settles a supplier invoice (DR Accounts Payable / CR cash-bank account). Partial payments are supported.</p>
        </div>
        @can('create-supplier-payments')
            <button class="btn btn-primary btn-sm" wire:click="openModal" @disabled($payableInvoices->isEmpty())>
                <i class="ti ti-plus me-1"></i> Record Payment
            </button>
        @endcan
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Invoice</th>
                        <th>Supplier</th>
                        <th class="text-end">Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d M Y') }}</td>
                            <td class="small text-muted">{{ $payment->supplierInvoice->invoice_number }}</td>
                            <td>{{ $payment->supplier->name }}</td>
                            <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                            <td class="text-capitalize">{{ $payment->method }}</td>
                            <td>{{ $payment->reference ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No supplier payments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $payments->links() }}
        </div>
    </div>

    <div class="modal fade" id="paymentModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Supplier Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="save">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Invoice</label>
                            <select class="form-select" wire:model.live="p_supplier_invoice_id">
                                <option value="">Select invoice</option>
                                @foreach ($payableInvoices as $inv)
                                    <option value="{{ $inv->id }}">{{ $inv->invoice_number }} — {{ $inv->supplier->name }} (Outstanding: {{ number_format($inv->outstanding_balance, 2) }})</option>
                                @endforeach
                            </select>
                            @error('p_supplier_invoice_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        @if ($selectedInvoice)
                            <div class="alert alert-light border py-2 small mb-3">
                                Outstanding balance: <strong>{{ number_format($selectedInvoice->outstanding_balance, 2) }}</strong>
                            </div>
                        @endif
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" min="0" class="form-control" wire:model="p_amount">
                                @error('p_amount') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Date</label>
                                <input type="date" class="form-control" wire:model="p_payment_date">
                                @error('p_payment_date') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Method</label>
                                <select class="form-select" wire:model="p_method">
                                    <option value="">Select method</option>
                                    @foreach ($methods as $method)
                                        <option value="{{ $method }}" class="text-capitalize">{{ ucfirst($method) }}</option>
                                    @endforeach
                                </select>
                                @error('p_method') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reference</label>
                                <input type="text" class="form-control" wire:model="p_reference">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" wire:model="p_notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Record Payment</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@script
    <script>
        window.addEventListener('show-payment-modal', () => new bootstrap.Modal(document.getElementById('paymentModal')).show());
        window.addEventListener('hide-payment-modal', () => bootstrap.Modal.getInstance(document.getElementById('paymentModal'))?.hide());
    </script>
@endscript
