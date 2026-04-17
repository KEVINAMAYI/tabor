<?php

use App\Models\Student;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Trimester;
use App\Models\PaymentAllocation;
use App\Models\StudentFeeItem;
use Livewire\Volt\Component;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use App\Services\FeeGenerationService;
use App\Services\TrimesterAssignmentService;
use App\Services\PaymentPostingService;
use App\Services\StudentStatementService;
use Carbon\Carbon;

new class extends Component {
    public Student $student;

    public string $activeTab = 'enrollments';
    public $selectedEnrollmentId = null;

    // Enroll modal
    public $course_id = '';
    public $admission_date = '';
    public $enrollment_status = 'approved';

    // Edit modal
    public $editEnrollmentId = null;
    public $edit_course_id = '';
    public $edit_admission_date = '';
    public $edit_enrollment_status = 'approved';

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
    public array $chargePreview = [];

    //statement items
    public $statement_trimester_id = '';
    public $statement_enrollment_id = '';
    public $statementData = null;

    public $statementEnrollmentId = null;

    public function rules()
    {
        return [
            'course_id' => ['nullable', 'exists:courses,id'],
            'admission_date' => ['nullable', 'date'],
            // 'enrollment_status' => ['nullable', 'in:approved,completed,deferred,cancelled'],

            'edit_course_id' => ['nullable', 'exists:courses,id'],
            'edit_admission_date' => ['nullable', 'date'],
            // 'edit_enrollment_status' => ['nullable', 'in:approved,completed,deferred,cancelled'],

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

        $totalCharges = StudentFeeItem::query()
            ->where('student_id', $student->id)
            ->where(function ($q) use ($selectedEnrollment) {
                $q->where('enrollment_id', $selectedEnrollment?->id)->orWhereNull('enrollment_id');
            })
            ->sum('amount');

        $totalPaid = PaymentAllocation::query()
            ->whereHas('studentFeeItem', function ($q) use ($student, $selectedEnrollment) {
                $q->where('student_id', $student->id)->where(function ($sub) use ($selectedEnrollment) {
                    $sub->where('enrollment_id', $selectedEnrollment?->id)->orWhereNull('enrollment_id');
                });
            })
            ->sum('amount_allocated');

        $balance = StudentFeeItem::query()
            ->where('student_id', $student->id)
            ->where(function ($q) use ($selectedEnrollment) {
                $q->where('enrollment_id', $selectedEnrollment?->id)->orWhereNull('enrollment_id');
            })
            ->sum('balance');

        $studentCharges = StudentFeeItem::query()->where('student_id', $student->id)->sum('amount');

        $studentBalance = StudentFeeItem::query()->where('student_id', $student->id)->sum('balance');

        $studentPaid = PaymentAllocation::query()
            ->whereHas('studentFeeItem', function ($q) use ($student) {
                $q->where('student_id', $student->id);
            })
            ->sum('amount_allocated');

        $activeEnrollments = $student->enrollments->where('status', 'approved')->count();
        $completedEnrollments = $student->enrollments->where('status', 'completed')->count();

        $statementEnrollment = $this->statementEnrollmentId ? $enrollments->firstWhere('id', $this->statementEnrollmentId) : null;

        $courses = Course::query()->where('active', true)->orderBy('title')->get();
        $trimesters = Trimester::orderBy('start_date')->get();

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
            'trimesters' => $trimesters,
        ];
    }

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
            // 'enrollment_status' => ['required', 'in:approved,completed,deferred,cancelled'],
        ]);

        DB::beginTransaction();

        try {
            $course = Course::findOrFail($this->course_id);

            $assignment = app(TrimesterAssignmentService::class)->assign(Carbon::parse($this->admission_date), $course);

            if (empty($assignment['assigned_start_trimester_id']) || empty($assignment['intake_trimester_id'])) {
                Log::error('Unable to assign trimester due to missing start trimester or intake trimester. Please check course setup and trimester setup.');
                LivewireAlert::text('Error assigning trimester. Please check course setup and trimester setup.')->error()->toast()->position('top-end')->show();
                return;
            }

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

            app(FeeGenerationService::class)->generateInitialCharges($enrollment);

            DB::commit();

            LivewireAlert::text('Enrollment created successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error creating enrollment: ' . $th->getMessage());
            LivewireAlert::text('Error creating enrollment: ' . $th->getMessage())
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
        }
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
            // 'edit_enrollment_status' => ['required', 'in:approved,completed,deferred,cancelled'],
        ]);

        try {
            DB::beginTransaction();

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

            app(FeeGenerationService::class)->generateInitialCharges($enrollment);

            DB::commit();

            LivewireAlert::text('Enrollment updated successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            DB::rollBack();

            LivewireAlert::text('Error updating enrollment: ' . $th->getMessage())
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
        }
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
        $enrollment = Enrollment::with('course')->findOrFail($enrollmentId);

        $this->generateChargesEnrollmentId = $enrollmentId;

        $this->chargePreview = app(FeeGenerationService::class)->previewInitialCharges($enrollment);

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
        $this->statement_enrollment_id = $enrollmentId;

        if (!$this->statement_trimester_id) {
            $enrollment = Enrollment::with('assignedStartTrimester')->find($enrollmentId);

            $this->statement_trimester_id = $enrollment?->assigned_start_trimester_id;
        }

        $this->loadStatement();
        $this->dispatch('show-statement-modal');
    }

    public function loadStatement(): void
    {
        if (!$this->statement_trimester_id) {
            $this->statementData = null;
            return;
        }

        $trimester = Trimester::findOrFail($this->statement_trimester_id);

        $this->statementData = app(StudentStatementService::class)->buildTrimesterStatement(student: $this->student, trimester: $trimester, enrollmentId: $this->statement_enrollment_id ?: null);
    }
    public function updatedStatementTrimesterId(): void
    {
        $this->loadStatement();
    }

    public function updatedStatementEnrollmentId(): void
    {
        $this->loadStatement();
    }

    protected function resetEnrollForm(): void
    {
        $this->course_id = '';
        $this->admission_date = now()->toDateString();
        $this->enrollment_status = 'approved';
    }

    protected function resetEditForm(): void
    {
        $this->editEnrollmentId = null;
        $this->edit_course_id = '';
        $this->edit_admission_date = '';
        $this->edit_enrollment_status = 'approved';
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

                                <div wire:click="selectEnrollment({{ $enrollment->id }})"
                                    class="enrollment-select-card {{ (int) $selectedEnrollmentId === (int) $enrollment->id ? 'active' : '' }}">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="pe-3 cursor-pointer flex-grow-1">
                                            <h6 class="mb-1 fw-semibold text-dark">
                                                {{ $enrollment->course->title . ' - ' . $enrollment->course->level }}
                                            </h6>
                                        </div>

                                        <span class="badge {{ $statusClasses }}">
                                            {{ $enrollment->status == 'approved' ? 'Active' : ucfirst($enrollment->status) }}
                                        </span>
                                    </div>

                                    <div class="row g-2 small mt-1 cursor-pointer">
                                        <div class="col-12 justify-content-between d-flex">
                                            <span class="text-muted">Enrollment ID:</span>
                                            <div>
                                                <span class="fw-semibold text-primary">TTI/</span>
                                                <span
                                                    class="fw-semibold text-danger">{{ $enrollment->student->admission_number . '/' . $enrollment->course->code . '/' }}</span>
                                                <span
                                                    class="fw-semibold text-primary">{{ $enrollment->created_at->format('Y') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-2 small mt-1 cursor-pointer">
                                        <div class="col-12 justify-content-between d-flex">
                                            <span class="text-muted">Balance:</span>
                                            <span class="fw-semibold text-danger">
                                                KES {{ number_format($itemBalance, 2) }}
                                            </span>
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
                            <hr>
                            <div class="finance-summary-box mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 fw-semibold">Finance Snapshot</h6>
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
                                    <a href="{{ route('students.statement', [
                                        'student' => $student->id,
                                        'trimester_id' => $selectedEnrollment->assigned_start_trimester_id,
                                        'enrollment_id' => $selectedEnrollment->id,
                                    ]) }}"
                                        target="_blank" class="btn btn-outline-secondary w-100 rounded-3">
                                        <i class="ti ti-printer me-1"></i> Statement
                                    </a>
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
                            <option value="approved">Active</option>
                            <option value="pending">Pending</option>
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
                            <option value="pending">Pending</option>
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

    {{-- GENERATE CHARGES MODAL --}}
    <div class="modal fade" id="generateChargesModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-semibold mb-1">Generate Initial Charges</h5>
                        <small class="text-muted">This will post starting charges for the selected enrollment.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    @if ($selectedEnrollment)
                        <div class="p-3 rounded-3 bg-light mb-3">
                            <div class="small text-muted mb-1">Enrollment</div>
                            <div class="fw-semibold text-dark">
                                {{ $selectedEnrollment->course->title }} - {{ $selectedEnrollment->course->level }}
                            </div>
                            <div class="small text-muted mt-1">
                                {{ optional($selectedEnrollment->admission_date)->format('d M Y') ?? '—' }}
                                •
                                {{ $selectedEnrollment->assignedStartTrimester->name ?? '—' }}
                                {{ $selectedEnrollment->assignedStartTrimester?->academicYear?->name }}
                            </div>
                        </div>
                    @endif

                    <div class="alert alert-warning border-0 rounded-3 mb-0">
                        <div class="fw-semibold mb-2">Charges to be generated</div>

                        @if (count($chargePreview))
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th>Scope</th>
                                            <th>Fee</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($chargePreview as $item)
                                            <tr>
                                                <td class="small text-muted">{{ $item['type'] }}</td>
                                                <td class="fw-medium">{{ $item['name'] }}</td>
                                                <td class="text-end fw-semibold">
                                                    KES {{ number_format($item['amount'], 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    <tfoot>
                                        <tr>
                                            <th colspan="2" class="text-end">Total</th>
                                            <th class="text-end">
                                                KES {{ number_format(collect($chargePreview)->sum('amount'), 2) }}
                                            </th>
                                        </tr>
                                    </tfoot>
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-muted small">
                                No new charges will be generated. All applicable fees already exist.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary rounded-3" wire:click="generateInitialCharges"
                        wire:loading.attr="disabled">

                        {{-- Normal state --}}
                        <span wire:loading.remove wire:target="generateInitialCharges">
                            <i class="ti ti-receipt me-1"></i> Generate Charges
                        </span>

                        {{-- Loading state --}}
                        <span wire:loading wire:target="generateInitialCharges">
                            <i class="ti ti-loader animate-spin me-1"></i> Processing...
                        </span>

                    </button>

                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- POST PAYMENT MODAL --}}
    <div class="modal fade" id="paymentModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-semibold mb-1">Post Payment</h5>
                        <small class="text-muted">Record a payment for this student and allocate it to outstanding
                            charges.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form wire:submit.prevent="savePayment">
                    <div class="modal-body">
                        @if ($selectedEnrollment)
                            <div class="p-3 rounded-3 bg-light mb-4">
                                <div class="fw-semibold text-dark">
                                    {{ $selectedEnrollment->course->title }} -
                                    {{ $selectedEnrollment->course->level }}
                                </div>
                                <div class="small text-muted mt-1">
                                    {{ $student->first_name }} {{ $student->last_name }}
                                    •
                                    {{ 'TTI/' . $student->admission_number . '/' . $selectedEnrollment->course->code . '/' . $student->created_at->format('Y') }}
                                </div>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Payment Date</label>
                                <input type="date" class="form-control rounded-3" wire:model="payment_date">
                                @error('payment_date')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Amount</label>
                                <input type="number" step="0.01" min="0" class="form-control rounded-3"
                                    wire:model="payment_amount" placeholder="0.00">
                                @error('payment_amount')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-medium">Method</label>
                                <select class="form-select rounded-3" wire:model="payment_method">
                                    <option value="">Select method</option>
                                    <option value="cash">Cash</option>
                                    <option value="mpesa">M-PESA</option>
                                    <option value="bank">Bank</option>
                                    <option value="card">Card</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('payment_method')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-medium">Reference No</label>
                                <input type="text" class="form-control rounded-3"
                                    wire:model="payment_reference_no" placeholder="Transaction reference">
                                @error('payment_reference_no')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-medium">Receipt No</label>
                                <input type="text" class="form-control rounded-3" wire:model="payment_receipt_no"
                                    placeholder="Receipt number">
                                @error('payment_receipt_no')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Notes</label>
                            <textarea class="form-control rounded-3" rows="3" wire:model="payment_notes" placeholder="Optional notes"></textarea>
                            @error('payment_notes')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="alert alert-info border-0 rounded-3 mb-0">
                            Payment will be allocated to the oldest outstanding fee items first.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary rounded-3">
                            <i class="ti ti-cash me-1"></i> Save Payment
                        </button>

                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Statement Modal --}}
    <div class="modal fade" id="statementModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-semibold mb-1">Student Trimester Statement</h5>
                        <small class="text-muted">Statement by trimester and enrollment context</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Trimester</label>
                            <select class="form-select rounded-3" wire:model="statement_trimester_id">
                                <option value="">Select trimester</option>
                                @foreach ($trimesters as $trimester)
                                    <option value="{{ $trimester->id }}">
                                        {{ $trimester->name }} {{ $trimester->academicYear?->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Enrollment Scope</label>
                            <select class="form-select rounded-3" wire:model="statement_enrollment_id">
                                <option value="">All student items</option>
                                @foreach ($enrollments as $enrollment)
                                    <option value="{{ $enrollment->id }}">
                                        {{ $enrollment->course->title ?? ($enrollment->course->name ?? 'Course') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($statementData)
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="finance-metric-card">
                                    <div class="finance-metric-label">Opening Balance</div>
                                    <div class="finance-metric-value">
                                        KES {{ number_format($statementData['opening_balance'], 2) }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="finance-metric-card">
                                    <div class="finance-metric-label">Charges This Trimester</div>
                                    <div class="finance-metric-value">
                                        KES {{ number_format($statementData['charge_total'], 2) }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="finance-metric-card">
                                    <div class="finance-metric-label">Closing Balance</div>
                                    <div class="finance-metric-value text-danger">
                                        KES {{ number_format($statementData['closing_balance'], 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="fw-semibold mb-3">Charges</h6>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th>Trimester</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">Paid</th>
                                            <th class="text-end">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($statementData['charges'] as $charge)
                                            <tr>
                                                <td>{{ optional($charge->charge_date)->format('d M Y') ?? '—' }}</td>
                                                <td>{{ $charge->description }}</td>
                                                <td>{{ $charge->trimester?->name ?? '—' }}</td>
                                                <td class="text-end">KES {{ number_format($charge->amount, 2) }}</td>
                                                <td class="text-end">KES {{ number_format($charge->amount_paid, 2) }}
                                                </td>
                                                <td class="text-end">KES {{ number_format($charge->balance, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-3">
                                                    No charges found for this trimester.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div>
                            <h6 class="fw-semibold mb-3">Payments Applied This Trimester</h6>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Receipt No</th>
                                            <th>Reference</th>
                                            <th>Applied To</th>
                                            <th class="text-end">Allocated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($statementData['allocations'] as $allocation)
                                            <tr>
                                                <td>{{ optional($allocation->payment?->payment_date)->format('d M Y') ?? '—' }}
                                                </td>
                                                <td>{{ $allocation->payment?->receipt_no ?? '—' }}</td>
                                                <td>{{ $allocation->payment?->reference_no ?? '—' }}</td>
                                                <td>{{ $allocation->studentFeeItem?->description ?? '—' }}</td>
                                                <td class="text-end">KES
                                                    {{ number_format($allocation->amount_allocated, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">
                                                    No payments applied in this trimester.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="text-end">Total Payments</th>
                                            <th class="text-end">
                                                KES {{ number_format($statementData['payment_total'], 2) }}
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-light border rounded-3 mb-0">
                            Select a trimester to load the statement.
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Close</button>
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
