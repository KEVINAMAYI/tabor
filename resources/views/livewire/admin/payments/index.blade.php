<?php

use App\Models\Payment;
use App\Models\Enrollment;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\WithPagination;

new class extends Component {

    use WithPagination;

    public $selectAll = false;
    public $amount, $method, $reference, $paid_at, $enrollment_id;
    public $editId = null;
    public $selected = [];
    public $search = '';
    public $enrollments = [];

    public function rules()
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,mpesa,card,bank',
            'reference' => 'nullable|string|max:255',
            'paid_at' => 'nullable|date',
            'enrollment_id' => 'required|exists:enrollments,id',
        ];
    }

    public function mount()
    {
        $this->enrollments = Enrollment::all(); // Get all enrollments
    }

    #[On('search')]
    public function search()
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function with()
    {
        $payments = Payment::with(['enrollment.student', 'enrollment.course']) // Eager load nested relationships for display
        ->when(!empty($this->search), function ($q) {
            $q->where(function ($query) {
                $query->whereHas('enrollment.student', function ($query) {
                    $query->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                })
                    ->orWhere('method', 'like', "%{$this->search}%")
                    ->orWhere('reference', 'like', "%{$this->search}%");
            });
        })
            ->latest()
            ->paginate(10); // Use paginate() instead of get(), specify items per page

        return [
            'payments' => $payments, // Pass the Paginator instance
        ];
    }


    public function addPayment()
    {
        $this->validate();

        try {

            DB::beginTransaction();
            Payment::create([
                'enrollment_id' => $this->enrollment_id,
                'amount' => $this->amount,
                'method' => $this->method,
                'reference' => $this->reference,
                'paid_at' => $this->paid_at,
            ]);

            DB::commit();

            $this->resetForm();
            $this->resetPage();
            $this->dispatch('hide-payment-modal');

            LivewireAlert::text('Payment added successfully.!')
                ->success()
                ->toast()
                ->position('top-end')
                ->show();

        } catch (\Exception $e) {

            DB::rollBack();
            Log::error('Error adding payment: ' . $e->getMessage());

            LivewireAlert::text('Failed to add payment.!')
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
        }
    }

    public function editPayment($id)
    {
        $payment = Payment::findOrFail($id);

        $this->editId = $payment->id;
        $this->enrollment_id = $payment->enrollment_id;
        $this->amount = $payment->amount;
        $this->method = $payment->method;
        $this->reference = $payment->reference;
        $this->paid_at = $payment->paid_at;

        $this->dispatch('show-payment-modal');
    }

    public function updatePayment()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $payment = Payment::findOrFail($this->editId);

            $payment->update([
                'enrollment_id' => $this->enrollment_id,
                'amount' => $this->amount,
                'method' => $this->method,
                'reference' => $this->reference,
                'paid_at' => $this->paid_at,
            ]);

            DB::commit();

            $this->resetForm();
            $this->resetPage();
            $this->dispatch('hide-payment-modal');

            LivewireAlert::text('Payment updated successfully.!')
                ->success()
                ->toast()
                ->position('top-end')
                ->show();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update payment: ' . $e->getMessage());
            LivewireAlert::text('Failed to update payment.!')
                ->error()
                ->toast()
                ->position('top-end')
                ->show();

        }
    }

    public function deletePayment($id)
    {
        Payment::findOrFail($id)->delete();
        $this->resetPage();

        LivewireAlert::text('Payment deleted successfully.!')
            ->success()
            ->toast()
            ->position('top-end')
            ->show();
    }

    public function deleteSelected()
    {
        Payment::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();

        LivewireAlert::text('Payments deleted successfully.!')
            ->success()
            ->toast()
            ->position('top-end')
            ->show();
    }

    private function resetForm()
    {
        $this->enrollment_id = $this->search = $this->amount = $this->method = $this->reference = $this->paid_at = null;
        $this->editId = null;
    }

    #[On('select-all')]
     public function selectAll()
     {
         if ($this->selectAll) {
             $currentPagePaymentIds = Payment::with(['enrollment.student', 'enrollment.course']) // Ensure the same query logic
             ->when(!empty($this->search), function ($q) {
                 $q->where(function ($query) {
                     $query->whereHas('enrollment.student', function ($query) {
                         $query->where('first_name', 'like', "%{$this->search}%")
                             ->orWhere('last_name', 'like', "%{$this->search}%")
                             ->orWhere('email', 'like', "%{$this->search}%");
                     })
                         ->orWhere('method', 'like', "%{$this->search}%")
                         ->orWhere('reference', 'like', "%{$this->search}%");
                 });
             })
                 ->latest()
                 ->paginate(10)
                 ->pluck('id')
                 ->map(fn($id) => (string)$id)
                 ->toArray();

             $this->selected = $currentPagePaymentIds;
         } else {
             $this->selected = [];
         }
     }
}; ?>

@push('styles')
    <style>
        .pagination {
            margin-left: 10px;
        }
    </style>
@endpush

