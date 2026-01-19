<?php

use Livewire\Volt\Component;
use App\Models\Enrollment;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use App\Models\EnrollmentTrimester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;

new class extends Component {
    public $enrollment_id;
    public $enrollment;
    public $trimesterFinancials = [];

    public function mount($enrollment_id)
    {
        $this->enrollment_id = $enrollment_id;
        $this->enrollment = Enrollment::with(['student', 'course', 'intake', 'trimesters.payments', 'payments'])->findorFail($enrollment_id);
        $this->calculateTrimesterFinancials();
    }

    public function updateTrimesterEnrollmentStatus($id)
    {
        try {
            DB::beginTransaction();
            $enrollmentTrimester = EnrollmentTrimester::lockForUpdate()->findOrFail($id);
            $enrollmentTrimester->update([
                'status' => 'completed',
                'end_date' => now(),
            ]);

            //Activate next pending trimester (if any)
            $nextTrimester = EnrollmentTrimester::where('enrollment_id', $enrollmentTrimester->enrollment_id)->where('status', 'pending')->orderBy('trimester_number')->first();

            if ($nextTrimester) {
                $nextTrimester->update([
                    'status' => 'in-progress',
                ]);
            }
            DB::commit();
            LivewireAlert::text('Status updated successfully!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update status: ' . $e->getMessage());
            LivewireAlert::text('Failed to update status!')->error()->toast()->position('top-end')->show();
        }
    }

    private function calculateTrimesterFinancials(): void
    {
        // Total tuition paid for this enrollment
        $totalPaid = Payment::where('enrollment_id', $this->enrollment_id)
            ->where('payment_reason', 'tuition') // or reference = 'tuition'
            ->sum('amount');

        $runningPaid = $totalPaid;

        // 2️⃣ Go in order of trimester_number
        foreach ($this->enrollment->trimesters as $trimester) {
            $fee = $trimester->fee_amount;

            $paidForThisTrimester = min($fee, max(0, $runningPaid));
            $balance = max(0, $fee - $paidForThisTrimester);

            $this->trimesterFinancials[$trimester->id] = [
                'paid' => $paidForThisTrimester,
                'balance' => $balance,
                'is_settled' => $balance === 0,
            ];

            $runningPaid -= $paidForThisTrimester;
        }
    }
}; ?>

<div class="col-12">

    <div class="card overflow-hidden">
        <div class="card-body p-0">
            <div class="row align-items-center">
                <div class="col-lg-4 mt-3">
                    <div class="d-flex align-items-center justify-content-around m-4">
                        <div class="text-center">
                            <p class="text-center">Student</p>
                            <h5 class="mb-0">
                                {{ $this->enrollment->student->first_name . ' ' . $this->enrollment->student->last_name }}
                            </h5>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex align-items-center justify-content-around m-4">
                        <div class="text-bold">
                            <p class="text-center">Course</p>
                            <h5 class="mb-0 ">{{ $this->enrollment->course->title }}</h5>
                            <h5 class="mb-0 text-center">{{ $this->enrollment->course->level }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex align-items-center justify-content-around m-4">
                        <div class="text-bold">
                            <p class="text-center">Intake</p>
                            <h5 class="mb-0 ">{{ $this->enrollment->intake->name }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h4>Trimesters</h4>
    <div class="card card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Trimester</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Academic Status</th>
                        <th>Fee Amount</th>
                        <th>Paid Amount</th>
                        <th>Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollment->trimesters as $trimester)
                        @php
                            $finance = $trimesterFinancials[$trimester->id] ?? [
                                'paid' => 0,
                                'balance' => $trimester->fee_amount,
                                'is_settled' => false,
                            ];
                        @endphp

                        <tr>
                            <td>{{ $trimester->trimester_number }}</td>
                            <td>{{ Date('d/m/Y', strtotime($trimester->start_date)) }}</td>
                            <td>{{ Date('d/m/Y', strtotime($trimester->end_date)) }}</td>
                            <td>
                                <span
                                    class="badge
                            {{ $trimester->status === 'completed'
                                ? 'bg-success'
                                : ($trimester->status === 'in-progress'
                                    ? 'bg-secondary'
                                    : 'bg-warning') }}">
                                    {{ ucfirst($trimester->status) }}
                                </span>
                            </td>
                            <td>{{ number_format($trimester->fee_amount,2) }}</td>
                            <td>
                                <span class="{{ $finance['paid'] > 0 ? 'text-success' : '' }}">
                                    {{ number_format($finance['paid'], 2) }}
                                </span>
                            </td>
                            <td>
                                <span
                                    class="{{ $finance['balance'] > 0 && ($trimester->status == 'in-progress' || $trimester->status == 'completed') ? 'text-danger' : '' }}">
                                    {{ number_format($finance['balance'], 2) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">

                                    {{-- Mark Complete (ACADEMIC) --}}
                                    @if ($trimester->status === 'in-progress')
                                        <button class="btn btn-sm btn-primary"
                                            wire:click="updateTrimesterEnrollmentStatus({{ $trimester->id }})">
                                            Mark Complete
                                        </button>
                                    @endif

                                    {{-- Pay Balance (FINANCIAL) --}}
                                    @if ($trimester->status != 'pending' && $finance['balance'] > 0)
                                        <button class="btn btn-sm btn-success"
                                            wire:click="editTrimester({{ $trimester->id }})">
                                            Pay Balance
                                        </button>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                No records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <h4>Payment Transactions</h4>
    <div class="card card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transaction ID</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Paid By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollment->payments as $transaction)
                        <tr>
                            <td>{{ optional($transaction->created_at)->format('d/m/Y') ?? 'N/A' }}</td>
                            <td>{{ $transaction->transaction_id ?? 'N/A' }}</td>
                            <td>{{ number_format($transaction->amount ?? 0, 2) }}</td>
                            <td>{{ $transaction->payment_method ?? 'N/A' }}</td>
                            <td>{{ $transaction->reference ?? 'N/A' }}</td>
                            <td>{{ $transaction->payer ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex justify-content-start mt-3">
        <a href="{{ route('students.view', $enrollment->student_id) }}" class="btn btn-primary">
            <i class="ti ti-arrow-left"></i> Back
        </a>
    </div>
</div>
