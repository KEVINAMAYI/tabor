<?php

use App\Models\Student;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Trimester;
use Livewire\Volt\Component;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use App\Services\Finance\FeeGenerationService;
use App\Services\TrimesterAssignmentService;
use App\Services\Finance\PaymentPostingService;
use Carbon\Carbon;

new class extends Component {
    public Student $student;

    public string $activeTab = 'enrollments';
    public $selectedEnrollmentId = null;

    // Enroll modal
    public $course_id = '';
    public $admission_date = '';
    public $enrollment_status = 'active';

    // Edit modal
    public $editEnrollmentId = null;
    public $edit_course_id = '';
    public $edit_admission_date = '';
    public $edit_enrollment_status = 'active';

    // Payment modal
    public $payment_enrollment_id = null;
    public $payment_date = '';
    public $payment_amount = '';
    public $payment_method = '';
    public $payment_reference_no = '';
    public $payment_receipt_no = '';
    public $payment_notes = '';

    // Generate charges modal
    public $generateChargesEnrollmentId = null;

    // Statement modal
    public $statementEnrollmentId = null;

    public function rules()
    {
        return [
            'course_id' => ['nullable', 'exists:courses,id'],
            'admission_date' => ['nullable', 'date'],
            'enrollment_status' => ['nullable', 'in:active,completed,deferred,cancelled'],

            'edit_course_id' => ['nullable', 'exists:courses,id'],
            'edit_admission_date' => ['nullable', 'date'],
            'edit_enrollment_status' => ['nullable', 'in:active,completed,deferred,cancelled'],

            'payment_date' => ['nullable', 'date'],
            'payment_amount' => ['nullable', 'numeric', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payment_reference_no' => ['nullable', 'string', 'max:255'],
            'payment_receipt_no' => ['nullable', 'string', 'max:255'],
            'payment_notes' => ['nullable', 'string'],
        ];
    }

    public function mount($student_id)
    {
        $this->student = Student::findOrFail($student_id);
        $this->selectedEnrollmentId = $this->student->enrollments()->latest()->value('id');

        $this->admission_date = now()->toDateString();
        $this->payment_date = now()->toDateString();
    }

    public function with(): array
    {
        $student = $this->student->load(['enrollments.course', 'enrollments.intakeTrimester.academicYear', 'enrollments.assignedStartTrimester.academicYear', 'enrollments.feeItems', 'enrollments.payments']);

        $enrollments = $student->enrollments->sortByDesc('created_at')->values();

        $selectedEnrollment = $enrollments->firstWhere('id', $this->selectedEnrollmentId);

        if (!$selectedEnrollment && $enrollments->count()) {
            $selectedEnrollment = $enrollments->first();
            $this->selectedEnrollmentId = $selectedEnrollment->id;
        }

        $totalCharges = $selectedEnrollment?->feeItems?->sum('amount') ?? 0;
        $totalPaid = $selectedEnrollment?->payments?->sum('amount') ?? 0;
        $balance = $totalCharges - $totalPaid;

        $studentCharges = $student->enrollments->flatMap->feeItems->sum('amount');
        $studentPaid = $student->enrollments->flatMap->payments->sum('amount');
        $studentBalance = $studentCharges - $studentPaid;

        $activeEnrollments = $student->enrollments->where('status', 'active')->count();
        $completedEnrollments = $student->enrollments->where('status', 'completed')->count();

        $statementEnrollment = $this->statementEnrollmentId ? $enrollments->firstWhere('id', $this->statementEnrollmentId) : null;

        $courses = Course::query()->where('active', true)->orderBy('title')->get();

        return [
            'enrollments' => $enrollments,
            'selectedEnrollment' => $selectedEnrollment,
            'statementEnrollment' => $statementEnrollment,
            'courses' => $courses,
            'totalCharges' => $totalCharges,
            'totalPaid' => $totalPaid,
            'balance' => $balance,
            'studentCharges' => $studentCharges,
            'studentPaid' => $studentPaid,
            'studentBalance' => $studentBalance,
            'activeEnrollments' => $activeEnrollments,
            'completedEnrollments' => $completedEnrollments,
        ];
    }

    /* public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    } */

    public function selectEnrollment(int $enrollmentId): void
    {
        $this->selectedEnrollmentId = $enrollmentId;
    }

    public function openEnrollModal(): void
    {
        $this->resetEnrollForm();
        $this->dispatch('show-enroll-modal');
    }

    public function saveEnrollment(): void
    {
        $this->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'admission_date' => ['required', 'date'],
            'enrollment_status' => ['required', 'in:active,completed,deferred,cancelled'],
        ]);

        $course = Course::findOrFail($this->course_id);

        $assignment = app(TrimesterAssignmentService::class)->assign(Carbon::parse($this->admission_date), $course);

        $enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course_id,
            'admission_date' => $this->admission_date,
            'status' => $this->enrollment_status,
            'intake_trimester_id' => $assignment['intake_trimester_id'],
            'assigned_start_trimester_id' => $assignment['assigned_start_trimester_id'],
        ]);

        $this->selectedEnrollmentId = $enrollment->id;

        $this->resetEnrollForm();
        $this->dispatch('hide-enroll-modal');

        LivewireAlert::text('Enrollment created successfully.')->success()->toast()->position('top-end')->show();
    }

    public function openEditEnrollmentModal(int $enrollmentId): void
    {
        $enrollment = Enrollment::where('student_id', $this->student->id)->findOrFail($enrollmentId);

        $this->editEnrollmentId = $enrollment->id;
        $this->edit_course_id = $enrollment->course_id;
        $this->edit_admission_date = optional($enrollment->admission_date)->format('Y-m-d');
        $this->edit_enrollment_status = $enrollment->status;

        $this->dispatch('show-edit-enrollment-modal');
    }

    public function updateEnrollment(): void
    {
        $this->validate([
            'edit_course_id' => ['required', 'exists:courses,id'],
            'edit_admission_date' => ['required', 'date'],
            'edit_enrollment_status' => ['required', 'in:active,completed,deferred,cancelled'],
        ]);

        $enrollment = Enrollment::where('student_id', $this->student->id)->findOrFail($this->editEnrollmentId);
        $course = Course::findOrFail($this->edit_course_id);

        $assignment = app(TrimesterAssignmentService::class)->assign(Carbon::parse($this->edit_admission_date), $course);

        $enrollment->update([
            'course_id' => $this->edit_course_id,
            'admission_date' => $this->edit_admission_date,
            'status' => $this->edit_enrollment_status,
            'intake_trimester_id' => $assignment['intake_trimester_id'],
            'assigned_start_trimester_id' => $assignment['assigned_start_trimester_id'],
        ]);

        $this->selectedEnrollmentId = $enrollment->id;

        $this->resetEditForm();
        $this->dispatch('hide-edit-enrollment-modal');

        LivewireAlert::text('Enrollment updated successfully.')->success()->toast()->position('top-end')->show();
    }

    public function deleteEnrollment(int $enrollmentId): void
    {
        $enrollment = Enrollment::where('student_id', $this->student->id)->findOrFail($enrollmentId);
        $enrollment->delete();

        if ((int) $this->selectedEnrollmentId === (int) $enrollmentId) {
            $this->selectedEnrollmentId = $this->student->enrollments()->latest()->value('id');
        }

        LivewireAlert::text('Enrollment deleted successfully.')->success()->toast()->position('top-end')->show();
    }

    public function confirmGenerateCharges(int $enrollmentId): void
    {
        $this->generateChargesEnrollmentId = $enrollmentId;
        $this->dispatch('show-generate-charges-modal');
    }

    public function generateInitialCharges(): void
    {
        $enrollment = Enrollment::where('student_id', $this->student->id)->findOrFail($this->generateChargesEnrollmentId);

        app(FeeGenerationService::class)->generateInitialCharges($enrollment);

        $this->generateChargesEnrollmentId = null;
        $this->dispatch('hide-generate-charges-modal');

        LivewireAlert::text('Initial charges generated successfully.')->success()->toast()->position('top-end')->show();
    }

    public function openPaymentModal(int $enrollmentId): void
    {
        $this->resetPaymentForm();
        $this->payment_enrollment_id = $enrollmentId;
        $this->dispatch('show-payment-modal');
    }

    public function savePayment(): void
    {
        $this->validate([
            'payment_date' => ['required', 'date'],
            'payment_amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payment_reference_no' => ['nullable', 'string', 'max:255'],
            'payment_receipt_no' => ['nullable', 'string', 'max:255'],
            'payment_notes' => ['nullable', 'string'],
        ]);

        app(PaymentPostingService::class)->post([
            'student_id' => $this->student->id,
            'enrollment_id' => $this->payment_enrollment_id,
            'payment_date' => $this->payment_date,
            'amount' => $this->payment_amount,
            'method' => $this->payment_method,
            'reference_no' => $this->payment_reference_no,
            'receipt_no' => $this->payment_receipt_no,
            'notes' => $this->payment_notes,
        ]);

        $this->dispatch('hide-payment-modal');
        $this->resetPaymentForm();

        LivewireAlert::text('Payment posted successfully.')->success()->toast()->position('top-end')->show();
    }

    public function openStatementModal(int $enrollmentId): void
    {
        $this->statementEnrollmentId = $enrollmentId;
        $this->dispatch('show-statement-modal');
    }

    protected function resetEnrollForm(): void
    {
        $this->course_id = '';
        $this->admission_date = now()->toDateString();
        $this->enrollment_status = 'active';
    }

    protected function resetEditForm(): void
    {
        $this->editEnrollmentId = null;
        $this->edit_course_id = '';
        $this->edit_admission_date = '';
        $this->edit_enrollment_status = 'active';
    }

    protected function resetPaymentForm(): void
    {
        $this->payment_enrollment_id = null;
        $this->payment_date = now()->toDateString();
        $this->payment_amount = '';
        $this->payment_method = '';
        $this->payment_reference_no = '';
        $this->payment_receipt_no = '';
        $this->payment_notes = '';
    }
};