<div class="row">
    <div class="col-12">
        <div class="widget-content searchable-container list">
            <div class="card card-body">
                <div class="row">
                    <div class="col-md-4 col-xl-3">
                        <!-- Search Input -->
                        <form class="position-relative">
                            <input wire:keyup.debounce.100ms="$dispatch('search')"
                                   type="text"
                                   class="form-control product-search ps-5"
                                   placeholder="Search Payments..."
                                   wire:model="search"/>
                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div
                        class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                        @if (count($selected) > 0)
                            <!-- Delete Selected Button -->
                            <div class="action-btn">
                                <a href="javascript:void(0)"
                                   wire:click.prevent="deleteSelected"
                                   class="delete-multiple bg-danger-subtle btn me-2 text-danger">
                                    <i class="ti ti-trash me-1 fs-5"></i> Delete Selected
                                </a>
                            </div>
                        @endif
                        <!-- Add Payment Button -->
                        <a href="javascript:void(0)"
                           wire:click="$dispatch('show-payment-modal')"
                           class="btn btn-primary d-flex align-items-center">
                            <i class="ti ti-credit-card text-white me-1 fs-5"></i> Add Payment
                        </a>
                    </div>
                </div>
            </div>

            <!-- Add Payment Modal -->
            <div class="modal fade" id="addPaymentModal" tabindex="-1"
                 role="dialog" aria-labelledby="addPaymentModalTitle"
                 aria-hidden="true" wire:ignore.self>
                <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header d-flex align-items-center">
                            <h5 class="modal-title">Payment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form wire:submit.prevent="{{ $editId ? 'updatePayment' : 'addPayment' }}">
                            <div class="modal-body">
                                <div class="row">
                                    <!-- Enrollment Selector -->
                                    <div class="col-md-6 mb-3">
                                        <select wire:model="enrollment_id" class="form-control">
                                            <option value="">Select Enrollment</option>
                                            @foreach($enrollments as $enrollment)
                                                @php
                                                    $student = $enrollment->student;
                                                    $course = $enrollment->course;
                                                    $intake = $enrollment->intake;
                                                @endphp
                                                <option value="{{ $enrollment->id }}">
                                                    {{ $student->first_name }} {{ $student->last_name }} —
                                                    {{ $course->title }} —
                                                    Intake: {{ $intake->name ?? 'N/A' }}
                                                </option>
                                            @endforeach

                                        </select>
                                        @error('enrollment_id') <small
                                            class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                    <!-- Amount Input -->
                                    <div class="col-md-6 mb-3">
                                        <input type="number" wire:model="amount" class="form-control"
                                               placeholder="Amount"/>
                                        @error('amount') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                    <!-- Payment Method Selector -->
                                    <div class="col-md-6 mb-3">
                                        <select wire:model="method" class="form-control">
                                            <option value="">Select Payment Method</option>
                                            <option value="cash">Cash</option>
                                            <option value="mpesa">M-Pesa</option>
                                            <option value="card">Card</option>
                                            <option value="bank">Bank</option>
                                        </select>
                                        @error('method') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                    <!-- Reference Input -->
                                    <div class="col-md-6 mb-3">
                                        <input type="text" wire:model="reference" class="form-control"
                                               placeholder="Reference"/>
                                    </div>
                                    <!-- Paid Date Input -->
                                    <div class="col-md-6 mb-3">
                                        <input type="date" wire:model="paid_at" class="form-control"/>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="d-flex gap-1 m-0">
                                    <button type="submit" class="btn btn-success">
                                        {{ $editId ? 'Save' : 'Add' }}
                                    </button>
                                    <button type="button" class="btn bg-danger-subtle text-danger"
                                            data-bs-dismiss="modal">Discard
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="card card-body">
                <div class="table-responsive">
                    <table class="table search-table align-middle text-nowrap">
                        <thead class="header-item">
                        <tr>
                            <th>
                                <div class="form-check text-center">
                                    <input wire:click="$dispatch('select-all')"
                                           type="checkbox"
                                           class="form-check-input"
                                           wire:model="selectAll"/>
                                </div>
                            </th>
                            <th>Course</th>
                            <th>Student</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Paid On</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="text-center">
                                    <div class="form-check text-center">
                                        <input type="checkbox" class="form-check-input" wire:model="selected"
                                               value="{{ (string) $payment->id }}"/>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark">{{ $payment->course->title }}</span></td>
                                <!-- Assuming `course` is a property of $payment -->
                                <td>
                                    <div class="user-meta-info">
                                        <h6 class="user-name mb-0">{{ $payment->student->first_name }}</h6>
                                        <!-- Assuming `payer_name` is a property -->
                                        <span class="user-work fs-3">{{ $payment->last_name }}</span>
                                        <!-- Assuming `payer_reference` -->
                                    </div>
                                </td>
                                <td><span class="">{{ $payment->reference }}</span></td>
                                <td><span class="badge bg-secondary">{{ $payment->amount }}</span></td>
                                <td><span class="badge bg-secondary">{{ $payment->method }}</span></td>
                                <td>{{ $payment->paid_at }}</td>
                                <td>
                                    <div class="action-btn">
                                        <!-- Edit Payment Button -->
                                        <a href="javascript:void(0)" wire:click="editPayment({{ $payment->id }})"
                                           class="text-primary">
                                            <i class="ti ti-pencil fs-5"></i>
                                        </a>
                                        <!-- Delete Payment Button -->
                                        <a href="javascript:void(0)" wire:click="deletePayment({{ $payment->id }})"
                                           class="text-dark ms-2">
                                            <i class="ti ti-trash fs-5"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No payments found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                    {{-- Add the pagination links here --}}
                    <div class="d-flex justify-content-center mt-4">
                        {{ $payments->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
    <script>
        window.addEventListener('show-payment-modal', () => {
            new bootstrap.Modal(document.getElementById('addPaymentModal')).show();
        });

        window.addEventListener('hide-payment-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('addPaymentModal'))?.hide();
        });
    </script>
@endpush




