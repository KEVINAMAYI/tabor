<?php

use Livewire\Volt\Component;

new class extends Component {

    public $enrollments;

    public function mount()
    {
        $this->enrollments = auth()->user()?->student
            ? auth()->user()->student->enrollments()->with(['course', 'intake'])->latest()->get()
            : collect();
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
                        <th>Description</th>
                        <th>Payment</th>
                        <th>Intake</th>
                        <th>Status</th>
                        <th>View</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($enrollments as $enrollment)
                        <tr>
                            <td>{{ $enrollment->course->title }}</td>
                            <td>{{ Str::limit($enrollment->course->description, 50) }}</td>
                            <td>${{ number_format($enrollment->course->payment, 2) }}</td>
                            <td>{{ $enrollment->intake->name }}</td>
                            <td>
                                    <span class="badge
                                        @if($enrollment->status == 'approved') bg-success
                                        @elseif($enrollment->status == 'rejected') bg-danger
                                        @else bg-warning
                                        @endif">
                                        {{ ucfirst($enrollment->status) }}
                                    </span>
                            </td>
                            <td>
                                <a href="{{ route('student.enrollments.view', $enrollment->course->id ) }}" class="btn btn-sm btn-primary">
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
</div>


@push('scripts')
    <script src="assets/js/apps/contact.js"></script>
@endpush




