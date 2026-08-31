<?php

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
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

    public $po_requisition_id = null;
    public $po_supplier_id = null;
    public $po_order_date = '';
    public $po_expected_delivery_date = '';
    public $po_description = '';
    public $po_amount = null;
    public $po_document = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-purchase-orders'), 403);
        $this->po_order_date = now()->toDateString();
    }

    public function with(): array
    {
        return [
            'purchaseOrders' => PurchaseOrder::query()
                ->with(['purchaseRequisition', 'supplier'])
                ->when(filled($this->statusFilter), fn ($q) => $q->where('status', $this->statusFilter))
                ->orderByDesc('id')
                ->paginate(20),
            'approvedRequisitions' => PurchaseRequisition::where('status', 'finance_approved')->orderBy('requisition_number')->get(),
            'suppliers' => Supplier::active()->orderBy('name')->get(),
        ];
    }

    public function updatedPoRequisitionId(): void
    {
        if ($this->po_requisition_id) {
            $req = PurchaseRequisition::find($this->po_requisition_id);
            $this->po_description = $req?->description;
            $this->po_amount = $req?->estimated_amount;
        }
    }

    public function openModal(): void
    {
        abort_unless(auth()->user()?->can('create-purchase-orders'), 403);
        $this->resetForm();
        $this->dispatch('show-po-modal');
    }

    public function create(): void
    {
        abort_unless(auth()->user()?->can('create-purchase-orders'), 403);

        $this->validate([
            'po_requisition_id' => 'required|exists:purchase_requisitions,id',
            'po_supplier_id' => 'required|exists:suppliers,id',
            'po_order_date' => 'required|date',
            'po_expected_delivery_date' => 'nullable|date',
            'po_description' => 'required|string|max:1000',
            'po_amount' => 'required|numeric|min:0.01',
            'po_document' => 'nullable|file|max:5120',
        ]);

        try {
            $documentPath = null;
            $documentOriginalName = null;

            if ($this->po_document) {
                $documentPath = $this->po_document->store('purchase-order-documents', 'public');
                $documentOriginalName = $this->po_document->getClientOriginalName();
            }

            app(ProcurementService::class)->createPurchaseOrder([
                'purchase_requisition_id' => $this->po_requisition_id,
                'supplier_id' => $this->po_supplier_id,
                'order_date' => $this->po_order_date,
                'expected_delivery_date' => $this->po_expected_delivery_date ?: null,
                'description' => $this->po_description,
                'amount' => $this->po_amount,
                'created_by' => auth()->id(),
                'document_path' => $documentPath,
                'document_original_name' => $documentOriginalName,
            ]);

            $this->resetForm();
            $this->dispatch('hide-po-modal');

            LivewireAlert::text('Purchase order created.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Purchase order creation failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function cancel($id): void
    {
        abort_unless(auth()->user()?->can('create-purchase-orders'), 403);

        try {
            app(ProcurementService::class)->cancelPurchaseOrder(PurchaseOrder::findOrFail($id), auth()->id());

            LivewireAlert::text('Purchase order cancelled.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Purchase order cancellation failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    protected function resetForm(): void
    {
        $this->po_requisition_id = null;
        $this->po_supplier_id = null;
        $this->po_order_date = now()->toDateString();
        $this->po_expected_delivery_date = '';
        $this->po_description = '';
        $this->po_amount = null;
        $this->po_document = null;
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Purchase Orders</h4>
            <p class="text-muted mb-0">Created from finance-approved requisitions. No GL impact yet — a PO is a commitment, not a recognized expense.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.procurement.requisitions') }}" class="btn btn-outline-secondary btn-sm">Requisitions</a>
            <a href="{{ route('accounting.procurement.goods-received') }}" class="btn btn-outline-secondary btn-sm">Goods Received</a>
            @can('create-purchase-orders')
                <button class="btn btn-primary btn-sm" wire:click="openModal" @disabled($approvedRequisitions->isEmpty())>
                    <i class="ti ti-plus me-1"></i> New PO
                </button>
            @endcan
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="">All Statuses</option>
                <option value="open">Open</option>
                <option value="received">Received</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>PO #</th>
                        <th>Requisition</th>
                        <th>Supplier</th>
                        <th>Order Date</th>
                        <th class="text-end">Amount</th>
                        <th>Document</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchaseOrders as $po)
                        <tr>
                            <td>{{ $po->po_number }}</td>
                            <td class="small text-muted">{{ $po->purchaseRequisition->requisition_number }}</td>
                            <td>{{ $po->supplier->name }}</td>
                            <td>{{ $po->order_date->format('d M Y') }}</td>
                            <td class="text-end">{{ number_format($po->amount, 2) }}</td>
                            <td>
                                @if ($po->document_url)
                                    <a href="{{ $po->document_url }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="ti ti-paperclip"></i></a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusClasses = match ($po->status) {
                                        'received' => 'bg-success-subtle text-success',
                                        'open' => 'bg-warning-subtle text-warning',
                                        default => 'bg-danger-subtle text-danger',
                                    };
                                @endphp
                                <span class="badge {{ $statusClasses }}">{{ ucfirst($po->status) }}</span>
                            </td>
                            <td>
                                @can('create-purchase-orders')
                                    @if ($po->status === 'open')
                                        <button class="btn btn-sm btn-outline-danger" wire:click="cancel({{ $po->id }})">Cancel</button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">No purchase orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $purchaseOrders->links() }}
        </div>
    </div>

    <div class="modal fade" id="poModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="create">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Requisition</label>
                                <select class="form-select" wire:model.live="po_requisition_id">
                                    <option value="">Select finance-approved requisition</option>
                                    @foreach ($approvedRequisitions as $req)
                                        <option value="{{ $req->id }}">{{ $req->requisition_number }} — {{ number_format($req->estimated_amount, 2) }}</option>
                                    @endforeach
                                </select>
                                @error('po_requisition_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Supplier</label>
                                <select class="form-select" wire:model="po_supplier_id">
                                    <option value="">Select supplier</option>
                                    @foreach ($suppliers as $sup)
                                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                    @endforeach
                                </select>
                                @error('po_supplier_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Order Date</label>
                                <input type="date" class="form-control" wire:model="po_order_date">
                                @error('po_order_date') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expected Delivery</label>
                                <input type="date" class="form-control" wire:model="po_expected_delivery_date">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" wire:model="po_description" rows="2"></textarea>
                            @error('po_description') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" min="0" class="form-control" wire:model="po_amount">
                                @error('po_amount') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">PO Document</label>
                                <input type="file" class="form-control" wire:model="po_document">
                                @error('po_document') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Create PO</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.addEventListener('show-po-modal', () => new bootstrap.Modal(document.getElementById('poModal')).show());
            window.addEventListener('hide-po-modal', () => bootstrap.Modal.getInstance(document.getElementById('poModal'))?.hide());
        });
    </script>
@endpush
