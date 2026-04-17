<?php

use App\Models\StudentFeeItem;
use Livewire\Volt\Component;

new class extends Component {
    public $studentFilter = '';
    public $statusFilter = '';

    public function with()
    {
        return [
            'feeItems' => StudentFeeItem::query()
                ->with(['student', 'enrollment.course', 'trimester', 'feeDefinition'])
                ->when(filled($this->studentFilter), fn($q) =>
                    $q->where('student_id', $this->studentFilter)
                )
                ->when(filled($this->statusFilter), fn($q) =>
                    $q->where('status', $this->statusFilter)
                )
                ->latest()
                ->get(),
        ];
    }
};

?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-semibold mb-1">Student Fee Items</h4>
                <p class="text-muted small mb-0">Posted charges for students.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
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
                        <td>{{ $item->student->name ?? '—' }}</td>
                        <td>{{ $item->enrollment?->course?->name ?? '—' }}</td>
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
        </div>
    </div>
</div>