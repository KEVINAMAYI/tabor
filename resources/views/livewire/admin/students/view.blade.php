<?php

use App\Models\Student;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Trimester;
use App\Models\Payment;
use App\Models\EnrollmentProgression;
use App\Models\PaymentAllocation;
use App\Models\StudentFeeItem;
use Livewire\Volt\Component;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use App\Services\FeeGenerationService;
use App\Services\FeeItemAdjustmentService;
use App\Services\PaymentPostingService;
use App\Services\EnrollmentChargePreviewService;
use App\Actions\Enrollment\CreateEnrollmentAction;
use App\Actions\Enrollment\UpdateEnrollmentAction;
use App\Actions\Enrollment\DeleteEnrollmentAction;
use App\Actions\Enrollment\MarkCourseCompletedAction;
use App\Actions\Enrollment\MoveToPendingGraduationAction;
use App\Actions\Enrollment\MarkGraduatedAction;
use App\Actions\Finance\ApplyStudentDiscountAction;
use App\Models\EnrollmentDeferral;
use App\Models\PaymentRefund;
use App\Services\DeferralService;
use App\Services\TrimesterRepeatService;
use App\Services\CreditService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Log;

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
    public $payment_reference = '';
    public $payment_receipt_no = '';
    public $payment_notes = '';

    // Discount modal
    public $discount_enrollment_id = null;
    public $discount_progression_id = '';
    public $discount_date = '';
    public $discount_amount = '';
    public $discount_description = '';

    // Deferral modal
    public $deferral_enrollment_id = null;
    public $deferral_resume_trimester_id = '';
    public $deferral_reason = '';

    // Waiver modal
    public $waiver_fee_item_id = null;
    public $waiver_reason = '';
    public $waiver_amount = '';

    // Edit fee item modal
    public $editFeeItemId = null;
    public $edit_fee_description = '';
    public $edit_fee_amount = '';
    public $edit_fee_charge_date = '';
    public $edit_fee_due_date = '';
    public $edit_fee_reason = '';

    // Fee item history modal
    public $historyFeeItemId = null;

    // Fee schedule / payments tab filters
    public $feeScheduleTrimesterFilter = '';
    public $paymentsTrimesterFilter = '';

    // Refund modal
    public $refund_payment_id = null;
    public $refund_amount = '';
    public $refund_reason = '';
    public $refund_method = '';

    // Repeat trimester modal
    public $repeat_progression_id = null;
    public $repeat_trimester_id = '';
    public $repeat_reason = '';

    // Edit progression modal
    public $editProgressionId = null;
    public $edit_progression_trimester_id = '';
    public $edit_progression_started_at = '';
    public $edit_progression_completed_at = '';
    public $edit_progression_notes = '';

    // Generate charges modal
    public $generateChargesEnrollmentId = null;
    public array $chargePreview = [];
    public array $enrollmentChargePreview = [];

    public function rules()
    {
        return [
            'course_id' => ['nullable', 'exists:courses,id'],
            'admission_date' => ['nullable', 'date'],
            'enrollment_status' => ['nullable', 'in:pending,active,deferred,withdrawn,cancelled,course_completed,pending_graduation,graduated'],

            'edit_course_id' => ['nullable', 'exists:courses,id'],
            'edit_admission_date' => ['nullable', 'date'],
            'edit_enrollment_status' => ['nullable', 'in:pending,active,deferred,withdrawn,cancelled,course_completed,pending_graduation,graduated'],

            'payment_date' => ['nullable', 'date'],
            'payment_amount' => ['nullable', 'numeric', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_receipt_no' => ['nullable', 'string', 'max:255'],
            'payment_notes' => ['nullable', 'string'],

            'edit_fee_description' => ['required', 'string', 'max:255'],
            'edit_fee_amount' => ['required', 'numeric', 'min:0'],
            'edit_fee_charge_date' => ['required', 'date'],
            'edit_fee_due_date' => ['nullable', 'date'],
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
        $student = $this->student->load(['enrollments.course', 'enrollments.intakeTrimester.academicYear', 'enrollments.assignedStartTrimester.academicYear', 'enrollments.feeItems', 'enrollments.payments', 'enrollments.progressions']);

        $enrollments = $student->enrollments->sortByDesc('created_at')->values();

        $selectedEnrollment = $enrollments->firstWhere('id', $this->selectedEnrollmentId);

        if (!$selectedEnrollment && $enrollments->count()) {
            $selectedEnrollment = $enrollments->first();
            $this->selectedEnrollmentId = $selectedEnrollment->id;
        }

        /*
    |--------------------------------------------------------------------------
    | Selected Enrollment Finance Summary
    |--------------------------------------------------------------------------
    */

        $totalCharges = 0;
        $totalPaid = 0;
        $balance = 0;

        if ($selectedEnrollment) {
            $totalCharges = $selectedEnrollment->feeItems->sum('amount');
            $totalPaid = $selectedEnrollment->feeItems->sum('amount_paid');
            $balance = $selectedEnrollment->feeItems->sum('balance');
        }

        /*
    |--------------------------------------------------------------------------
    | Enrollment Stats
    |--------------------------------------------------------------------------
    */

        $activeEnrollments = $student->enrollments->where('status', 'active')->count();

        $completedEnrollments = $student->enrollments->whereIn('status', ['course_completed', 'pending_graduation', 'graduated'])->count();

        /*
    |--------------------------------------------------------------------------
    | Dropdown Data
    |--------------------------------------------------------------------------
    */

        $courses = Course::query()->where('active', true)->orderBy('title')->get();

        $trimesters = Trimester::query()->orderBy('start_date')->get();

        /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */

        $payments = Payment::query()
            ->with(['allocations.studentFeeItem.progression.trimester'])
            ->where('student_id', $student->id)
            ->when(filled($this->paymentsTrimesterFilter), function ($q) {
                $q->whereHas('allocations.studentFeeItem', function ($sub) {
                    $sub->where('enrollment_progression_id', $this->paymentsTrimesterFilter);
                });
            })
            ->latest('payment_date')
            ->latest('id')
            ->get();

        return [
            'enrollments' => $enrollments,
            'selectedEnrollment' => $selectedEnrollment,
            'courses' => $courses,
            'trimesters' => $trimesters,
            'payments' => $payments,

            'totalCharges' => $totalCharges,
            'totalPaid' => $totalPaid,
            'balance' => $balance,

            'activeEnrollments' => $activeEnrollments,
            'completedEnrollments' => $completedEnrollments,

            'discountProgressions' => $selectedEnrollment
                ? EnrollmentProgression::query()
                    ->with(['trimester.academicYear'])
                    ->where('enrollment_id', $selectedEnrollment->id)
                    ->orderBy('trimester_sequence')
                    ->get()
                : collect(),

            'selectedFeeItems' => $selectedEnrollment
                ? StudentFeeItem::query()
                    ->with(['progression.trimester'])
                    ->withCount('audits')
                    ->where('enrollment_id', $selectedEnrollment->id)
                    ->when(filled($this->feeScheduleTrimesterFilter), function ($q) {
                        $q->where('enrollment_progression_id', $this->feeScheduleTrimesterFilter);
                    })
                    ->orderBy('charge_date')
                    ->orderBy('id')
                    ->get()
                : collect(),

            'historyFeeItem' => $this->historyFeeItemId
                ? StudentFeeItem::with(['audits.user'])->find($this->historyFeeItemId)
                : null,
        ];
    }

    public function selectEnrollment(int $enrollmentId): void
    {
        $this->selectedEnrollmentId = $enrollmentId;
        $this->feeScheduleTrimesterFilter = '';
        $this->paymentsTrimesterFilter = '';
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
            'enrollment_status' => ['required', 'in:pending,active,deferred,withdrawn,cancelled,course_completed,pending_graduation,graduated'],
        ]);

        try {
            $enrollment = app(CreateEnrollmentAction::class)->execute($this->student, [
                'course_id' => $this->course_id,
                'admission_date' => $this->admission_date,
                'status' => $this->enrollment_status,
                'fee_overrides' => $this->enrollmentChargePreview,
            ]);

            $this->selectedEnrollmentId = $enrollment->id;

            $this->resetEnrollForm();
            $this->dispatch('hide-enroll-modal');

            LivewireAlert::text('Enrollment created successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            Log::error('Error creating enrollment', [
                'student_id' => $this->student->id,
                'message' => $th->getMessage(),
            ]);

            LivewireAlert::text('Error creating enrollment: ' . $th->getMessage())
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
        }
    }

    public function markCourseCompleted($enrollmentId): void
    {
        try {
            $enrollment = $this->student->enrollments()->findOrFail($enrollmentId);

            app(MarkCourseCompletedAction::class)->execute($enrollment);

            $this->loadStudentData();

            LivewireAlert::text('Course marked as completed.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            LivewireAlert::text($th->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function moveToPendingGraduation($enrollmentId): void
    {
        try {
            $enrollment = $this->student->enrollments()->findOrFail($enrollmentId);

            app(MoveToPendingGraduationAction::class)->execute($enrollment);

            $this->loadStudentData();

            LivewireAlert::text('Moved to pending graduation and graduation fees generated.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            LivewireAlert::text($th->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function markGraduated($enrollmentId): void
    {
        try {
            $enrollment = $this->student->enrollments()->findOrFail($enrollmentId);

            app(MarkGraduatedAction::class)->execute($enrollment);

            $this->loadStudentData();

            LivewireAlert::text('Student marked as graduated.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            LivewireAlert::text($th->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function loadStudentData(): void
    {
        $this->student = Student::findOrFail($this->student->id);
    }

    public function updatedCourseId($value): void
    {
        $this->loadEnrollmentChargePreview();
    }

    public function loadEnrollmentChargePreview(): void
    {
        $this->enrollmentChargePreview = [];

        if (blank($this->course_id)) {
            return;
        }

        $course = Course::find($this->course_id);

        if (!$course) {
            return;
        }

        $this->enrollmentChargePreview = app(EnrollmentChargePreviewService::class)->preview($this->student, $course);
    }

    public function removeEnrollmentFeeItem(int $index): void
    {
        unset($this->enrollmentChargePreview[$index]);
        $this->enrollmentChargePreview = array_values($this->enrollmentChargePreview);
    }

    public function addCustomEnrollmentFeeItem(): void
    {
        $this->enrollmentChargePreview[] = [
            'type' => 'Custom',
            'description' => '',
            'timing' => 'One-time (specific to this student)',
            'amount' => 0,
            'fee_definition_id' => null,
            'course_fee_plan_id' => null,
        ];
    }

    public function getEnrollmentFeeTotalProperty(): float
    {
        return (float) collect($this->enrollmentChargePreview)->sum(fn ($item) => (float) ($item['amount'] ?? 0));
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
            'edit_enrollment_status' => ['required', 'in:pending,active,deferred,withdrawn,cancelled,course_completed,pending_graduation,graduated'],
        ]);

        try {
            $enrollment = Enrollment::where('student_id', $this->student->id)->findOrFail($this->editEnrollmentId);

            $enrollment = app(UpdateEnrollmentAction::class)->execute($enrollment, [
                'course_id' => $this->edit_course_id,
                'admission_date' => $this->edit_admission_date,
                'status' => $this->edit_enrollment_status,
            ]);

            $this->selectedEnrollmentId = $enrollment->id;

            $this->resetEditForm();
            $this->dispatch('hide-edit-enrollment-modal');

            LivewireAlert::text('Enrollment updated successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            LivewireAlert::text('Error updating enrollment: ' . $th->getMessage())
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
        }
    }

    public function deleteEnrollment(int $enrollmentId): void
    {
        try {
            $enrollment = Enrollment::where('student_id', $this->student->id)->findOrFail($enrollmentId);

            app(DeleteEnrollmentAction::class)->execute($enrollment);

            if ((int) $this->selectedEnrollmentId === (int) $enrollmentId) {
                $this->selectedEnrollmentId = $this->student->enrollments()->latest()->value('id');
            }

            LivewireAlert::text('Enrollment deleted successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            LivewireAlert::text('Error deleting enrollment: ' . $th->getMessage())
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
        }
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
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_notes' => ['nullable', 'string'],
        ]);

        app(PaymentPostingService::class)->post([
            'student_id' => $this->student->id,
            'enrollment_id' => $this->payment_enrollment_id,
            'payment_date' => $this->payment_date,
            'amount' => $this->payment_amount,
            'method' => $this->payment_method,
            'reference' => $this->payment_reference,
            'receipt_no' => $this->payment_receipt_no,
            'notes' => $this->payment_notes,
        ]);

        $this->dispatch('hide-payment-modal');
        $this->resetPaymentForm();

        LivewireAlert::text('Payment posted successfully.')->success()->toast()->position('top-end')->show();
    }

    public function openDiscountModal(int $enrollmentId): void
    {
        $this->resetDiscountForm();

        $this->discount_enrollment_id = $enrollmentId;
        $this->discount_date = now()->toDateString();

        $currentProgression = EnrollmentProgression::query()->where('enrollment_id', $enrollmentId)->where('status', 'active')->orderByDesc('id')->first();

        $this->discount_progression_id = $currentProgression?->id ?? '';

        $this->dispatch('show-discount-modal');
    }

    public function saveDiscount(): void
    {
        $this->validate([
            'discount_enrollment_id' => ['required', 'exists:enrollments,id'],
            'discount_progression_id' => ['required', 'exists:enrollment_progressions,id'],
            'discount_date' => ['required', 'date'],
            'discount_amount' => ['required', 'numeric', 'min:1'],
            'discount_description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $progression = EnrollmentProgression::query()->where('enrollment_id', $this->discount_enrollment_id)->findOrFail($this->discount_progression_id);

            app(ApplyStudentDiscountAction::class)->execute($progression, [
                'amount' => $this->discount_amount,
                'discount_date' => $this->discount_date,
                'description' => $this->discount_description ?: 'Discount - Trimester ' . $progression->trimester_sequence,
            ]);

            $this->resetDiscountForm();

            $this->dispatch('hide-discount-modal');

            LivewireAlert::text('Discount applied successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            LivewireAlert::text('Failed to apply discount: ' . $th->getMessage())
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
        }
    }

    protected function resetDiscountForm(): void
    {
        $this->discount_enrollment_id = null;
        $this->discount_progression_id = '';
        $this->discount_date = now()->toDateString();
        $this->discount_amount = '';
        $this->discount_description = '';
    }

    protected function resetEnrollForm(): void
    {
        $this->course_id = '';
        $this->admission_date = now()->toDateString();
        $this->enrollment_status = 'active';
        $this->enrollmentChargePreview = [];
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
        $this->payment_reference = '';
        $this->payment_receipt_no = '';
        $this->payment_notes = '';
    }

    // -----------------------------------------------------------------------
    // Deferral
    // -----------------------------------------------------------------------

    public function openDeferralModal(int $enrollmentId): void
    {
        $this->deferral_enrollment_id = $enrollmentId;
        $this->deferral_resume_trimester_id = '';
        $this->deferral_reason = '';
        $this->dispatch('show-deferral-modal');
    }

    public function saveDeferral(): void
    {
        $this->validate([
            'deferral_enrollment_id'       => ['required', 'exists:enrollments,id'],
            'deferral_resume_trimester_id' => ['required', 'exists:trimesters,id'],
            'deferral_reason'              => ['required', 'string', 'min:5'],
        ]);

        try {
            $enrollment = Enrollment::where('student_id', $this->student->id)->findOrFail($this->deferral_enrollment_id);
            $trimester  = Trimester::findOrFail($this->deferral_resume_trimester_id);

            $deferral = app(DeferralService::class)->requestDeferral($enrollment, $trimester, $this->deferral_reason);

            // Auto-approve since admin is making the request
            app(DeferralService::class)->approveDeferral($deferral, auth()->user());

            $this->deferral_enrollment_id = null;
            $this->deferral_resume_trimester_id = '';
            $this->deferral_reason = '';
            $this->dispatch('hide-deferral-modal');

            LivewireAlert::text('Deferral approved. Student will resume in the selected trimester.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            LivewireAlert::text('Deferral failed: ' . $th->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    // -----------------------------------------------------------------------
    // Waiver
    // -----------------------------------------------------------------------

    public function openWaiverModal(int $feeItemId): void
    {
        $feeItem = StudentFeeItem::where('student_id', $this->student->id)->findOrFail($feeItemId);
        $this->waiver_fee_item_id = $feeItem->id;
        $this->waiver_amount      = (string) $feeItem->balance;
        $this->waiver_reason      = '';
        $this->dispatch('show-waiver-modal');
    }

    public function saveWaiver(): void
    {
        $this->validate([
            'waiver_fee_item_id' => ['required', 'exists:student_fee_items,id'],
            'waiver_amount'      => ['required', 'numeric', 'min:0.01'],
            'waiver_reason'      => ['required', 'string', 'min:5'],
        ]);

        try {
            $feeItem = StudentFeeItem::where('student_id', $this->student->id)->findOrFail($this->waiver_fee_item_id);

            app(CreditService::class)->waiveFee($feeItem, $this->waiver_reason, auth()->user(), (float) $this->waiver_amount);

            $this->waiver_fee_item_id = null;
            $this->waiver_reason = '';
            $this->waiver_amount = '';
            $this->dispatch('hide-waiver-modal');

            LivewireAlert::text('Fee waived successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            LivewireAlert::text('Waiver failed: ' . $th->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    // -----------------------------------------------------------------------
    // Edit Fee Item
    // -----------------------------------------------------------------------

    public function openEditFeeItemModal(int $feeItemId): void
    {
        $feeItem = StudentFeeItem::where('student_id', $this->student->id)->findOrFail($feeItemId);

        $this->editFeeItemId = $feeItem->id;
        $this->edit_fee_description = $feeItem->description;
        $this->edit_fee_amount = (string) $feeItem->amount;
        $this->edit_fee_charge_date = optional($feeItem->charge_date)->format('Y-m-d');
        $this->edit_fee_due_date = optional($feeItem->due_date)->format('Y-m-d');
        $this->edit_fee_reason = '';

        $this->dispatch('show-edit-fee-item-modal');
    }

    public function updateFeeItem(): void
    {
        $this->validate([
            'edit_fee_description' => ['required', 'string', 'max:255'],
            'edit_fee_amount' => ['required', 'numeric', 'min:0'],
            'edit_fee_charge_date' => ['required', 'date'],
            'edit_fee_due_date' => ['nullable', 'date'],
        ]);

        try {
            $feeItem = StudentFeeItem::where('student_id', $this->student->id)->findOrFail($this->editFeeItemId);

            $amountChanged = abs((float) $this->edit_fee_amount - (float) $feeItem->amount) > 0.001;

            if ($amountChanged && blank($this->edit_fee_reason)) {
                $this->addError('edit_fee_reason', 'A reason is required when changing the amount.');
                return;
            }

            app(FeeItemAdjustmentService::class)->update($feeItem, [
                'description' => $this->edit_fee_description,
                'amount' => $this->edit_fee_amount,
                'charge_date' => $this->edit_fee_charge_date,
                'due_date' => $this->edit_fee_due_date,
                'reason' => $this->edit_fee_reason ?: null,
            ], auth()->user());

            $this->editFeeItemId = null;
            $this->edit_fee_reason = '';
            $this->dispatch('hide-edit-fee-item-modal');

            LivewireAlert::text('Fee item updated successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            LivewireAlert::text('Failed to update fee item: ' . $th->getMessage())
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
        }
    }

    public function openFeeItemHistoryModal(int $feeItemId): void
    {
        $this->historyFeeItemId = StudentFeeItem::where('student_id', $this->student->id)
            ->findOrFail($feeItemId)
            ->id;

        $this->dispatch('show-fee-item-history-modal');
    }

    // -----------------------------------------------------------------------
    // Refund
    // -----------------------------------------------------------------------

    public function openRefundModal(int $paymentId): void
    {
        $this->refund_payment_id = $paymentId;
        $this->refund_amount     = '';
        $this->refund_reason     = '';
        $this->refund_method     = 'cash';
        $this->dispatch('show-refund-modal');
    }

    public function saveRefund(): void
    {
        $this->validate([
            'refund_payment_id' => ['required', 'exists:payments,id'],
            'refund_amount'     => ['required', 'numeric', 'min:0.01'],
            'refund_reason'     => ['required', 'string', 'min:5'],
            'refund_method'     => ['required', 'in:cash,bank,mpesa'],
        ]);

        try {
            $payment = Payment::where('student_id', $this->student->id)->findOrFail($this->refund_payment_id);
            $refundService = app(RefundService::class);

            $refund = $refundService->initiateRefund($payment, (float) $this->refund_amount, $this->refund_reason);
            $refundService->processRefund($refund, $this->refund_method, null, auth()->user());

            $this->refund_payment_id = null;
            $this->refund_amount = '';
            $this->refund_reason = '';
            $this->refund_method = '';
            $this->dispatch('hide-refund-modal');

            LivewireAlert::text('Refund processed successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            LivewireAlert::text('Refund failed: ' . $th->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    // -----------------------------------------------------------------------
    // Repeat Trimester
    // -----------------------------------------------------------------------

    public function openRepeatTrimesterModal(int $progressionId): void
    {
        $this->repeat_progression_id = $progressionId;
        $this->repeat_trimester_id   = '';
        $this->repeat_reason         = '';
        $this->dispatch('show-repeat-trimester-modal');
    }

    public function saveRepeatTrimester(): void
    {
        $this->validate([
            'repeat_progression_id' => ['required', 'exists:enrollment_progressions,id'],
            'repeat_trimester_id'   => ['required', 'exists:trimesters,id'],
            'repeat_reason'         => ['required', 'string', 'min:5'],
        ]);

        try {
            $progression = EnrollmentProgression::where('student_id', $this->student->id)->findOrFail($this->repeat_progression_id);
            $trimester   = Trimester::findOrFail($this->repeat_trimester_id);

            app(TrimesterRepeatService::class)->repeatTrimester($progression, $trimester, $this->repeat_reason);

            $this->repeat_progression_id = null;
            $this->repeat_trimester_id   = '';
            $this->repeat_reason         = '';
            $this->dispatch('hide-repeat-trimester-modal');

            LivewireAlert::text('Trimester marked as repeated. New progression created with fresh charges.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            LivewireAlert::text('Failed: ' . $th->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    // -----------------------------------------------------------------------
    // Edit Progression (correct trimester link / dates)
    // -----------------------------------------------------------------------

    public function openEditProgressionModal(int $progressionId): void
    {
        $progression = EnrollmentProgression::where('student_id', $this->student->id)->findOrFail($progressionId);

        $this->editProgressionId = $progression->id;
        $this->edit_progression_trimester_id = $progression->trimester_id;
        $this->edit_progression_started_at = optional($progression->started_at)->format('Y-m-d');
        $this->edit_progression_completed_at = optional($progression->completed_at)->format('Y-m-d');
        $this->edit_progression_notes = $progression->notes;

        $this->dispatch('show-edit-progression-modal');
    }

    public function updateProgressionDates(): void
    {
        $this->validate([
            'edit_progression_trimester_id' => ['required', 'exists:trimesters,id'],
            'edit_progression_started_at'   => ['nullable', 'date'],
            'edit_progression_completed_at' => ['nullable', 'date'],
            'edit_progression_notes'        => ['nullable', 'string', 'max:1000'],
        ]);

        if (
            $this->edit_progression_started_at
            && $this->edit_progression_completed_at
            && $this->edit_progression_completed_at < $this->edit_progression_started_at
        ) {
            $this->addError('edit_progression_completed_at', 'Completed date must be on or after the start date.');
            return;
        }

        try {
            $progression = EnrollmentProgression::where('student_id', $this->student->id)->findOrFail($this->editProgressionId);

            $duplicate = EnrollmentProgression::where('enrollment_id', $progression->enrollment_id)
                ->where('trimester_id', $this->edit_progression_trimester_id)
                ->where('id', '!=', $progression->id)
                ->exists();

            if ($duplicate) {
                throw new \Exception('This enrollment already has a progression linked to that trimester.');
            }

            $progression->update([
                'trimester_id'  => $this->edit_progression_trimester_id,
                'started_at'    => $this->edit_progression_started_at ?: null,
                'completed_at'  => $this->edit_progression_completed_at ?: null,
                'notes'         => $this->edit_progression_notes ?: null,
            ]);

            $this->editProgressionId = null;
            $this->dispatch('hide-edit-progression-modal');

            LivewireAlert::text('Progression updated successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            LivewireAlert::text('Failed to update progression: ' . $th->getMessage())
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
        }
    }
};

?>
@push('styles')
<style>
/* ════════════════════════════════════════════
   Student View — Layout
════════════════════════════════════════════ */
.sv-layout {
    display: flex;
    gap: 0;
    align-items: flex-start;
}
.sv-sidebar {
    width: 288px;
    flex-shrink: 0;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    overflow: hidden;
    border: 1px solid #f0f0f5;
}
.sv-sidebar-header {
    padding: 14px 18px 12px;
    border-bottom: 1px solid #f3f4f6;
    background: #fafafa;
}
.sv-sidebar-body {
    overflow-y: auto;
    max-height: calc(100vh - 260px);
    padding: 10px;
}
.sv-detail {
    flex: 1;
    min-width: 0;
    margin-left: 16px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    border: 1px solid #f0f0f5;
    overflow: hidden;
}

/* ════════════════════════════════════════════
   Student Header
════════════════════════════════════════════ */
.sv-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #635bff, #46caeb);
    color: #fff;
    font-size: 1.4rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    letter-spacing: -1px;
}
.sv-stat-card {
    border-radius: 10px;
    padding: 8px 16px;
    background: #f9f9ff;
    border: 1px solid #eeeeff;
    min-width: 80px;
}
.sv-stat-label {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #98a2b3;
    margin-bottom: 2px;
    white-space: nowrap;
}
.sv-stat-value {
    font-size: .95rem;
    font-weight: 700;
    color: #101828;
}

/* ════════════════════════════════════════════
   Enrollment sidebar cards
════════════════════════════════════════════ */
.sv-enroll-card {
    border: 1px solid #e9ecef;
    border-left: 3px solid transparent;
    border-radius: 10px;
    padding: 11px 13px;
    margin-bottom: 6px;
    cursor: pointer;
    transition: all .15s ease;
}
.sv-enroll-card:hover {
    background: #fafafe;
    border-color: #d5d3ff;
    border-left-color: #9b8ffd;
}
.sv-enroll-card.is-selected {
    background: #f5f4ff;
    border-color: #c7c2ff;
    border-left-color: #635bff;
}
.sv-enroll-card-title {
    font-size: .84rem;
    font-weight: 600;
    color: #101828;
    line-height: 1.35;
}
.sv-enroll-card-sub {
    font-size: .74rem;
    color: #98a2b3;
}

/* Progress dots */
.sv-dots { display: flex; gap: 3px; }
.sv-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #e9ecef;
}
.sv-dot.completed, .sv-dot.course_completed { background: #36c76c; }
.sv-dot.active    { background: #635bff; }
.sv-dot.deferred  { background: #F8C20A; }
.sv-dot.repeated  { background: #fd7e14; }
.sv-dot.cancelled { background: #ff6692; }
.sv-dot.graduated { background: #29343d; }

/* ════════════════════════════════════════════
   Enrollment detail panel
════════════════════════════════════════════ */
.sv-detail-card { display: flex; flex-direction: column; height: 100%; }
.sv-detail-top {
    padding: 20px 24px 16px;
    border-bottom: 1px solid #f3f4f6;
}
.sv-section-label {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #98a2b3;
    font-weight: 600;
    margin-bottom: 10px;
}

/* ════════════════════════════════════════════
   Progression timeline
════════════════════════════════════════════ */
.sv-timeline-section {
    padding: 16px 24px 14px;
    border-bottom: 1px solid #f3f4f6;
    background: #fafafe;
}
.sv-timeline-wrap { overflow-x: auto; }
.sv-timeline {
    display: flex;
    align-items: flex-start;
    padding: 4px 0 8px;
    min-width: max-content;
}
.sv-tl-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 88px;
}
.sv-tl-connector {
    flex: 1;
    height: 2px;
    background: #dee2e6;
    align-self: center;
    min-width: 28px;
    margin-top: -26px;
}
.sv-tl-connector.done    { background: #36c76c; }
.sv-tl-connector.partial { background: linear-gradient(to right, #36c76c, #635bff); }

.sv-tl-seq {
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .04em;
    color: #9e97ff;
    margin-bottom: 4px;
}
.sv-tl-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .85rem;
    font-weight: 700;
    border: 2px solid;
    margin-bottom: 6px;
}
.sv-tl-circle.completed,
.sv-tl-circle.course_completed { background: #36c76c; border-color: #36c76c; color: #fff; }
.sv-tl-circle.active           { background: #635bff; border-color: #635bff; color: #fff; box-shadow: 0 0 0 4px rgba(99,91,255,.12); }
.sv-tl-circle.deferred         { background: #fffbe6; border-color: #F8C20A; color: #92400e; }
.sv-tl-circle.repeated         { background: #fff3e0; border-color: #fd7e14; color: #c45a00; }
.sv-tl-circle.cancelled        { background: #fff0f4; border-color: #ff6692; color: #9f1239; }
.sv-tl-circle.pending-grad     { background: #e0f7fc; border-color: #46caeb; color: #0369a1; }
.sv-tl-circle.graduated        { background: #1f2937; border-color: #1f2937; color: #fff; }
.sv-tl-circle.upcoming         { background: #fff; border-color: #dee2e6; color: #adb5bd; }

.sv-tl-label {
    text-align: center;
    max-width: 84px;
}

/* ════════════════════════════════════════════
   Finance summary cards
════════════════════════════════════════════ */
.sv-fin-row { padding: 16px 20px; border-bottom: 1px solid #f3f4f6; }
.sv-fin-card { border-radius: 10px; padding: 14px 16px; }
.sv-fin-label {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    font-weight: 600;
    margin-bottom: 4px;
}
.sv-fin-value { font-size: 1.25rem; font-weight: 700; line-height: 1.1; }

.sv-fin-primary { background: #eef2ff; }
.sv-fin-primary .sv-fin-label { color: #635bff; }
.sv-fin-primary .sv-fin-value { color: #3730a3; }

.sv-fin-success { background: #e8f7ec; }
.sv-fin-success .sv-fin-label { color: #16a34a; }
.sv-fin-success .sv-fin-value { color: #15803d; }

.sv-fin-danger  { background: #fff0f4; }
.sv-fin-danger .sv-fin-label  { color: #ff6692; }
.sv-fin-danger .sv-fin-value  { color: #9f1239; }

/* ════════════════════════════════════════════
   Action bar & tabs
════════════════════════════════════════════ */
.sv-action-bar {
    padding: 12px 20px;
    border-bottom: 1px solid #f3f4f6;
    background: #fcfcfe;
}
.sv-tabs-wrap { flex: 1; display: flex; flex-direction: column; }
.sv-tabs {
    padding: 8px 16px 0;
    border-bottom: 1px solid #f0f0f5;
    background: #fcfcfe;
}
.sv-tabs .nav-link {
    font-size: .8rem;
    font-weight: 500;
    color: #667085;
    padding: 7px 14px;
    border-radius: 6px 6px 0 0;
    border: none;
}
.sv-tabs .nav-link.active {
    color: #635bff;
    background: #fff;
    border: 1px solid #f0f0f5;
    border-bottom: 1px solid #fff;
    margin-bottom: -1px;
}
.sv-tabs .nav-link:not(.active):hover { color: #635bff; background: #f5f4ff; }
.sv-tab-content { flex: 1; overflow: hidden; }
.sv-tab-content .tab-pane { padding: 0; }

/* Fee item status badges */
.badge-waived  { background: #f3f4f6; color: #6b7280; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-partial { background: #dbeafe; color: #1d4ed8; }
.badge-paid    { background: #d1fae5; color: #065f46; }

/* ════════════════════════════════════════════
   Responsive
════════════════════════════════════════════ */
@media (max-width: 991.98px) {
    .sv-layout { flex-direction: column; }
    .sv-sidebar { width: 100%; }
    .sv-detail  { margin-left: 0; margin-top: 16px; }
    .sv-sidebar-body { max-height: 240px; }
}
</style>
@endpush

<div>
    {{-- Back breadcrumb --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('students.index') }}" class="btn btn-sm btn-outline-secondary rounded-3 px-3">
            <i class="ti ti-arrow-left me-1"></i> Back
        </a>
        <span class="text-muted small">
            Students / {{ $student->first_name }} {{ $student->last_name }}
        </span>
    </div>

    {{-- Student header --}}
    @include('livewire.admin.students.partials._header')

    {{-- Two-column layout --}}
    <div class="sv-layout mt-4">

        @include('livewire.admin.students.partials._enrollment-sidebar')

        <div class="sv-detail">
            @if ($selectedEnrollment)
                @include('livewire.admin.students.partials._enrollment-panel')
            @else
                <div class="text-center py-5 px-4">
                    <div class="mb-3">
                        <i class="ti ti-layout-sidebar-right-expand"
                           style="font-size:2.8rem; color:#c7c2ff;"></i>
                    </div>
                    <h6 class="fw-semibold text-dark">Select an enrollment</h6>
                    <p class="text-muted small mb-0">
                        Choose an enrollment from the list on the left to view
                        details, financials, and actions.
                    </p>
                </div>
            @endif
        </div>

    </div>

    {{-- All modals --}}
    @include('livewire.admin.students.partials._modals')

</div>

@push('scripts')
<script>
    function modalInstance(id) {
        const el = document.getElementById(id);
        if (!el) return null;
        return bootstrap.Modal.getOrCreateInstance(el);
    }

    window.addEventListener('show-enroll-modal',           () => modalInstance('enrollModal')?.show());
    window.addEventListener('hide-enroll-modal',           () => modalInstance('enrollModal')?.hide());
    window.addEventListener('show-edit-enrollment-modal',  () => modalInstance('editEnrollmentModal')?.show());
    window.addEventListener('hide-edit-enrollment-modal',  () => modalInstance('editEnrollmentModal')?.hide());
    window.addEventListener('show-generate-charges-modal', () => modalInstance('generateChargesModal')?.show());
    window.addEventListener('hide-generate-charges-modal', () => modalInstance('generateChargesModal')?.hide());
    window.addEventListener('show-payment-modal',          () => modalInstance('paymentModal')?.show());
    window.addEventListener('hide-payment-modal',          () => modalInstance('paymentModal')?.hide());
    window.addEventListener('show-discount-modal',         () => modalInstance('discountModal')?.show());
    window.addEventListener('hide-discount-modal',         () => modalInstance('discountModal')?.hide());
    window.addEventListener('show-deferral-modal',         () => modalInstance('deferralModal')?.show());
    window.addEventListener('hide-deferral-modal',         () => modalInstance('deferralModal')?.hide());
    window.addEventListener('show-waiver-modal',           () => modalInstance('waiverModal')?.show());
    window.addEventListener('hide-waiver-modal',           () => modalInstance('waiverModal')?.hide());
    window.addEventListener('show-edit-fee-item-modal',    () => modalInstance('editFeeItemModal')?.show());
    window.addEventListener('hide-edit-fee-item-modal',    () => modalInstance('editFeeItemModal')?.hide());
    window.addEventListener('show-fee-item-history-modal', () => modalInstance('feeItemHistoryModal')?.show());
    window.addEventListener('show-refund-modal',           () => modalInstance('refundModal')?.show());
    window.addEventListener('hide-refund-modal',           () => modalInstance('refundModal')?.hide());
    window.addEventListener('show-repeat-trimester-modal', () => modalInstance('repeatTrimesterModal')?.show());
    window.addEventListener('hide-repeat-trimester-modal', () => modalInstance('repeatTrimesterModal')?.hide());
    window.addEventListener('show-edit-progression-modal', () => modalInstance('editProgressionModal')?.show());
    window.addEventListener('hide-edit-progression-modal', () => modalInstance('editProgressionModal')?.hide());
</script>
@endpush