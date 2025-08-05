<?php

use Livewire\Volt\Component;

new class extends Component {

    public $payments;

    public function mount()
    {
        $this->loadPayments();
    }

    public function loadPayments()
    {
        $student = auth()->user()->student;

        if (!$student) {
            $this->payments = collect(); // empty collection if no student linked
            return;
        }

        $this->payments = $student->payments()->with('enrollment.course')->latest()->get();
    }

}; ?>

<div class="row">
    <div class="col-12">
        <div class="card card-body">
            <h4 class="mb-4">My Payments</h4>

            <div class="table-responsive">
                <table class="table table-bordered align-middle text-nowrap">
                    <thead class="table-light">
                    <tr>
                        <th>Course Title</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Paid On</th>
                        <th>Reference</th>
                        <th>View</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->enrollment->course->title ?? 'N/A' }}</td>
                            <td>${{ number_format($payment->amount, 2) }}</td>
                            <td>{{ ucfirst($payment->method) }}</td>
                            <td>{{ \Carbon\Carbon::parse($payment->paid_at)->format('d M Y') }}</td>
                            <td>{{ $payment->reference }}</td>
                            <td>
                                <a href="javascript:void(0)" wire:click="viewPayment({{ $payment->id }})" class="btn btn-sm btn-primary">
                                    <i class="ti ti-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No payments found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>





