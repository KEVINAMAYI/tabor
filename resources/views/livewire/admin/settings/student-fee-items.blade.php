<?php

use App\Models\StudentFeeItem;
use App\Models\Student;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Services\PaymentPostingService;
use App\Models\Payment;

new class extends Component {
    use WithPagination;
    public $studentFilter = '';
    public $statusFilter = '';
    public $perPage = 10;

    /* public function mount()
    {
        $payments = Payment::query()->orderBy('created_at')->orderBy('id')->get();
        // dd($payments->count() . ' payments to backfill');

        foreach ($payments as $payment) {
            app(PaymentPostingService::class)->allocateExistingPayment($payment);
        }
        dd('Backfill complete');
    } */
    public function with()
    {
        return [
            'feeItems' => StudentFeeItem::query()
                ->with(['student', 'enrollment.course', 'trimester', 'feeDefinition'])
                ->when(filled($this->studentFilter), fn($q) => $q->where('student_id', $this->studentFilter))
                ->when(filled($this->statusFilter), fn($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate($this->perPage),
            'students' => Student::orderBy('first_name')->get(),
        ];
    }
};

?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex justify-content-center align-items-center mb-4">
            <div>
                <h4 class="fw-semibold mb-1">Student Fee Items</h4>
                <p class="text-muted small mb-0">Posted charges for students.</p>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label small text-muted">Filter by Student</label>
                <select class="form-select" wire:model.live="studentFilter">
                    <option value="">All students</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small text-muted">Filter by Status</label>
                <select class="form-select" wire:model.live="statusFilter">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>
        </div>

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
            </div>
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Fee</th>
                        <th>Trimester</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feeItems as $item)
                        <tr>
                            <td>{{ $loop->iteration + ($feeItems->currentPage() - 1) * $feeItems->perPage() }}</td>
                            <td>{{ $item->student->first_name . ' ' . $item->student->last_name }}</td>
                            <td>{{ $item->enrollment?->course?->title }} - {{ $item->enrollment?->course?->level }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->trimester?->name ?? '—' }}</td>
                            <td>KES {{ number_format($item->amount, 2) }}</td>
                            <td>KES {{ number_format($item->amount_paid, 2) }}</td>
                            <td>KES {{ number_format($item->balance, 2) }}</td>
                            <td>{{ ucfirst($item->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No student fee items found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="d-flex justify-content-start align-items-center mt-3">
                {{ $feeItems->links() }}
            </div>
        </div>
    </div>
</div>
