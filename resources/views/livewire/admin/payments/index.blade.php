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

new class extends Component {
    use WithPagination;

    public $selectAll = false;
    public $amount, $payment_method, $payment_reason, $reference, $paid_at, $enrollment_id, $status, $payer;
    public $editId = null;
    public $selected = [];
    public $search = '';
    public $student_search = '';
    public $enrollments = [];

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
        $payments = Payment::with(['enrollment.student', 'enrollment.course']) // Eager load nested relationships for display
            ->when(!empty($this->search), function ($q) {
                $q->where(function ($query) {
                    $query
                        ->whereHas('enrollment.student', function ($query) {
                            $query
                                ->where('first_name', 'like', "%{$this->search}%")
                                ->orWhere('last_name', 'like', "%{$this->search}%")
                                ->orWhere('email', 'like', "%{$this->search}%");
                        })
                        ->orWhere('payment_method', 'like', "%{$this->search}%")
                        ->orWhere('reference', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate($this->perPage); // Use paginate() instead of get(), specify items per page

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
                                                                $student->first_name .
                                                                ' ' .
                                                                $student->last_name .
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
                                                                    open = false;" style="cursor: pointer;">
                                                            <strong>{{ $student->first_name }}
                                                                {{ $student->last_name }}</strong><br>
                                                            <span class="text-muted">{{ $course?->title }} — Intake:
                                                                {{ $intake->name ?? 'N/A' }}</span>
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
                                <th>Course/Ref</th>
                                <th>Amount</th>
                                <th>Status</th>
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
    </script>
@endpush