?>

@push('styles')
    <style>
        .student-hero-body {
            background: linear-gradient(180deg, #ffffff 0%, #fbfbff 100%);
        }

        .student-name {
            font-size: 2.2rem;
            font-weight: 700;
            color: #101828;
        }

        .student-hero-meta {
            color: #667085;
            font-size: 0.95rem;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .meta-dot {
            opacity: 0.5;
        }

        .student-summary-pill {
            border-radius: 999px;
            padding: 12px 18px;
            text-align: center;
            font-weight: 600;
        }

        .student-summary-label {
            font-size: 0.78rem;
            opacity: 0.9;
            margin-bottom: 2px;
        }

        .student-summary-value {
            font-size: 1rem;
            font-weight: 700;
        }

        .primary-pill {
            background: #ece9ff;
            color: #4f46e5;
        }

        .success-pill {
            background: #e8f7ec;
            color: #16a34a;
        }

        .danger-pill {
            background: #fde7ef;
            color: #ff5c8a;
        }

        .student-tabs-wrap {
            background: #eeecff;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            padding-top: 8px;
            padding-bottom: 0;
            margin-top: 32px;
        }

        .overview-metric-card {
            border-radius: 18px;
            background: #fff;
        }

        .overview-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #98a2b3;
            margin-bottom: 10px;
        }

        .overview-value {
            font-size: 2rem;
            font-weight: 700;
            color: #101828;
            line-height: 1.1;
        }

        @media (max-width: 991.98px) {
            .student-name {
                font-size: 1.7rem;
            }

            .student-summary-pill {
                border-radius: 18px;
            }
        }

        .enrollment-select-card {
            border: 1px solid #e9ecef;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 14px;
            background: #ffffff;
            transition: all 0.2s ease;
        }

        .enrollment-select-card:hover {
            border-color: #c7d2fe;
            background: #f8faff;
        }

        .enrollment-select-card.active {
            background: #eef2ff;
            border-color: #4f46e5;
            box-shadow: 0 0 0 1px rgba(79, 70, 229, 0.15);
        }

        .enrollment-select-card.active h6,
        .enrollment-select-card.active .small,
        .enrollment-select-card.active .text-muted {
            color: #312e81 !important;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .btn-icon-action {
            border: none;
            background: transparent;
            padding: 0.35rem 0.5rem;
            border-radius: 8px;
        }

        .btn-icon-action:hover {
            background: #f3f4f6;
        }
    </style>
@endpush

<div class="student-show-page">
    {{-- Top summary bar --}}
    <div class="card border-0 shadow-sm student-topbar mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <a href="{{ route('students.index') }}" class="btn btn-secondary rounded-3 px-3">
                        <i class="ti ti-arrow-left me-1"></i> Back
                    </a>
                </div>
                <div>
                    <h4 class="mb-4 mb-sm-0 card-title">
                        {{ $student->first_name . ' ' . $student->last_name }}
                    </h4>
                </div>
                <div>
                    <h4 class="mb-4 mb-sm-0 card-title">
                        {{ 'TTI/' . $student->admission_number . '/' . $student->created_at->format('Y') }}
                    </h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Main student summary card --}}
    <div class="card border-0 shadow-sm student-hero-card mb-4">
        <div class="card-body p-0">
            <div class="student-hero-body px-4 py-5">

                <div class="row g-3 mb-4 justify-content-center">
                    <div class="col-lg-3 col-md-6">
                        <div class="student-summary-pill primary-pill">
                            <div class="student-summary-label">Total Charges</div>
                            <div class="student-summary-value">KES {{ number_format($studentCharges, 2) }}</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="student-summary-pill success-pill">
                            <div class="student-summary-label">Total Paid</div>
                            <div class="student-summary-value">KES {{ number_format($studentPaid, 2) }}</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="student-summary-pill danger-pill">
                            <div class="student-summary-label">Balance</div>
                            <div class="student-summary-value">KES {{ number_format($studentBalance, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            
            <ul class="nav nav-pills user-profile-tab justify-content-start mt-2 bg-primary-subtle rounded-2 rounded-top-0"
                id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link hstack gap-2 rounded-0 fs-12 py-6
    {{ $activeTab === 'enrollments' ? 'active' : '' }}"
                        wire:click="$set('activeTab', 'enrollments')" id="enrollments-tab" data-bs-toggle="pill"
                        data-bs-target="#enrollments" type="button" role="tab">
                        <i class="ti ti-book fs-5"></i>
                        Enrollments
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link hstack gap-2 rounded-0 fs-12 py-6
    {{ $activeTab === 'payments' ? 'active' : '' }}"
                        wire:click="$set('activeTab', 'payments')" id="payments-tab" data-bs-toggle="pill"
                        data-bs-target="#payments" type="button" role="tab">
                        <i class="ti ti-credit-card fs-5"></i>
                        Payments
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link hstack gap-2 rounded-0 fs-12 py-6
    {{ $activeTab === 'statements' ? 'active' : '' }}"
                        wire:click="$set('activeTab', 'statements')" id="statements-tab" data-bs-toggle="pill"
                        data-bs-target="#statements" type="button" role="tab">
                        <i class="ti ti-credit-card fs-5"></i>
                        Statements
                    </button>
                </li>
            </ul>
        </div>
    </div>

    @if ($activeTab === 'enrollments')
        <div class="row g-4 mt-1">
            {{-- LEFT: ENROLLMENTS LIST --}}
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm student-enrollment-panel h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="mb-1 fw-semibold">Total Enrollments</h5>
                                <span
                                    class="badge rounded-circle bg-primary-subtle text-primary fs-6">{{ $enrollments->count() }}</span>
                            </div>

                            <button type="button" class="btn btn-primary btn-sm rounded-3 px-3"
                                wire:click="openEnrollModal">
                                <i class="ti ti-plus me-1"></i> Enroll
                            </button>
                        </div>
                        <hr>

                        <div>
                            @forelse($enrollments as $enrollment)
                                @php
                                    $charges = $enrollment->feeItems->sum('amount');
                                    $paid = $enrollment->payments->sum('amount');
                                    $itemBalance = $charges - $paid;

                                    $statusClasses = match ($enrollment->status) {
                                        'approved' => 'bg-primary-subtle text-primary',
                                        'completed' => 'bg-success-subtle text-success',
                                        'deferred' => 'bg-warning-subtle text-warning',
                                        'cancelled' => 'bg-danger-subtle text-danger',
                                        default => 'bg-light text-muted',
                                    };
                                @endphp

                                <div
                                    class="enrollment-select-card {{ (int) $selectedEnrollmentId === (int) $enrollment->id ? 'active' : '' }}">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="pe-3 cursor-pointer flex-grow-1"
                                            wire:click="selectEnrollment({{ $enrollment->id }})">
                                            <h6 class="mb-1 fw-semibold text-dark">
                                                {{ $enrollment->course->title . ' - ' . $enrollment->course->level }}
                                            </h6>

                                            <div class="small text-muted">
                                                {{ ucfirst($enrollment->course->course_type ?? '—') }} course
                                            </div>
                                        </div>

                                        <span class="badge {{ $statusClasses }}">
                                            {{ $enrollment->status == 'approved' ? 'Active' : ucfirst($enrollment->status) }}
                                        </span>
                                    </div>

                                    <div class="row g-2 small mt-1 cursor-pointer"
                                        wire:click="selectEnrollment({{ $enrollment->id }})">


                                        <div class="col-12">
                                            <span class="text-muted">Balance:</span>
                                            <span class="fw-semibold text-danger">
                                                KES {{ number_format($itemBalance, 2) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                        <div class="small text-muted cursor-pointer"
                                            wire:click="selectEnrollment({{ $enrollment->id }})">
                                            Admitted
                                            {{ optional($enrollment->admission_date)->format('d M Y') ?? '—' }}
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-icon-action text-primary"
                                                wire:click="selectEnrollment({{ $enrollment->id }})" title="View">
                                                <i class="ti ti-eye"></i>
                                            </button>

                                            <button type="button" class="btn btn-icon-action text-warning"
                                                wire:click="openEditEnrollmentModal({{ $enrollment->id }})"
                                                title="Edit">
                                                <i class="ti ti-pencil"></i>
                                            </button>

                                            <button type="button" class="btn btn-icon-action text-danger"
                                                wire:click="deleteEnrollment({{ $enrollment->id }})"
                                                wire:confirm="Are you sure you want to delete this enrollment?"
                                                title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="empty-enrollment-state text-center py-5">
                                    <div class="mb-3">
                                        <i class="ti ti-school fs-1 text-muted"></i>
                                    </div>
                                    <h6 class="fw-semibold">No enrollments yet</h6>
                                    <p class="text-muted small mb-3">
                                        This student has not been enrolled in any course.
                                    </p>
                                    <button type="button" class="btn btn-primary btn-sm rounded-3 px-3"
                                        wire:click="openEnrollModal">
                                        <i class="ti ti-plus me-1"></i> Enroll in Course
                                    </button>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: SELECTED ENROLLMENT DETAILS --}}
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm student-enrollment-panel h-100">
                    <div class="card-body p-4">
                        @if ($selectedEnrollment)
                            @php
                                $selectedStatusClasses = match ($selectedEnrollment->status) {
                                    'approved' => 'bg-primary-subtle text-primary',
                                    'completed' => 'bg-success-subtle text-success',
                                    'deferred' => 'bg-warning-subtle text-warning',
                                    'cancelled' => 'bg-danger-subtle text-danger',
                                    default => 'bg-light text-muted',
                                };
                            @endphp

                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                                <div>
                                    <div class="text-muted small mb-1">Selected Enrollment</div>
                                    <h4 class="mb-1 fw-semibold">
                                        {{ $selectedEnrollment->course->title . ' - ' . $selectedEnrollment->course->level }}
                                    </h4>
                                    <div class="text-muted">
                                        {{ ucfirst($selectedEnrollment->course->course_type ?? '—') }} course
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge {{ $selectedStatusClasses }}">
                                        {{ $selectedEnrollment->status == 'approved' ? 'Active' : ucfirst($selectedEnrollment->status) }}
                                    </span>

                                    <button type="button" class="btn btn-light btn-sm rounded-3"
                                        wire:click="openEditEnrollmentModal({{ $selectedEnrollment->id }})">
                                        <i class="ti ti-pencil me-1"></i> Edit
                                    </button>
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <div class="detail-label">Admission Date</div>
                                    <div class="detail-value">
                                        {{ optional($selectedEnrollment->admission_date)->format('d M Y') ?? '—' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="detail-label">Duration</div>
                                    <div class="detail-value">
                                        {{ $selectedEnrollment->course->number_of_trimesters ?? 0 }} trimester(s)
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="detail-label">Intake Trimester</div>
                                    <div class="detail-value">
                                        {{ $selectedEnrollment->intakeTrimester->name ?? '—' }}
                                        {{ $selectedEnrollment->intakeTrimester?->academicYear?->name }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="detail-label">Assigned Start Trimester</div>
                                    <div class="detail-value">
                                        {{ $selectedEnrollment->assignedStartTrimester->name ?? '—' }}
                                        {{ $selectedEnrollment->assignedStartTrimester?->academicYear?->name }}
                                    </div>
                                </div>
                            </div>

                            <div class="finance-summary-box mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 fw-semibold">Finance Snapshot</h6>
                                    <span class="text-muted small">Enrollment-linked totals</span>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="finance-metric-card">
                                            <div class="finance-metric-label">Total Charges</div>
                                            <div class="finance-metric-value">
                                                KES {{ number_format($totalCharges, 2) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="finance-metric-card">
                                            <div class="finance-metric-label">Total Paid</div>
                                            <div class="finance-metric-value text-success">
                                                KES {{ number_format($totalPaid, 2) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="finance-metric-card">
                                            <div class="finance-metric-label">Balance</div>
                                            <div class="finance-metric-value text-danger">
                                                KES {{ number_format($balance, 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-primary w-100 rounded-3"
                                        wire:click="confirmGenerateCharges({{ $selectedEnrollment->id }})">
                                        <i class="ti ti-receipt me-1"></i> Generate Charges
                                    </button>
                                </div>

                                <div class="col-md-4">
                                    <button type="button" class="btn btn-outline-primary w-100 rounded-3"
                                        wire:click="openPaymentModal({{ $selectedEnrollment->id }})">
                                        <i class="ti ti-cash me-1"></i> Post Payment
                                    </button>
                                </div>

                                <div class="col-md-4">
                                    <button type="button" class="btn btn-outline-secondary w-100 rounded-3"
                                        wire:click="openStatementModal({{ $selectedEnrollment->id }})">
                                        <i class="ti ti-printer me-1"></i> Statement
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="ti ti-layout-sidebar-right-expand fs-1 text-muted"></i>
                                </div>
                                <h5 class="fw-semibold">No enrollment selected</h5>
                                <p class="text-muted mb-0">
                                    Select an enrollment on the left to view details.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($activeTab === 'payments')
        <div class="card border-0 shadow-sm">
            <div class="card-body p-5 text-center">
                <i class="ti ti-credit-card fs-1 text-muted mb-3"></i>
                <h5 class="fw-semibold">Payments tab</h5>
                <p class="text-muted mb-0">This will show payment history and posting UI next.</p>
            </div>
        </div>
    @endif

    @if ($activeTab === 'statements')
        <div class="card border-0 shadow-sm">
            <div class="card-body p-5 text-center">
                <i class="ti ti-file-invoice fs-1 text-muted mb-3"></i>
                <h5 class="fw-semibold">Statements tab</h5>
                <p class="text-muted mb-0">This will show trimester-based statements next.</p>
            </div>
        </div>
    @endif

    {{-- enroll modal --}}
    <div wire:ignore.self class="modal fade" id="enrollModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">Add Enrollment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Course</label>
                        <select class="form-select" wire:model="course_id">
                            <option value="">Select course</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}">
                                    {{ $course->title }} - {{ $course->level }}
                                </option>
                            @endforeach
                        </select>
                        @error('course_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Admission Date</label>
                        <input type="date" class="form-control" wire:model="admission_date">
                        @error('admission_date')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" wire:model="enrollment_status">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="deferred">Deferred</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        @error('enrollment_status')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="saveEnrollment">
                        Save Enrollment
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- edit enrollment modal --}}
    <div wire:ignore.self class="modal fade" id="editEnrollmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Enrollment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Course</label>
                        <select class="form-select" wire:model="edit_course_id">
                            <option value="">Select course</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}">
                                    {{ $course->title }} - {{ $course->level }}
                                </option>
                            @endforeach
                        </select>
                        @error('edit_course_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Admission Date</label>
                        <input type="date" class="form-control" wire:model="edit_admission_date">
                        @error('edit_admission_date')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" wire:model="edit_enrollment_status">
                            <option value="approved">Active</option>
                            <option value="completed">Completed</option>
                            <option value="deferred">Deferred</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        @error('edit_enrollment_status')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="updateEnrollment">
                        Update Enrollment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function modalInstance(id) {
            const el = document.getElementById(id);
            if (!el) return null;
            return bootstrap.Modal.getOrCreateInstance(el);
        }

        window.addEventListener('show-enroll-modal', () => modalInstance('enrollModal')?.show());
        window.addEventListener('hide-enroll-modal', () => modalInstance('enrollModal')?.hide());

        window.addEventListener('show-edit-enrollment-modal', () => modalInstance('editEnrollmentModal')?.show());
        window.addEventListener('hide-edit-enrollment-modal', () => modalInstance('editEnrollmentModal')?.hide());

        window.addEventListener('show-generate-charges-modal', () => modalInstance('generateChargesModal')?.show());
        window.addEventListener('hide-generate-charges-modal', () => modalInstance('generateChargesModal')?.hide());

        window.addEventListener('show-payment-modal', () => modalInstance('paymentModal')?.show());
        window.addEventListener('hide-payment-modal', () => modalInstance('paymentModal')?.hide());

        window.addEventListener('show-statement-modal', () => modalInstance('statementModal')?.show());
        window.addEventListener('hide-statement-modal', () => modalInstance('statementModal')?.hide());
    </script>
@endpush
