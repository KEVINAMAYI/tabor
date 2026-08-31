<?php

use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
use App\Services\ProcurementService;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithFileUploads;
    use WithPagination;

    public $g_purchase_order_id = null;
    public $g_received_date = '';
    public $g_notes = '';
    public $g_delivery_note = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('record-goods-received'), 403);
        $this->g_received_date = now()->toDateString();
    }

    public function with(): array
    {
        return [
            'grns' => GoodsReceivedNote::with(['purchaseOrder.supplier', 'receivedBy'])->orderByDesc('id')->paginate(20),
            'openPurchaseOrders' => PurchaseOrder::where('status', 'open')->with('supplier')->orderBy('po_number')->get(),
        ];
    }

    public function openModal(): void
    {
        abort_unless(auth()->user()?->can('record-goods-received'), 403);
        $this->resetForm();
        $this->dispatch('show-grn-modal');
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('record-goods-received'), 403);

        $this->validate([
            'g_purchase_order_id' => 'required|exists:purchase_orders,id',
            'g_received_date' => 'required|date',
            'g_delivery_note' => 'nullable|file|max:5120',
        ]);

        try {
            $deliveryNotePath = null;
            $deliveryNoteOriginalName = null;

            if ($this->g_delivery_note) {
                $deliveryNotePath = $this->g_delivery_note->store('grn-delivery-notes', 'public');
                $deliveryNoteOriginalName = $this->g_delivery_note->getClientOriginalName();
            }

            app(ProcurementService::class)->recordGoodsReceived([
                'purchase_order_id' => $this->g_purchase_order_id,
                'received_date' => $this->g_received_date,
                'received_by' => auth()->id(),
                'notes' => $this->g_notes,
                'delivery_note_path' => $deliveryNotePath,
                'delivery_note_original_name' => $deliveryNoteOriginalName,
            ]);

            $this->resetForm();
            $this->dispatch('hide-grn-modal');

            LivewireAlert::text('Goods received note recorded.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('GRN recording failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    protected function resetForm(): void
    {
        $this->g_purchase_order_id = null;
        $this->g_received_date = now()->toDateString();
        $this->g_notes = '';
        $this->g_delivery_note = null;
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Goods Received Notes</h4>
            <p class="text-muted mb-0">Confirms delivery before a supplier invoice can be recorded. No stock-quantity tracking yet (Inventory is a future phase).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.procurement.supplier-invoices') }}" class="btn btn-outline-secondary btn-sm">Supplier Invoices</a>
            @can('record-goods-received')
                <button class="btn btn-primary btn-sm" wire:click="openModal" @disabled($openPurchaseOrders->isEmpty())>
                    <i class="ti ti-plus me-1"></i> Record GRN
                </button>
            @endcan
        </div>
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>GRN #</th>
                        <th>PO #</th>
                        <th>Supplier</th>
                        <th>Received Date</th>
                        <th>Received By</th>
                        <th>Delivery Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($grns as $grn)
                        <tr>
                            <td>{{ $grn->grn_number }}</td>
                            <td class="small text-muted">{{ $grn->purchaseOrder->po_number }}</td>
                            <td>{{ $grn->purchaseOrder->supplier->name }}</td>
                            <td>{{ $grn->received_date->format('d M Y') }}</td>
                            <td>{{ $grn->receivedBy->name ?? '—' }}</td>
                            <td>
                                @if ($grn->delivery_note_url)
                                    <a href="{{ $grn->delivery_note_url }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="ti ti-paperclip"></i></a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No goods received notes yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $grns->links() }}
        </div>
    </div>

    <div class="modal fade" id="grnModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Goods Received</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="save">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Purchase Order</label>
                            <select class="form-select" wire:model="g_purchase_order_id">
                                <option value="">Select open PO</option>
                                @foreach ($openPurchaseOrders as $po)
                                    <option value="{{ $po->id }}">{{ $po->po_number }} — {{ $po->supplier->name }}</option>
                                @endforeach
                            </select>
                            @error('g_purchase_order_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Received Date</label>
                            <input type="date" class="form-control" wire:model="g_received_date">
                            @error('g_received_date') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" wire:model="g_notes" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Delivery Note</label>
                            <input type="file" class="form-control" wire:model="g_delivery_note">
                            @error('g_delivery_note') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@script
    <script>
        window.addEventListener('show-grn-modal', () => new bootstrap.Modal(document.getElementById('grnModal')).show());
        window.addEventListener('hide-grn-modal', () => bootstrap.Modal.getInstance(document.getElementById('grnModal'))?.hide());
    </script>
@endscript
