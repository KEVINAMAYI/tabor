<?php

use Livewire\Volt\Component;
use App\Http\Controllers\MpesaApi;

new class extends Component {

    public $enrollments;
    public $amount;
    public $phone_number;
    public $enrollment;

    public function mount()
    {
        $this->enrollments = auth()->user()?->student
            ? auth()->user()->student->enrollments()->with(['course', 'intake','payments'])->latest()->get()
            : collect();
    }

    public function payEnrollment($enrollmentId)
    {
        $enrollment = auth()->user()?->student
            ? auth()->user()->student->enrollments()->where('id', $enrollmentId)->first()
            : null;
        $this->enrollment = $enrollment;

        if ($enrollment) {
            $this->amount = $enrollment->course->price - $enrollment->payments->sum('amount');
            $this->dispatch('show-payment-modal');
        }
    }

    public function processPayment()
    {
        // Validate input
        $this->validate([
            'amount' => 'required|numeric|min:1',
            'phone_number' => 'required|string',
        ]);

        // Process payment logic here (e.g., integrate with payment gateway)
        $payment = MpesaApi::initiateStk($this->enrollment, $this->amount, $this->phone_number);
        // Close the modal after processing
        $this->dispatch('hide-payment-modal');
    }

}; ?>

<div class="row">
    <div class="col-12">
        <div class="card card-body">
            <h4 class="mb-4">My Enrollments</h4>

            <div class="table-responsive">
                <table class="table table-bordered align-middle text-nowrap">
                    <thead class="table-light">
                    <tr>
                        <th>Course Title</th>
                        <th>Intake</th>
                        <th>Tuition Fee (KES)</th>
                        <th>Balance (KES)</th>
                        <th>Status</th>
                        <th>View</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($enrollments as $enrollment)
                        <tr>
                            <td>{{ $enrollment->course->title }}</td>
                            <td>{{ $enrollment->currentTrimesterLabel() }}</td>
                            <td>{{ number_format($enrollment->payments->sum('amount'), 2) }}</td>
                            @php
                                $totalPaid = $enrollment->payments->sum('amount');
                                $courseFee = $enrollment->course->price;
                                $balance = $courseFee - $totalPaid;
                            @endphp
                            <td class="{{ $balance > 0 && $enrollment->status == 'active' ? 'text-danger' : '' }}">
                                {{ number_format($balance, 2) }}
                                @if ($balance > 0 && $enrollment->status == 'active')
                                    <button wire:click="payEnrollment({{ $enrollment->id }})" class="float-end btn btn-success btn-sm">Pay</button>
                                @endif
                            </td>
                            <td>
                                    <span class="badge
                                        @if($enrollment->status == 'active') bg-primary
                                        @elseif(in_array($enrollment->status, ['course_completed', 'pending_graduation', 'graduated'])) bg-success
                                        @elseif($enrollment->status == 'rejected') bg-danger
                                        @elseif(in_array($enrollment->status, ['withdrawn', 'cancelled'])) bg-secondary
                                        @else bg-warning
                                        @endif">
                                        {{ ucwords(str_replace('_', ' ', $enrollment->status)) }}
                                    </span>
                            </td>
                            <td>
                                <a href="{{ route('student.enrollments.view', $enrollment->id ) }}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No enrollments found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Make Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Payment form content goes here -->
                    <form wire:submit.prevent="processPayment">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (KES)</label>
                            <input type="number" class="form-control" id="amount" placeholder="Enter amount to pay" wire:model="amount">
                            @error('amount')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone_number" placeholder="Eg: 254712345678" wire:model="phone_number">
                            @error('phone_number')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-success float-end">Initiate Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
    <script src="assets/js/apps/contact.js"></script>

    <script>
        window.addEventListener('show-payment-modal', (event) => {
            const enrollment = event.detail;
            // Show the payment modal with the enrollment details
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        });
    </script>

@endpush




