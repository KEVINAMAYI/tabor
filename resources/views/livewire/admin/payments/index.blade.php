<?php

use App\Exports\PaymentExport;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentFeeItem;
use App\Models\PaymentAllocation;
use App\Services\PaymentPostingService;
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
    public $selected = [];

    public $search = '';
    public $studentFilter = '';
    public $allocationFilter = '';
    public $methodFilter = '';
    public $perPage = 10;

    public $amount;
    public $payment_method;
    public $reference;
    public $paid_at;
    public $enrollment_id;
    public $status;
    public $payer;
    public $editId = null;

    public $student_search = '';
    public $enrollments = [];

    public $selectedPayment = null;
    public $allocationRows = [];
    public $allocationDraftRows = [];
    public $unallocatedAmount = 0;

    public $paymentAllocationRows = [];

    // When true (the default, matching prior behavior), any amount left
    // unallocated after the rows above is auto-swept via FIFO priority
    // order onto the student's other outstanding fees. Toggle off to
    // genuinely leave it unallocated — e.g. deliberately reducing/removing
    // a wrong allocation without the freed-up money silently landing
    // somewhere else the admin didn't choose.
    public $autoAllocateRemaining = true;

    protected string $paginationTheme = 'bootstrap';

    public function rules()
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string'],
            'reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
            'enrollment_id' => ['nullable', 'exists:enrollments,id'],

            'paymentAllocationRows' => ['array'],
            'paymentAllocationRows.*.student_id' => ['nullable', 'exists:students,id'],
            'paymentAllocationRows.*.enrollment_id' => ['nullable', 'exists:enrollments,id'],
            'paymentAllocationRows.*.student_fee_item_id' => ['nullable', 'exists:student_fee_items,id'],
            'paymentAllocationRows.*.amount' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    public function mount()
    {
        $this->paid_at = now()->toDateString();

        $this->enrollments = Enrollment::query()
            ->with(['student', 'course'])
            ->latest()
            ->limit(20)
            ->get();
    }

    #[On('search')]
    public function search()
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedStudentFilter(): void
    {
        $this->resetPage();
    }

    public function updatedAllocationFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMethodFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[On('perform-search')]
    public function searchEnrollments($query)
    {
        $this->student_search = $query;

        $this->enrollments = Enrollment::with(['student', 'course'])
            ->whereHas('student', function ($q) use ($query) {
                $q->where('first_name', 'like', '%' . $query . '%')
                    ->orWhere('last_name', 'like', '%' . $query . '%')
                    ->orWhere('admission_number', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%');
            })
            ->orWhereHas('course', function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')->orWhere('code', 'like', '%' . $query . '%');
            })
            ->limit(10)
            ->get();
    }

    public function selectEnrollment($id)
    {
        $this->enrollment_id = $id;

        $enrollment = Enrollment::with(['student', 'course'])->findOrFail($id);

        $this->student_search = $enrollment->student?->first_name . ' ' . $enrollment->student?->last_name . ' - ' . $enrollment->course?->title;

        if (empty($this->paymentAllocationRows)) {
            $this->addPaymentAllocationRow();
        }
    }

    public function with()
    {
        $baseQuery = Payment::query()
            ->with(['enrollment.student', 'enrollment.course', 'allocations.studentFeeItem.student', 'allocations.studentFeeItem.enrollment.course'])
            ->when(filled($this->search), function ($q) {
                $q->where(function ($query) {
                    $query
                        ->whereHas('enrollment.student', function ($studentQuery) {
                            $studentQuery
                                ->where('first_name', 'like', "%{$this->search}%")
                                ->orWhere('last_name', 'like', "%{$this->search}%")
                                ->orWhere('email', 'like', "%{$this->search}%")
                                ->orWhere('admission_number', 'like', "%{$this->search}%");
                        })
                        ->orWhere('method', 'like', "%{$this->search}%")
                        ->orWhere('payment_method', 'like', "%{$this->search}%")
                        ->orWhere('reference', 'like', "%{$this->search}%")
                        ->orWhere('receipt_no', 'like', "%{$this->search}%")
                        ->orWhere('transaction_id', 'like', "%{$this->search}%")
                        ->orWhere('payer', 'like', "%{$this->search}%");
                });
            })
            ->when(filled($this->studentFilter), function ($q) {
                $q->where(function ($query) {
                    $query
                        ->where('student_id', $this->studentFilter)
                        ->orWhereHas('enrollment', function ($enrollmentQuery) {
                            $enrollmentQuery->where('student_id', $this->studentFilter);
                        })
                        ->orWhereHas('allocations.studentFeeItem', function ($itemQuery) {
                            $itemQuery->where('student_id', $this->studentFilter);
                        });
                });
            })
            ->when(filled($this->methodFilter), function ($q) {
                $q->where(function ($query) {
                    $query->where('method', $this->methodFilter)->orWhere('payment_method', $this->methodFilter);
                });
            });

        $paymentsForSummary = (clone $baseQuery)->get();

        if (filled($this->allocationFilter)) {
            $paymentsForSummary = $paymentsForSummary->filter(function ($payment) {
                $allocated = (float) $payment->allocations->sum('amount_allocated');
                $unallocated = (float) ($payment->unallocated_balance ?? (float) $payment->amount - $allocated);

                return match ($this->allocationFilter) {
                    'fully_allocated' => $unallocated <= 0,
                    'partial' => $allocated > 0 && $unallocated > 0,
                    'unallocated' => $allocated <= 0 && $unallocated > 0,
                    default => true,
                };
            });
        }

        $paymentIds = $paymentsForSummary->pluck('id')->all();

        $payments = Payment::query()
            ->with(['enrollment.student', 'enrollment.course', 'allocations.studentFeeItem.student', 'allocations.studentFeeItem.enrollment.course'])
            ->whereIn('id', $paymentIds)
            ->latest()
            ->paginate($this->perPage);

        return [
            'payments' => $payments,

            'students' => Student::query()->orderBy('first_name')->orderBy('last_name')->get(),

            'methods' => Payment::query()->selectRaw('COALESCE(method, payment_method) as method_name')->whereRaw('COALESCE(method, payment_method) IS NOT NULL')->distinct()->orderBy('method_name')->pluck('method_name'),

            'allocationStudents' => Student::query()->orderBy('first_name')->orderBy('last_name')->get(),

            'allocationEnrollments' => Enrollment::query()
                ->with(['student', 'course'])
                ->whereIn('status', ['active', 'course_completed', 'pending_graduation', 'graduated'])
                ->orderByDesc('id')
                ->get(),

            'allocationFeeItems' => StudentFeeItem::query()
                ->with(['student', 'enrollment.course', 'feeDefinition'])
                ->where(function ($q) {
                    $q->where(function ($outstanding) {
                        $outstanding->where('balance', '>', 0)->whereIn('status', ['pending', 'partial']);
                    });

                    // Always include whatever fee item each allocation row
                    // is CURRENTLY set to, even if its balance is now 0 —
                    // which it always will be for a payment being edited,
                    // since that balance was paid down by this very
                    // payment. Without this, the dropdown has no <option>
                    // for the row's real value, renders blank, and saving
                    // silently wipes every allocation on this payment (see
                    // updatePayment()'s reverse-then-reapply flow).
                    $rowFeeItemIds = collect($this->paymentAllocationRows)
                        ->pluck('student_fee_item_id')
                        ->filter()
                        ->unique()
                        ->values();

                    if ($rowFeeItemIds->isNotEmpty()) {
                        $q->orWhereIn('id', $rowFeeItemIds);
                    }
                })
                ->orderBy('charge_date')
                ->orderBy('id')
                ->get(),

            'summaryCount' => $paymentsForSummary->count(),
            'summaryAmount' => $paymentsForSummary->sum('amount'),
            'summaryAllocated' => $paymentsForSummary->sum(fn($payment) => $payment->allocations->sum('amount_allocated')),
            'summaryUnallocated' => $paymentsForSummary->sum(fn($payment) => $payment->unallocated_balance ?? (float) $payment->amount - (float) $payment->allocations->sum('amount_allocated')),
        ];
    }

    public function openCreatePaymentModal(): void
    {
        $this->resetForm();
        $this->addPaymentAllocationRow();
        $this->dispatch('show-payment-modal');
    }

    public function addPaymentAllocationRow(): void
    {
        $studentId = '';
        $enrollmentId = '';

        if ($this->enrollment_id) {
            $enrollment = Enrollment::find($this->enrollment_id);

            $studentId = $enrollment?->student_id ?: '';
            $enrollmentId = $enrollment?->id ?: '';
        }

        $this->paymentAllocationRows[] = [
            'student_id' => $studentId,
            'enrollment_id' => $enrollmentId,
            'student_fee_item_id' => '',
            'amount' => '',
        ];
    }

    public function removePaymentAllocationRow(int $index): void
    {
        unset($this->paymentAllocationRows[$index]);
        $this->paymentAllocationRows = array_values($this->paymentAllocationRows);
    }

    public function updatedPaymentAllocationRows($value, $key): void
    {
        if (str_ends_with($key, '.student_id')) {
            $index = explode('.', $key)[0];

            $this->paymentAllocationRows[$index]['enrollment_id'] = '';
            $this->paymentAllocationRows[$index]['student_fee_item_id'] = '';
        }

        if (str_ends_with($key, '.enrollment_id')) {
            $index = explode('.', $key)[0];

            $enrollment = Enrollment::find($value);

            if ($enrollment) {
                $this->paymentAllocationRows[$index]['student_id'] = $enrollment->student_id;
            }

            $this->paymentAllocationRows[$index]['student_fee_item_id'] = '';
        }

        if (str_ends_with($key, '.student_fee_item_id')) {
            $index = explode('.', $key)[0];

            $item = StudentFeeItem::find($value);

            if ($item) {
                $this->paymentAllocationRows[$index]['student_id'] = $item->student_id;
                $this->paymentAllocationRows[$index]['enrollment_id'] = $item->enrollment_id;
                $this->paymentAllocationRows[$index]['amount'] = $item->balance;
            }
        }
    }

    protected function validatePaymentAllocationRows(float $paymentAmount): void
    {
        $rows = collect($this->paymentAllocationRows)->filter(fn($row) => filled($row['student_fee_item_id'] ?? null) && (float) ($row['amount'] ?? 0) > 0)->values();

        $total = $rows->sum(fn($row) => (float) $row['amount']);

        if ($total > $paymentAmount) {
            throw new \RuntimeException('Total allocation cannot exceed payment amount.');
        }

        foreach ($rows as $row) {
            $feeItem = StudentFeeItem::find($row['student_fee_item_id']);

            if (!$feeItem) {
                throw new \RuntimeException('Invalid fee item selected.');
            }

            if ((float) $row['amount'] > (float) $feeItem->balance) {
                throw new \RuntimeException('Allocation amount cannot exceed fee item balance for ' . $feeItem->description);
            }
        }
    }

    /**
     * The payment's own student_id/enrollment_id used to come solely from
     * the top-level "Default Student / Enrollment" search field — if that
     * was left blank (even though a student/enrollment was picked directly
     * in an allocation row), the payment's own student_id/enrollment_id got
     * saved as null, silently unlinking it from the student even though its
     * PaymentAllocation rows correctly pointed at their fee items. Falls
     * back to the first allocation row that has a student picked.
     */
    protected function resolvePaymentStudentAndEnrollment(?Enrollment $enrollment): array
    {
        if ($enrollment) {
            return [$enrollment->student_id, $enrollment->id];
        }

        $fallbackRow = collect($this->paymentAllocationRows)
            ->first(fn($row) => filled($row['student_id'] ?? null));

        if (!$fallbackRow) {
            return [null, null];
        }

        return [
            $fallbackRow['student_id'] ?: null,
            $fallbackRow['enrollment_id'] ?: null,
        ];
    }

    protected function applyPaymentAllocationRows(Payment $payment): void
    {
        $remaining = (float) $payment->amount;

        foreach ($this->paymentAllocationRows as $row) {
            $feeItemId = $row['student_fee_item_id'] ?? null;
            $amount = (float) ($row['amount'] ?? 0);

            if (empty($feeItemId) || $amount <= 0) {
                continue;
            }

            $feeItem = StudentFeeItem::query()->where('id', $feeItemId)->lockForUpdate()->firstOrFail();

            $allocatable = min($amount, (float) $feeItem->balance, $remaining);

            if ($allocatable <= 0) {
                continue;
            }

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'student_fee_item_id' => $feeItem->id,
                'amount_allocated' => $allocatable,
            ]);

            $newPaid = (float) $feeItem->amount_paid + $allocatable;
            $newBalance = max(0, (float) $feeItem->amount - $newPaid);

            $feeItem->update([
                'amount_paid' => $newPaid,
                'balance' => $newBalance,
                'status' => $newBalance <= 0 ? 'paid' : 'partial',
            ]);

            $remaining -= $allocatable;
        }

        $payment->update([
            'unallocated_balance' => max(0, $remaining),
            'status' => 'completed', //max(0, $remaining) > 0 ? 'partial' :
        ]);
    }

    protected function reversePaymentAllocations(Payment $payment): void
    {
        $payment->loadMissing('allocations.studentFeeItem');

        foreach ($payment->allocations as $allocation) {
            $feeItem = $allocation->studentFeeItem;

            if (!$feeItem) {
                continue;
            }

            $newPaid = max(0, (float) $feeItem->amount_paid - (float) $allocation->amount_allocated);
            $newBalance = max(0, (float) $feeItem->amount - $newPaid);

            $feeItem->update([
                'amount_paid' => $newPaid,
                'balance' => $newBalance,
                'status' => $newBalance <= 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'pending'),
            ]);
        }

        $payment->allocations()->delete();

        $payment->update([
            'unallocated_balance' => $payment->amount,
        ]);
    }

    /**
     * The form's single, static wire:submit target — dispatches to
     * updatePayment() or addPayment() based on current state instead of
     * baking the method name into the rendered HTML. wire:submit.prevent
     * with a Blade-interpolated action name (e.g. "{{ $editId ? 'x' : 'y'
     * }}") is unreliable: this is a Bootstrap modal toggled by JS, not
     * re-mounted between opens, and the attribute Livewire's morph applies
     * can lag behind $editId's real value — so a save while editing could
     * silently fire addPayment() instead of updatePayment(), touching
     * nothing on the payment actually being edited while still looking
     * "successful". A single static target removes the whole class of bug.
     */
    public function savePayment(): void
    {
        if ($this->editId) {
            $this->updatePayment();
            return;
        }

        $this->addPayment();
    }

    public function addPayment()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $amount = (float) $this->amount;

            $this->validatePaymentAllocationRows($amount);

            $enrollment = $this->enrollment_id ? Enrollment::find($this->enrollment_id) : null;
            [$paymentStudentId, $paymentEnrollmentId] = $this->resolvePaymentStudentAndEnrollment($enrollment);

            $payment = Payment::create([
                'student_id' => $paymentStudentId,
                'enrollment_id' => $paymentEnrollmentId,
                'amount' => $amount,
                'unallocated_balance' => $amount,
                'method' => $this->payment_method,
                'payment_method' => $this->payment_method,
                'status' => 'completed',
                'reference' => $this->reference,
                'payment_date' => $this->paid_at ?: now()->toDateString(),
                'paid_at' => $this->paid_at ?: now(),
                'payer' => $this->payer,
            ]);

            $this->applyPaymentAllocationRows($payment->fresh());

            // Auto-allocate any remaining unallocated balance using priority
            // order — only when the admin has opted in. Otherwise the
            // remainder genuinely stays unallocated, matching what the
            // modal's own help text already promises.
            if ($this->autoAllocateRemaining) {
                $freshPayment = $payment->fresh();
                if ((float) $freshPayment->unallocated_balance > 0) {
                    app(PaymentPostingService::class)->allocateExistingPayment($freshPayment);
                }
            }

            DB::commit();

            $this->resetForm();
            $this->resetPage();
            $this->dispatch('hide-payment-modal');

            LivewireAlert::text('Payment added successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error adding payment', [
                'message' => $e->getMessage(),
            ]);

            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function editPayment($id)
    {
        $payment = Payment::with(['enrollment.student', 'enrollment.course', 'allocations.studentFeeItem'])->findOrFail($id);

        $this->editId = $payment->id;
        $this->enrollment_id = $payment->enrollment_id;
        $this->amount = $payment->amount;
        $this->status = $payment->status;
        $this->payment_method = $payment->method ?? $payment->payment_method;
        $this->payment_reason = $payment->payment_reason;
        $this->reference = $payment->reference;
        $this->paid_at = optional($payment->payment_date ?? $payment->paid_at)->format('Y-m-d');
        $this->payer = $payment->payer;

        $this->student_search = $payment->enrollment ? trim(($payment->enrollment->student?->first_name ?? '') . ' ' . ($payment->enrollment->student?->last_name ?? '') . ' - ' . ($payment->enrollment->course?->title ?? '')) : '';

        $this->paymentAllocationRows = $payment->allocations
            ->map(function ($allocation) {
                $item = $allocation->studentFeeItem;

                return [
                    'student_id' => $item?->student_id ?? '',
                    'enrollment_id' => $item?->enrollment_id ?? '',
                    'student_fee_item_id' => $item?->id ?? '',
                    'amount' => (float) $allocation->amount_allocated,
                ];
            })
            ->values()
            ->toArray();

        if (empty($this->paymentAllocationRows)) {
            $this->addPaymentAllocationRow();
        }

        $this->dispatch('show-payment-modal');
    }

    public function updatePayment()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $amount = (float) $this->amount;

            $payment = Payment::query()->where('id', $this->editId)->lockForUpdate()->firstOrFail();

            $this->reversePaymentAllocations($payment);

            $this->validatePaymentAllocationRows($amount);

            $enrollment = $this->enrollment_id ? Enrollment::find($this->enrollment_id) : null;
            [$paymentStudentId, $paymentEnrollmentId] = $this->resolvePaymentStudentAndEnrollment($enrollment);

            $payment->update([
                'student_id' => $paymentStudentId,
                'enrollment_id' => $paymentEnrollmentId,
                'amount' => $amount,
                'unallocated_balance' => $amount,
                'method' => $this->payment_method,
                'payment_method' => $this->payment_method,
                'reference' => $this->reference,
                'status' => 'completed',
                'payment_date' => $this->paid_at ?: now()->toDateString(),
                'paid_at' => $this->paid_at ?: now(),
                'payer' => $this->payer,
            ]);

            $this->applyPaymentAllocationRows($payment->fresh());

            // Auto-allocate any remaining unallocated balance using priority
            // order — only when the admin has opted in. Otherwise the
            // remainder genuinely stays unallocated, matching what the
            // modal's own help text already promises.
            if ($this->autoAllocateRemaining) {
                $freshPayment = $payment->fresh();
                if ((float) $freshPayment->unallocated_balance > 0) {
                    app(PaymentPostingService::class)->allocateExistingPayment($freshPayment);
                }
            }

            DB::commit();

            $this->resetForm();
            $this->resetPage();
            $this->dispatch('hide-payment-modal');

            LivewireAlert::text('Payment updated successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            DB::rollBack();

            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function viewAllocation(int $paymentId): void
    {
        $payment = Payment::with(['enrollment.student', 'enrollment.course', 'allocations.studentFeeItem.student', 'allocations.studentFeeItem.enrollment.course'])->findOrFail($paymentId);

        $allocated = $payment->allocations->sum('amount_allocated');

        $this->selectedPayment = [
            'id' => $payment->id,
            'receipt_no' => $payment->receipt_no ?? ($payment->transaction_id ?? 'N/A'),
            'reference' => $payment->reference ?? 'N/A',
            'amount' => (float) $payment->amount,
            'allocated' => (float) $allocated,
            'unallocated' => (float) ($payment->unallocated_balance ?? (float) $payment->amount - (float) $allocated),
            'method' => $payment->method ?? ($payment->payment_method ?? 'N/A'),
            'payment_date' => optional($payment->payment_date ?? $payment->paid_at)->format('d M Y'),
            'payer' => $payment->payer ?? 'N/A',
            'student' => trim(($payment->enrollment?->student?->first_name ?? '') . ' ' . ($payment->enrollment?->student?->last_name ?? '')) ?: 'Multiple / Unmapped',
            'course' => $payment->enrollment?->course?->title ?? 'Multiple / Unmapped',
        ];

        $this->allocationRows = $payment->allocations
            ->map(function ($allocation) {
                $item = $allocation->studentFeeItem;

                return [
                    'fee_item_id' => $allocation->student_fee_item_id,
                    'student' => trim(($item?->student?->first_name ?? '') . ' ' . ($item?->student?->last_name ?? '')),
                    'course' => $item?->enrollment?->course?->title ?? '—',
                    'description' => $item?->description ?? 'Fee Item',
                    'amount_allocated' => (float) $allocation->amount_allocated,
                    'fee_amount' => (float) ($item?->amount ?? 0),
                    'fee_paid' => (float) ($item?->amount_paid ?? 0),
                    'fee_balance' => (float) ($item?->balance ?? 0),
                ];
            })
            ->toArray();

        $this->unallocatedAmount = (float) $this->selectedPayment['unallocated'];
        $this->dispatch('show-allocation-modal');
    }

    public function deletePayment($id)
    {
        try {
            DB::beginTransaction();

            $payment = Payment::with('allocations.studentFeeItem')->findOrFail($id);

            if ($payment->allocations()->exists()) {
                throw new \RuntimeException('Cannot delete payment with allocations. Edit it and clear allocations first.');
            }

            $payment->delete();

            DB::commit();

            $this->resetPage();

            LivewireAlert::text('Payment deleted successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            DB::rollBack();

            LivewireAlert::text($th->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function deleteSelected()
    {
        try {
            DB::beginTransaction();

            $paymentsWithAllocations = Payment::query()->whereIn('id', $this->selected)->whereHas('allocations')->count();

            if ($paymentsWithAllocations > 0) {
                throw new \RuntimeException('Some selected payments have allocations and cannot be deleted.');
            }

            Payment::whereIn('id', $this->selected)->delete();

            DB::commit();

            $this->selected = [];
            $this->selectAll = false;
            $this->resetPage();

            LivewireAlert::text('Payments deleted successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $th) {
            DB::rollBack();

            LivewireAlert::text($th->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    private function resetForm()
    {
        $this->enrollment_id = null;
        $this->student_search = '';
        $this->amount = null;
        $this->payment_method = null;
        $this->reference = null;
        $this->paid_at = now()->toDateString();
        $this->payer = null;
        $this->editId = null;
        $this->paymentAllocationRows = [];
        $this->autoAllocateRemaining = true;
    }

    #[On('select-all')]
    public function selectAll()
    {
        if ($this->selectAll) {
            $currentPagePaymentIds = Payment::query()->latest()->paginate($this->perPage)->pluck('id')->map(fn($id) => (string) $id)->toArray();

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
        return redirect()->to(route('payments.export.pdf'));
    }
};

?>

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
        #addPaymentModal .modal-content {
    max-height: 90vh;
}

#addPaymentModal .modal-body {
    overflow-y: auto;
}
    </style>
@endpush

<div class="row">
    <div class="col-12">
        <div class="widget-content searchable-container list">
            <div class="card card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Search</label>
                        <input wire:keyup.debounce.150ms="$dispatch('search')" type="text" class="form-control"
                            placeholder="Search payments..." wire:model="search" />
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted">Student</label>
                        <select class="form-select select2-searchable" data-placeholder="All students"
                            wire:model.live="studentFilter">
                            <option value="">All students</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">
                                    {{ $student->admission_number }} - {{ $student->first_name }}
                                    {{ $student->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted">Allocation</label>
                        <select class="form-select" wire:model.live="allocationFilter">
                            <option value="">All</option>
                            <option value="fully_allocated">Fully Allocated</option>
                            <option value="partial">Partially Allocated</option>
                            <option value="unallocated">Unallocated</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted">Method</label>
                        <select class="form-select" wire:model.live="methodFilter">
                            <option value="">All methods</option>
                            @foreach ($methods as $method)
                                <option value="{{ $method }}">{{ ucfirst($method) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-1 text-end">
                        @can('create-payments')
                            <button type="button" wire:click="openCreatePaymentModal" class="btn btn-primary w-100">
                                Add
                            </button>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="card border-0 bg-primary-subtle">
                        <div class="card-body">
                            <div class="small text-primary">Payments</div>
                            <div class="fs-5 fw-bold text-primary">{{ number_format($summaryCount) }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <div class="small text-muted">Total Received</div>
                            <div class="fs-5 fw-bold">KES {{ number_format($summaryAmount, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 bg-success-subtle">
                        <div class="card-body">
                            <div class="small text-success">Allocated</div>
                            <div class="fs-5 fw-bold text-success">KES {{ number_format($summaryAllocated, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 bg-danger-subtle">
                        <div class="card-body">
                            <div class="small text-danger">Unallocated</div>
                            <div class="fs-5 fw-bold text-danger">KES {{ number_format($summaryUnallocated, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PAYMENT MODAL --}}
            <div class="modal fade" id="addPaymentModal" tabindex="-1" wire:ignore.self>
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-semibold">
                                    {{ $editId ? 'Edit Payment & Allocations' : 'Create Payment & Allocate' }}
                                </h5>
                                <small class="text-muted">
                                    Select payment details, then choose the fee items this payment should cover.
                                </small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <form wire:submit.prevent="savePayment">
                            <div class="modal-body">
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6 position-relative" x-data="{ open: false, studentSearch: @entangle('student_search').defer }"
                                        @click.away="open = false">

                                        <label class="form-label">Default Student / Enrollment</label>

                                        <input type="text" class="form-control" autocomplete="off"
                                            placeholder="Search student, admission number, or course..."
                                            x-model="studentSearch"
                                            @input="$dispatch('perform-search', { query: studentSearch }); open = true"
                                            @focus="open = true" />

                                        <div x-show="open && studentSearch.length > 0" x-transition
                                            class="dropdown-results position-absolute bg-white rounded mt-1 border border-light shadow-lg"
                                            style="width: 95%; max-height: 220px; overflow-y: auto; z-index: 1050;">
                                            @if (!empty($student_search))
                                                @forelse ($enrollments as $enrollment)
                                                    @php
                                                        $student = $enrollment->student;
                                                        $course = $enrollment->course;
                                                        $displayText =
                                                            trim(
                                                                ($student?->first_name ?? '') .
                                                                    ' ' .
                                                                    ($student?->last_name ?? ''),
                                                            ) .
                                                            ' - ' .
                                                            ($course?->title ?? '');
                                                    @endphp

                                                    <div class="dropdown-item px-3 py-2 border-bottom small hover-bg"
                                                        @click="
                                                            $wire.selectEnrollment({{ $enrollment->id }});
                                                            studentSearch = '{{ $displayText }}';
                                                            open = false;"
                                                        style="cursor: pointer;">
                                                        <strong>{{ $student?->admission_number }} -
                                                            {{ $student?->first_name }}
                                                            {{ $student?->last_name }}</strong><br>
                                                        <span class="text-muted">{{ $course?->title }}</span>
                                                    </div>
                                                @empty
                                                    <div class="px-3 py-2 text-muted small">No results found</div>
                                                @endforelse
                                            @endif
                                        </div>

                                        <small class="text-muted">
                                            Leave empty only for rare grouped/multi-student payments.
                                        </small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Amount</label>
                                        <input type="number" step="0.01" wire:model.live="amount"
                                            class="form-control" />
                                        @error('amount')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Payment Method</label>
                                        <select wire:model="payment_method" class="form-control">
                                            <option value="">Select Payment Method</option>
                                            <option value="mpesa">M-Pesa</option>
                                            <option value="bank">Bank</option>
                                            <option value="cash">Cash</option>
                                        </select>
                                        @error('payment_method')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Reference</label>
                                        <input type="text" wire:model="reference" class="form-control" />
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Paid On</label>
                                        <input type="date" wire:model="paid_at" class="form-control" />
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Narration / Payer</label>
                                        <textarea wire:model="payer" class="form-control"></textarea>
                                    </div>
                                </div>

                                <div class="border rounded-3 p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="fw-semibold mb-1">Payment Allocations</h6>
                                            <p class="small text-muted mb-0">
                                                Add rows for the fee items this payment should cover. Any unpaid part
                                                remains unallocated.
                                            </p>
                                        </div>

                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            wire:click="addPaymentAllocationRow">
                                            Add Row
                                        </button>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" id="autoAllocateRemaining"
                                            wire:model="autoAllocateRemaining">
                                        <label class="form-check-label small" for="autoAllocateRemaining">
                                            Auto-allocate any remaining balance to other outstanding fees.
                                            Uncheck to leave the unallocated portion genuinely unallocated instead
                                            of it being swept elsewhere automatically.
                                        </label>
                                    </div>
                                    {{-- <div style="max-height:400px; overflow-y:auto;"> --}}

                                        @foreach ($paymentAllocationRows as $index => $row)
                                            @php
                                                $rowStudentId = $row['student_id'] ?? '';
                                                $rowEnrollmentId = $row['enrollment_id'] ?? '';

                                                $rowEnrollments = $allocationEnrollments->when(
                                                    $rowStudentId,
                                                    fn($items) => $items->where('student_id', (int) $rowStudentId),
                                                );

                                                $rowFeeItems = $allocationFeeItems
                                                    ->when(
                                                        $rowStudentId,
                                                        fn($items) => $items->where('student_id', (int) $rowStudentId),
                                                    )
                                                    ->when(
                                                        $rowEnrollmentId,
                                                        fn($items) => $items->where(
                                                            'enrollment_id',
                                                            (int) $rowEnrollmentId,
                                                        ),
                                                    );
                                            @endphp

                                            <div wire:key="allocation-row-{{ $index }}" class="row g-2 align-items-end mb-2">
                                                <div class="col-md-3">
                                                    <label class="form-label small">Student</label>
                                                    <select wire:key="allocation-{{ $index }}-student" class="form-select select2-searchable"
                                                        data-placeholder="Select student"
                                                        wire:model.live="paymentAllocationRows.{{ $index }}.student_id">
                                                        <option value="">Select student</option>
                                                        @foreach ($allocationStudents as $student)
                                                            <option value="{{ $student->id }}">
                                                                {{ $student->admission_number }} -
                                                                {{ $student->first_name }} {{ $student->last_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-md-3">
                                                    <label class="form-label small">Enrollment</label>
                                                    <select wire:key="allocation-{{ $index }}-enrollment" class="form-select"
                                                        wire:model.live="paymentAllocationRows.{{ $index }}.enrollment_id">
                                                        <option value="">Any enrollment</option>
                                                        @foreach ($rowEnrollments as $enrollment)
                                                            <option value="{{ $enrollment->id }}">
                                                                {{ $enrollment->course?->title }} -
                                                                {{ $enrollment->course?->level }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-md-3">
                                                    <label class="form-label small">Fee Item</label>
                                                    <select wire:key="allocation-{{ $index }}-fee-item" class="form-select"
                                                        wire:model.live="paymentAllocationRows.{{ $index }}.student_fee_item_id">
                                                        <option value="">Select fee item</option>
                                                        @foreach ($rowFeeItems as $item)
                                                            <option value="{{ $item->id }}">
                                                                {{ $item->description }} - Bal KES
                                                                {{ number_format($item->balance, 2) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div wire:key="allocation-{{ $index }}-amount-col" class="col-md-2">
                                                    <label class="form-label small">Amount</label>
                                                    <input type="number" step="0.01" min="1"
                                                        class="form-control"
                                                        wire:model="paymentAllocationRows.{{ $index }}.amount">
                                                </div>

                                                <div wire:key="allocation-{{ $index }}-remove-col" class="col-md-1">
                                                    <button type="button" class="btn btn-outline-danger w-100"
                                                        wire:click="removePaymentAllocationRow({{ $index }})">
                                                        ×
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    {{-- </div> --}}
                                    <div class="small text-muted mt-3">
                                        Total payment amount:
                                        <strong>KES {{ number_format((float) $amount, 2) }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success" wire:loading.attr="disabled">
                                    {{ $editId ? 'Save Payment' : 'Create Payment' }}
                                </button>

                                <button type="button" class="btn bg-danger-subtle text-danger"
                                    data-bs-dismiss="modal">
                                    Discard
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ALLOCATION VIEW MODAL --}}
            <div class="modal fade" id="allocationModal" tabindex="-1" wire:ignore.self>
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-semibold">Payment Allocation</h5>
                                <small class="text-muted">View allocation breakdown.</small>
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

                                <div class="table-responsive mb-4">
                                    <table class="table align-middle">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Course</th>
                                                <th>Fee Item</th>
                                                <th class="text-end">Allocated</th>
                                                <th class="text-end">Fee Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($allocationRows as $row)
                                                <tr>
                                                    <td>{{ $row['student'] ?: '—' }}</td>
                                                    <td>{{ $row['course'] }}</td>
                                                    <td>{{ $row['description'] }}</td>
                                                    <td class="text-end text-success">
                                                        KES {{ number_format($row['amount_allocated'], 2) }}
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
                            @endif
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PAYMENTS TABLE --}}
            <div class="card card-body">
                <div class="table-responsive">
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

                        <h6 class="mb-0 fw-semibold text-primary">Payments List</h6>

                        <div class="d-flex gap-2 flex-wrap">
                            <button wire:click="exportExcel" class="btn btn-outline-success btn-sm">
                                Excel
                            </button>

                            <button wire:click="exportPdf" class="btn btn-outline-danger btn-sm">
                                PDF
                            </button>
                        </div>
                    </div>

                    <table class="table search-table align-middle text-nowrap">
                        <thead>
                            <tr>
                                <th>
                                    <input wire:click="$dispatch('select-all')" type="checkbox"
                                        class="form-check-input" wire:model="selectAll" />
                                </th>
                                <th>#</th>
                                <th>Receipt</th>
                                <th>Student</th>
                                <th>Course/Ref</th>
                                <th class="text-end">Amount</th>
                                <th>Allocation</th>
                                <th>Method</th>
                                <th>Paid On</th>
                                <th>Narration</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($payments as $payment)
                                @php
                                    $allocated = $payment->allocations->sum('amount_allocated');
                                    $unallocated =
                                        $payment->unallocated_balance ?? (float) $payment->amount - (float) $allocated;
                                    $student = $payment->enrollment?->student;
                                    $course = $payment->enrollment?->course;
                                @endphp

                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input" wire:model="selected"
                                            value="{{ (string) $payment->id }}" />
                                    </td>

                                    <td>{{ $loop->iteration + ($payments->currentPage() - 1) * $payments->perPage() }}
                                    </td>

                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ $payment->receipt_no ?? ($payment->transaction_id ?? 'N/A') }}
                                        </span>
                                    </td>

                                    <td>
                                        @if ($student)
                                            <a href="{{ route('students.view', $student->id) }}">
                                                {{ $student->first_name }} {{ $student->last_name }}
                                            </a>
                                            <div class="small text-muted">{{ $student->admission_number }}</div>
                                        @else
                                            <span class="text-muted">Multiple / Unmapped</span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $course?->title ?? ($payment->reference ?? 'N/A') }}
                                        @if ($course?->level)
                                            <div class="small text-muted">{{ $course->level }}</div>
                                        @endif
                                    </td>

                                    <td class="text-end fw-semibold text-primary">
                                        KES {{ number_format($payment->amount, 2) }}
                                    </td>

                                    <td>
                                        @if ($unallocated <= 0)
                                            <span class="badge bg-success-subtle text-success">Fully Allocated</span>
                                        @elseif($allocated > 0)
                                            <span class="badge bg-warning-subtle text-warning">Partial</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Unallocated</span>
                                        @endif

                                        <div class="small text-muted">
                                            Unalloc: KES {{ number_format($unallocated, 2) }}
                                        </div>
                                    </td>

                                    <td>{{ ucfirst($payment->method ?? ($payment->payment_method ?? 'N/A')) }}</td>
                                    <td>{{ optional($payment->payment_date ?? $payment->paid_at)->format('d/m/y') }}
                                    </td>
                                    <td>{{ $payment->payer }}</td>

                                    <td>
                                        <div class="action-btn">
                                            <a href="javascript:void(0)"
                                                wire:click="editPayment({{ $payment->id }})" title="Edit">
                                                <i class="ti ti-pencil fs-5"></i>
                                            </a>

                                            <a href="javascript:void(0)"
                                                wire:click="viewAllocation({{ $payment->id }})"
                                                style="color: #28a745; margin-left: 10px;" title="Allocation">
                                                <i class="ti ti-list-details fs-5"></i>
                                            </a>
                                            <a href="{{ route('payments.receipt', $payment->id) }}" target="_blank"
                                                style="color: #0d6efd; margin-left: 10px;" title="Print Receipt">
                                                <i class="ti ti-printer fs-5"></i>
                                            </a>

                                            <a href="javascript:void(0)"
                                                onclick="confirm('Delete this payment?') || event.stopImmediatePropagation()"
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
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addPaymentModal')).show();
            setTimeout(() => window.initPaymentSelect2(document.getElementById('addPaymentModal')), 150);
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

        // Searchable (Select2) student/enrollment/fee-item dropdowns.
        //
        // These selects keep their normal wire:model.live bindings — Select2
        // is purely a visual layer on top, never wire:ignore'd, because the
        // Enrollment/Fee Item options for a given allocation row are
        // re-rendered by the server every time that row's Student (or
        // Enrollment) changes. Select2 doesn't watch a <select> for
        // added/removed <option>s once initialized, so we destroy and
        // re-init any decorated select whenever the modal's DOM changes —
        // cheap for a handful of rows, and guarantees the dropdown always
        // reflects whatever Livewire just wrote into the DOM (including
        // which option is pre-selected, e.g. the new-row student auto-fill).
        // Select2 fires a native `change` event on the underlying <select>
        // when a value is picked via its UI, which wire:model.live already
        // listens for — no custom event bridge needed.
        //
        // A MutationObserver (not a Livewire JS hook) drives the re-init so
        // this doesn't depend on a specific Livewire internal API version.
        window.initPaymentSelect2 = function (context) {
            context = context || document;
            const $ = window.jQuery;

            if (!$ || !$.fn || !$.fn.select2) {
                console.error('[payments] jQuery/Select2 not available yet — dropdowns will stay plain selects.');
                return;
            }

            // Select2 init mutates the DOM (hides the <select>, injects its
            // own widget) — disconnect first so the observer below doesn't
            // see that as "Livewire changed something" and re-trigger itself.
            window.__paymentSelect2Observer?.disconnect();

            $(context).find('select.select2-searchable').each(function () {
                const $el = $(this);

                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }

                $el.select2({
                    width: '100%',
                    dropdownParent: $el.closest('.modal').length ? $el.closest('.modal') : $(document.body),
                    placeholder: $el.data('placeholder') || 'Search...',
                    allowClear: true,
                });
            });

            if (window.__paymentSelect2Observer && window.__paymentSelect2Target) {
                window.__paymentSelect2Observer.observe(window.__paymentSelect2Target, { childList: true, subtree: true });
            }
        };

        function bootPaymentSelect2() {
            window.initPaymentSelect2();

            const modal = document.getElementById('addPaymentModal');

            if (!modal || !window.MutationObserver) {
                return;
            }

            let debounce = null;

            window.__paymentSelect2Target = modal;
            window.__paymentSelect2Observer = new MutationObserver((mutations) => {
                // Select2 itself mutates the DOM inside this modal (its
                // results dropdown is appended here so it positions/z-indexes
                // correctly over a Bootstrap modal). Without this filter,
                // opening the dropdown would immediately trigger our own
                // destroy+rebuild below, closing it again instantly. Only
                // react to mutations that touch something NOT select2-owned.
                const isRealChange = mutations.some((m) =>
                    [...m.addedNodes, ...m.removedNodes].some((n) => {
                        if (n.nodeType !== 1) return false;
                        const cls = String(n.className || '');
                        return !cls.includes('select2');
                    })
                );

                if (!isRealChange) return;

                clearTimeout(debounce);
                debounce = setTimeout(() => window.initPaymentSelect2(modal), 60);
            });
            window.__paymentSelect2Observer.observe(modal, { childList: true, subtree: true });
        }

        // 'livewire:navigated' fires both on the initial page load AND after
        // every subsequent wire:navigate transition — unlike
        // 'DOMContentLoaded', which only ever fires once per browser tab and
        // would silently never run this setup if the Payments page was
        // reached via a wire:navigate link rather than a full page load.
        document.addEventListener('livewire:navigated', bootPaymentSelect2);
        if (document.readyState !== 'loading') {
            bootPaymentSelect2();
        } else {
            document.addEventListener('DOMContentLoaded', bootPaymentSelect2);
        }
    </script>
@endpush
