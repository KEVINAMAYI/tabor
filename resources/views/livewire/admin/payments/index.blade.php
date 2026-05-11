<?php

use App\Exports\PaymentExport;
use App\Models\Payment;
use App\Models\Enrollment;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\StudentFeeItem;
use App\Models\PaymentAllocation;
use App\Services\Finance\PaymentPostingService;

new class extends Component {
    use WithPagination;

    public $selectAll = false;
    public $amount, $payment_method, $payment_reason, $reference, $paid_at, $enrollment_id, $status, $payer;
    public $editId = null;
    public $selected = [];
    public $search = '';
    public $student_search = '';
    public $enrollments = [];

    public $selectedPayment = null;
    public $allocationRows = [];
    public $unallocatedAmount = 0;

    public $manual_fee_item_id = '';
    public $manual_allocation_amount = '';
    public $availableFeeItems = [];

    public $perPage = 10;

    public function rules()
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required',
            'payment_reason' => 'required',
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

    #[On('perform-search')]
    public function searchEnrollments($query)
    {
        $this->student_search = $query;

        $this->enrollments = Enrollment::with(['student', 'course', 'intake'])
            ->whereHas('student', function ($q) use ($query) {
                $q->where('first_name', 'like', '%' . $query . '%')
                    ->orWhere('last_name', 'like', '%' . $query . '%')
                    ->orWhere('admission_number', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%');
            })
            ->orWhereHas('course', function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%');
            })
            ->limit(10)
            ->get();
    }

    public function selectEnrollment($id)
    {
        $this->enrollment_id = $id;

        $enrollment = Enrollment::with(['student', 'course', 'intake'])->findOrFail($id);

        if ($enrollment) {
            $this->student_search = $enrollment->student->first_name . ' ' . $enrollment->student->last_name . ' — ' . $enrollment->course->title . ' (Intake: ' . ($enrollment->intake->name ?? 'N/A') . ')';
        }
    }

    public function with()
    {
        $payments = Payment::with(['enrollment.student', 'enrollment.course', 'allocations.studentFeeItem'])
            ->when(!empty($this->search), function ($q) {
                $q->where(function ($query) {
                    $query
                        ->whereHas('enrollment.student', function ($query) {
                            $query
                                ->where('first_name', 'like', "%{$this->search}%")
                                ->orWhere('last_name', 'like', "%{$this->search}%")
                                ->orWhere('email', 'like', "%{$this->search}%")
                                ->orWhere('admission_number', 'like', "%{$this->search}%");
                        })
                        ->orWhere('method', 'like', "%{$this->search}%")
                        ->orWhere('payment_method', 'like', "%{$this->search}%")
                        ->orWhere('reference', 'like', "%{$this->search}%")
                        ->orWhere('receipt_no', 'like', "%{$this->search}%")
                        ->orWhere('transaction_id', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate($this->perPage);

        return [
            'payments' => $payments,
        ];
    }

    public function viewAllocation(int $paymentId): void
    {
        $payment = Payment::with(['enrollment.student', 'enrollment.course', 'allocations.studentFeeItem'])->findOrFail($paymentId);

        $allocated = $payment->allocations->sum('amount_allocated');

        $this->selectedPayment = [
            'id' => $payment->id,
            'receipt_no' => $payment->receipt_no ?? ($payment->transaction_id ?? 'N/A'),
            'reference_no' => $payment->reference_no ?? ($payment->reference ?? 'N/A'),
            'amount' => (float) $payment->amount,
            'method' => $payment->method ?? ($payment->payment_method ?? 'N/A'),
            'payment_date' => optional($payment->payment_date ?? $payment->paid_at)->format('d M Y h:i A'),
            'student' => trim(($payment->enrollment?->student?->first_name ?? '') . ' ' . ($payment->enrollment?->student?->last_name ?? '')) ?: 'N/A',
            'course' => $payment->enrollment?->course?->title ?? ($payment->enrollment?->course?->name ?? 'N/A'),
        ];

        $this->allocationRows = $payment->allocations
            ->map(function ($allocation) {
                return [
                    'fee_item_id' => $allocation->student_fee_item_id,
                    'description' => $allocation->studentFeeItem?->description ?? 'Fee Item',
                    'amount_allocated' => (float) $allocation->amount_allocated,
                    'fee_amount' => (float) ($allocation->studentFeeItem?->amount ?? 0),
                    'fee_paid' => (float) ($allocation->studentFeeItem?->amount_paid ?? 0),
                    'fee_balance' => (float) ($allocation->studentFeeItem?->balance ?? 0),
                ];
            })
            ->toArray();

        $this->unallocatedAmount = (float) $payment->amount - (float) $allocated;

        $this->loadAvailableFeeItems($payment);

        $this->dispatch('show-allocation-modal');
    }

    protected function loadAvailableFeeItems(Payment $payment): void
    {
        $query = StudentFeeItem::query()->where('student_id', $payment->student_id)->where('balance', '>', 0)->orderByRaw('CASE WHEN enrollment_id IS NULL THEN 0 ELSE 1 END')->orderBy('charge_date')->orderBy('id');

        if ($payment->enrollment_id) {
            $query->where(function ($q) use ($payment) {
                $q->whereNull('enrollment_id')->orWhere('enrollment_id', $payment->enrollment_id);
            });
        }

        $this->availableFeeItems = $query
            ->get()
            ->map(
                fn($item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'balance' => (float) $item->balance,
                    'charge_date' => optional($item->charge_date)->format('d M Y'),
                ],
            )
            ->toArray();
    }

    public function allocateManually(): void
    {
        $this->validate([
            'manual_fee_item_id' => ['required', 'exists:student_fee_items,id'],
            'manual_allocation_amount' => ['required', 'numeric', 'min:1'],
        ]);

        try {
            DB::transaction(function () {
                $payment = Payment::with('allocations')->findOrFail($this->selectedPayment['id']);
                $feeItem = StudentFeeItem::lockForUpdate()->findOrFail($this->manual_fee_item_id);

                $allocated = $payment->allocations()->sum('amount_allocated');
                $remainingPayment = (float) $payment->amount - (float) $allocated;

                if ($remainingPayment <= 0) {
                    throw new \Exception('This payment has no unallocated balance.');
                }

                if ((float) $feeItem->balance <= 0) {
                    throw new \Exception('The selected fee item has no outstanding balance.');
                }

                $amount = min((float) $this->manual_allocation_amount, $remainingPayment, (float) $feeItem->balance);

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'student_fee_item_id' => $feeItem->id,
                    'amount_allocated' => $amount,
                ]);

                $newPaid = (float) $feeItem->amount_paid + $amount;
                $newBalance = max(0, (float) $feeItem->amount - $newPaid);

                $feeItem->update([
                    'amount_paid' => $newPaid,
                    'balance' => $newBalance,
                    'status' => $newBalance <= 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'pending'),
                ]);
            });

            $paymentId = $this->selectedPayment['id'];
            $this->manual_fee_item_id = '';
            $this->manual_allocation_amount = '';

            $this->viewAllocation($paymentId);

            LivewireAlert::text('Payment allocated successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Manual payment allocation failed', [
                'payment_id' => $this->selectedPayment['id'] ?? null,
                'fee_item_id' => $this->manual_fee_item_id,
                'message' => $e->getMessage(),
            ]);

            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    /*
    public function autoAllocateSelectedPayment(): void
{
    try {
        $payment = Payment::findOrFail($this->selectedPayment['id']);

        app(PaymentPostingService::class)
            ->allocateExistingPayment($payment);

        $this->viewAllocation($payment->id);

        LivewireAlert::text('Payment auto-allocated successfully.')
            ->success()
            ->toast()
            ->position('top-end')
            ->show();

    } catch (\Throwable $e) {
        Log::error('Auto allocation failed', [
            'payment_id' => $this->selectedPayment['id'] ?? null,
            'message' => $e->getMessage(),
        ]);

        LivewireAlert::text('Auto allocation failed.')
            ->error()
            ->toast()
            ->position('top-end')
            ->show();
    }
} */

    public function addPayment()
    {
        $this->validate();

        try {
            DB::beginTransaction();
            Payment::create([
                'enrollment_id' => $this->enrollment_id,
                'amount' => $this->amount,
                'payment_method' => $this->payment_method,
                'payment_reason' => $this->payment_reason,
                'status' => 'completed',
                'reference' => $this->reference,
                'paid_at' => $this->paid_at,
                'payer' => $this->payer,
            ]);

            DB::commit();

            $this->resetForm();
            $this->resetPage();
            $this->dispatch('hide-payment-modal');

            LivewireAlert::text('Payment added successfully.!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding payment: ' . $e->getMessage());

            LivewireAlert::text('Failed to add payment.!')->error()->toast()->position('top-end')->show();
        }
    }

    public function editPayment($id)
    {
        $payment = Payment::findOrFail($id);

        $this->editId = $payment->id;
        $this->enrollment_id = $payment->enrollment_id;
        $this->amount = $payment->amount;
        $this->status = $payment->status;
        $this->payment_method = $payment->payment_method;
        $this->payment_reason = $payment->payment_reason;
        $this->reference = $payment->reference;
        $this->paid_at = $payment->paid_at;
        $this->payer = $payment->payer;

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
                'payment_method' => $this->payment_method,
                'payment_reason' => $this->payment_reason,
                'reference' => $this->reference,
                'status' => 'completed',
                'paid_at' => $this->paid_at,
                'payer' => $this->payer,
            ]);

            DB::commit();

            $this->resetForm();
            $this->resetPage();
            $this->dispatch('hide-payment-modal');

            LivewireAlert::text('Payment updated successfully.!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update payment: ' . $e->getMessage());
            LivewireAlert::text('Failed to update payment.!')->error()->toast()->position('top-end')->show();
        }
    }

    public function deletePayment($id)
    {
        Payment::findOrFail($id)->delete();
        $this->resetPage();

        LivewireAlert::text('Payment deleted successfully.!')->success()->toast()->position('top-end')->show();
    }

    public function deleteSelected()
    {
        Payment::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();

        LivewireAlert::text('Payments deleted successfully.!')->success()->toast()->position('top-end')->show();
    }

    private function resetForm()
    {
        $this->enrollment_id = $this->search = $this->amount = $this->payment_method = $this->payment_reason = $this->reference = $this->paid_at = null;
        $this->editId = null;
    }

    #[On('select-all')]
    public function selectAll()
    {
        if ($this->selectAll) {
            $currentPagePaymentIds = Payment::with(['enrollment.student', 'enrollment.course']) // Ensure the same query logic
                ->when(!empty($this->search), function ($q) {
                    $q->where(function ($query) {
                        $query
                            ->whereHas('enrollment.student', function ($query) {
                                $query
                                    ->where('first_name', 'like', "%{$this->search}%")
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
                ->map(fn($id) => (string) $id)
                ->toArray();

            $this->selected = $currentPagePaymentIds;
        } else {
            $this->selected = [];
        }
    }

    public function exportExcel()
    {
        return Excel::download(app(PaymentExport::class), 'payments.xlsx');
    }

    public function exportPdf()
    {
        $url = route('payments.export.pdf');
        return redirect()->to($url);
    }
}; ?>

@push('styles')
    <style>
        .pagination {
            margin-left: 10px;
        }

        .action-btn a {
            color: #446076;
            transition: color 0.2s ease;
        }

        .action-btn a:hover {
            color: #f69121;
        }

        .search-table tbody tr:hover {
            background-color: #fff6ee;
        }

        .form-check-input:checked {
            background-color: #f69121;
            border-color: #f69121;
        }

        .dropdown-results {
            border-radius: 8px;
            background-color: #fff;
            overflow-y: auto;
        }

        .dropdown-item:hover,
        .hover-bg:hover {
            background-color: #f8f9fa;
        }

        .dropdown-item:last-child {
            border-bottom: none;
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
                            <input wire:keyup.debounce.100ms="$dispatch('search')" type="text"
                                class="form-control product-search ps-5" placeholder="Search Payments..."
                                wire:model="search" />
                            <i
                                class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div
                        class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                        @if (count($selected) > 0)
                            <!-- Delete Selected Button -->
                            @can('delete-payments')
                                <div class="action-btn">
                                    <a href="javascript:void(0)" wire:click.prevent="deleteSelected"
                                        class="delete-multiple bg-danger-subtle btn me-2 text-danger">
                                        <i class="ti ti-trash me-1 fs-5"></i> Delete Selected
                                    </a>
                                </div>
                            @endcan
                        @endif
                        <!-- Add Payment Button -->
                        @can('create-payments')
                            <a href="javascript:void(0)" wire:click="$dispatch('show-payment-modal')"
                                class="btn btn-primary d-flex align-items-center">
                                <i class="ti ti-credit-card text-white me-1 fs-5"></i> Add Payment
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Add Payment Modal -->
            <div class="modal fade" id="addPaymentModal" tabindex="-1" role="dialog"
                aria-labelledby="addPaymentModalTitle" aria-hidden="true" wire:ignore.self>
                <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header d-flex align-items-center">
                            <h5 class="modal-title">Create Payment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form wire:submit.prevent="{{ $editId ? 'updatePayment' : 'addPayment' }}">
                            <div class="modal-body">
                                <div class="row">

                                    <!-- Enrollment Selector -->
                                    <div class="col-md-6 mb-3 position-relative" x-data="{ open: false, studentSearch: @entangle('student_search').defer }"
                                        @click.away="open = false">


                                        <label for="search" class="form-label">Student</label>

                                        <input type="text" id="student_search" class="form-control"
                                            autocomplete="off"
                                            placeholder="Search student name, id, phone, course, or intake..."
                                            x-model="studentSearch"
                                            @input="$dispatch('perform-search', { query: studentSearch }); open = true"
                                            @focus="open = true" />


                                        <!-- Floating Dropdown -->
                                        @php
                                            $hasResults = count($enrollments) > 0;
                                            $dropdownClass = $hasResults
                                                ? 'border border-light shadow-lg'
                                                : 'border-0 shadow-none';
                                        @endphp

                                        <div x-show="open && studentSearch.length > 0" x-transition
                                            class="dropdown-results position-absolute bg-white rounded mt-1"
                                            :class="studentSearch.length > 0 && {{ count($enrollments) }} > 0 ?
                                                'border border-light shadow-lg' : 'border-0 shadow-none'"
                                            style="width: 95%; max-height: 220px; overflow-y: auto; z-index: 1050;">


                                            @if (!empty($student_search))
                                                @if (count($enrollments) > 0)
                                                    @foreach ($enrollments as $enrollment)
                                                        @php
                                                            $student = $enrollment->student;
                                                            $course = $enrollment->course;
                                                            $intake = $enrollment->intake;
                                                            $displayText =
                                                                $student?->first_name .
                                                                ' ' .
                                                                $student?->last_name .
                                                                ' — ' .
                                                                $course?->title .
                                                                ' (Intake: ' .
                                                                ($intake->name ?? 'N/A') .
                                                                ')';
                                                        @endphp
                                                        <div class="dropdown-item px-3 py-2 border-bottom small hover-bg"
                                                            @click="
                                                                    $wire.selectEnrollment({{ $enrollment->id }});
                                                                    studentSearch = '{{ $displayText }}';
                                                                    open = false;"
                                                            style="cursor: pointer;">
                                                            <strong>{{ $student?->first_name }}
                                                                {{ $student?->last_name }}</strong><br>
                                                            <span class="text-muted">{{ $course?->title }} — Intake:
                                                                {{ $intake?->name ?? 'N/A' }}</span>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="px-3 py-2 text-muted small">No results found</div>
                                                @endif
                                            @endif
                                        </div>

                                        </select>
                                        @error('enrollment_id')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <!-- Amount Input -->
                                    <div class="col-md-6 mb-3">
                                        <label for="amount" class="form-label">Amount</label>
                                        <input type="number" wire:model="amount" class="form-control"
                                            placeholder="Amount" />
                                        @error('amount')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <!-- Payment Method Selector -->
                                    <div class="col-md-6 mb-3">
                                        <label for="method" class="form-label">Payment Method</label>
                                        <select wire:model="payment_method" class="form-control">
                                            <option value="">Select Payment Method</option>
                                            <option value="mpesa">M-Pesa</option>
                                            <option value="bank">Bank</option>
                                            @can('give-discounts')
                                                <option value="discount">Discount</option>
                                            @endcan
                                        </select>
                                        @error('method')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <!-- Payment Method Selector -->
                                    <div class="col-md-6 mb-3">
                                        <label for="method" class="form-label">Payment For</label>
                                        <select wire:model="payment_reason" class="form-control">
                                            <option value="">Select Reason</option>
                                            <option value="tuition">Tuition</option>
                                            <option value="exam">Exam</option>
                                            <option value="attachment">Industrial Attachment</option>
                                            <option value="other">Other</option>
                                        </select>
                                        @error('payment_reason')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <!-- Reference Input -->
                                    {{-- <div class="col-md-6 mb-3">
                                        <label for="reference" class="form-label">Reference</label>
                                        <input type="text" wire:model="reference" class="form-control"
                                            placeholder="Reference" />
                                    </div> --}}
                                    <!-- Paid Date Input -->
                                    <div class="col-md-6 mb-3">
                                        <label for="paid_at" class="form-label">Paid On</label>
                                        <input type="date" wire:model="paid_at" class="form-control" />
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="payer" class="form-label">Narration/Comments</label>
                                        <textarea wire:model="payer" class="form-control" placeholder="Narration/Comments"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="d-flex gap-1 m-0">
                                    <button type="submit" class="btn btn-success" wire:loading.attr="disabled">
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

            <!--Allocation Modal -->
            <div class="modal fade" id="allocationModal" tabindex="-1" wire:ignore.self>
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-semibold">Payment Allocation</h5>
                                <small class="text-muted">See how this payment was distributed across fee
                                    items.</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            @if ($selectedPayment)
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <div class="border rounded-3 p-3">
                                            <div class="small text-muted">Receipt</div>
                                            <div class="fw-semibold">{{ $selectedPayment['receipt_no'] }}</div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="border rounded-3 p-3">
                                            <div class="small text-muted">Amount</div>
                                            <div class="fw-semibold text-primary">
                                                KES {{ number_format($selectedPayment['amount'], 2) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="border rounded-3 p-3">
                                            <div class="small text-muted">Allocated</div>
                                            <div class="fw-semibold text-success">
                                                KES
                                                {{ number_format(collect($allocationRows)->sum('amount_allocated'), 2) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="border rounded-3 p-3">
                                            <div class="small text-muted">Unallocated</div>
                                            <div
                                                class="fw-semibold {{ $unallocatedAmount > 0 ? 'text-danger' : 'text-muted' }}">
                                                KES {{ number_format($unallocatedAmount, 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="small text-muted">Student / Course</div>
                                    <div class="fw-semibold">
                                        {{ $selectedPayment['student'] }} — {{ $selectedPayment['course'] }}
                                    </div>
                                    <div class="small text-muted">
                                        {{ $selectedPayment['method'] }} • {{ $selectedPayment['payment_date'] }}
                                    </div>
                                </div>

                                <h6 class="fw-semibold mb-3">Allocation Breakdown</h6>

                                <div class="table-responsive mb-4">
                                    <table class="table align-middle">
                                        <thead>
                                            <tr>
                                                <th>Fee Item</th>
                                                <th class="text-end">Allocated</th>
                                                <th class="text-end">Fee Amount</th>
                                                <th class="text-end">Fee Paid</th>
                                                <th class="text-end">Fee Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($allocationRows as $row)
                                                <tr>
                                                    <td>{{ $row['description'] }}</td>
                                                    <td class="text-end text-success">
                                                        KES {{ number_format($row['amount_allocated'], 2) }}
                                                    </td>
                                                    <td class="text-end">
                                                        KES {{ number_format($row['fee_amount'], 2) }}
                                                    </td>
                                                    <td class="text-end">
                                                        KES {{ number_format($row['fee_paid'], 2) }}
                                                    </td>
                                                    <td class="text-end">
                                                        KES {{ number_format($row['fee_balance'], 2) }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        This payment has not been allocated yet.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                @if ($unallocatedAmount > 0)
                                    <div class="border rounded-3 p-3 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h6 class="fw-semibold mb-1">Allocate Unallocated Balance</h6>
                                                <p class="small text-muted mb-0">
                                                    Best option: auto-allocate first using the finance allocation rules.
                                                    Use manual allocation only for corrections.
                                                </p>
                                            </div>

                                            <button type="button" class="btn btn-primary btn-sm"
                                                wire:click="autoAllocateSelectedPayment" wire:loading.attr="disabled">
                                                Auto Allocate
                                            </button>
                                        </div>

                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-7">
                                                <label class="form-label">Outstanding Fee Item</label>
                                                <select class="form-select" wire:model="manual_fee_item_id">
                                                    <option value="">Select fee item</option>
                                                    @foreach ($availableFeeItems as $item)
                                                        <option value="{{ $item['id'] }}">
                                                            {{ $item['description'] }} — Balance KES
                                                            {{ number_format($item['balance'], 2) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('manual_fee_item_id')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Amount</label>
                                                <input type="number" step="0.01" class="form-control"
                                                    wire:model="manual_allocation_amount"
                                                    placeholder="{{ number_format($unallocatedAmount, 2, '.', '') }}">
                                                @error('manual_allocation_amount')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-success w-100"
                                                    wire:click="allocateManually" wire:loading.attr="disabled">
                                                    Allocate
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="card card-body">
                <div class="table-responsive">

                    <!-- Top Bar Inside the Card -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 px-2">
                        <div class="d-flex align-items-center">
                            <label for="perPage" class="form-label me-2">Show</label>
                            <select wire:model.live="perPage" id="perPage" class="form-select form-select-sm">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="ms-2">entries</span>
                        </div>
                        <!-- Title -->
                        <h6 class="mb-0 fw-semibold text-primary d-flex align-items-center">
                            <iconify-icon icon="mdi:wallet-outline" class="me-2"
                                style="font-size: 20px;"></iconify-icon>

                            Payments List
                        </h6>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 flex-wrap">

                            <!-- Export Excel Button -->
                            <button wire:click="exportExcel"
                                class="btn btn-outline-success btn-sm d-flex align-items-center px-3 py-1 rounded">
                                <iconify-icon icon="mdi:file-excel-outline" class="me-1"
                                    style="font-size: 18px;"></iconify-icon>
                                Excel
                            </button>

                            <!-- Export PDF Button -->
                            <button wire:click="exportPdf"
                                class="btn btn-outline-danger btn-sm d-flex align-items-center px-3 py-1 rounded">
                                <iconify-icon icon="mdi:file-pdf-box" class="me-1"
                                    style="font-size: 18px;"></iconify-icon>
                                PDF
                            </button>
                        </div>
                    </div>

                    <table class="table search-table align-middle text-nowrap">
                        <thead class="header-item">
                            <tr>
                                <th>
                                    <div class="form-check text-center">
                                        <input wire:click="$dispatch('select-all')" type="checkbox"
                                            class="form-check-input" wire:model="selectAll" />
                                    </div>
                                </th>
                                <th>#</th>
                                <th>Trans ID</th>
                                <th>Student</th>
                                <th>Student ID</th>
                                <th>Course/Ref</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Allocation Status</th>
                                <th>Method</th>
                                <th>Payment For</th>
                                <th>Paid On</th>
                                <th>Narration</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payments as $payment)
                                <tr class="search-items">
                                    <td class="text-center">
                                        <div class="form-check text-center">
                                            <input type="checkbox" class="form-check-input" wire:model="selected"
                                                value="{{ (string) $payment->id }}" />
                                        </div>
                                    </td>
                                    <td class="text-muted">{{ $loop->iteration }}</td>
                                    <td>
                                        <span
                                            class="badge bg-light text-dark">{{ $payment->transaction_id ?? 'N/A' }}</span>
                                    </td>
                                    @if (!empty($payment->enrollment))
                                        <td style="color: #446076; font-weight: 500;">
                                            <a href="{{ route('students.view', $payment->enrollment->student->id) }}">
                                                {{ !empty($payment->enrollment)
                                                    ? $payment->enrollment->student->first_name . ' ' . $payment->enrollment->student->last_name
                                                    : 'N/A' }}
                                            </a>
                                        </td>
                                    @else
                                        <td style="color: #446076; font-weight: 500;">
                                            {{ !empty($payment->enrollment)
                                                ? $payment->enrollment->student->first_name . ' ' . $payment->enrollment->student->last_name
                                                : 'N/A' }}
                                        </td>
                                    @endif
                                    <td>
                                        <span
                                            class="badge bg-light text-dark">{{ 'TTI/' . $payment->enrollment?->student?->admission_number . '/' . $payment->enrollment?->course?->code . '/' . $payment->enrollment?->created_at->format('Y') }}</span>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-light text-dark">{{ !empty($payment->enrollment) ? $payment->enrollment->course->title . ' - ' . $payment->enrollment->course->level : 'N/A' }}</span>
                                    </td>
                                    <td style="color: #f69121; font-weight: 600;">
                                        {{ number_format($payment->amount, 2) }}
                                    </td>
                                    <td>
                                        @if ($payment->status === 'completed')
                                            <span
                                                style="background-color: #e6f4ea; color: #28a745; padding: 4px 8px; border-radius: 4px;">
                                                Mapped
                                            </span>
                                        @elseif($payment->status === 'pending')
                                            <span
                                                style="background-color: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px;">
                                                Pending
                                            </span>
                                        @endif
                                    </td>
                                    @php
                                        $allocated = $payment->allocations->sum('amount_allocated');
                                        $unallocated = $payment->amount - $allocated;
                                    @endphp
                                    <td>
                                        @if ($unallocated <= 0)
                                            <span class="badge bg-success-subtle text-success">Fully Allocated</span>
                                        @elseif($allocated > 0)
                                            <span class="badge bg-warning-subtle text-warning">Partial</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Unallocated</span>
                                        @endif
                                    </td>

                                    <td><strong>{{ ucfirst($payment->payment_method) }}</strong></td>
                                    <td><strong>{{ ucfirst($payment->payment_reason) }}</strong></td>
                                    <td><strong>{{ \Carbon\Carbon::parse($payment->paid_at)->format('d/m/y h:i A') }}</strong>
                                    </td>
                                    <td>{{ $payment->payer }}</td>
                                    <td>
                                        <div class="action-btn">
                                            <a href="javascript:void(0)"
                                                wire:click="editPayment({{ $payment->id }})" style="color: #446076;"
                                                title="Edit">
                                                <i class="ti ti-pencil fs-5"></i>
                                            </a>
                                            <a href="javascript:void(0)"
                                                wire:click="viewAllocation({{ $payment->id }})"
                                                style="color: #28a745; margin-left: 10px;" title="View Allocation">
                                                <i class="ti ti-list-details fs-5"></i>
                                            </a>
                                            <a href="javascript:void(0)"
                                                onclick="confirm('Are you sure you want to delete this payment? This action cannot be undone.') || event.stopImmediatePropagation()"
                                                wire:click="deletePayment({{ $payment->id }})"
                                                style="color: #f69121; margin-left: 10px;" title="Delete">
                                                <i class="ti ti-trash fs-5"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted">No payments found.</td>
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

        window.addEventListener('show-allocation-modal', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('allocationModal')).show();
        });

        window.addEventListener('hide-allocation-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('allocationModal'))?.hide();
        });
    </script>
@endpush
