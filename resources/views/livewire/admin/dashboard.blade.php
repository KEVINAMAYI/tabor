<?php

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\Lecturer;
use App\Models\Payment;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {

    public $students;
    public $courses;
    public $lecturers;
    public $total_revenue;

    public function mount()
    {
        $this->students = Enrollment::where('status', 'approved')
        ->groupBy('student_id')->count();
        $this->courses = Course::count();
        $this->lecturers = Lecturer::count();
        $this->total_revenue = Payment::sum('amount');
    }
}; ?>

<div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-4 pb-0" data-simplebar>
                    <div class="row flex-nowrap">

                        {{-- Students --}}
                        <div class="col">
                            <div class="card primary-gradient">
                                <div class="card-body text-center px-9 pb-4">
                                    <div class="d-flex align-items-center justify-content-center round-48 rounded text-bg-primary flex-shrink-0 mb-3 mx-auto">
                                        <iconify-icon icon="mdi:school" class="fs-7 text-white"></iconify-icon>
                                    </div>
                                    <h6 class="fw-normal fs-3 mb-1">Enrolled Students</h6>
                                    <h4 class="mb-3 d-flex align-items-center justify-content-center gap-1">
                                        {{ $students }}
                                    </h4>
                                    <a href="{{ route('students.enrollments') }}" class="btn btn-white fs-2 fw-semibold text-nowrap">View Details</a>
                                </div>
                            </div>
                        </div>

                        {{-- Courses --}}
                        <div class="col">
                            <div class="card warning-gradient">
                                <div class="card-body text-center px-9 pb-4">
                                    <div class="d-flex align-items-center justify-content-center round-48 rounded text-bg-warning flex-shrink-0 mb-3 mx-auto">
                                        <iconify-icon icon="mdi:book-open-page-variant" class="fs-7 text-white"></iconify-icon>
                                    </div>
                                    <h6 class="fw-normal fs-3 mb-1">Courses</h6>
                                    <h4 class="mb-3 d-flex align-items-center justify-content-center gap-1">
                                        {{ $courses }}
                                    </h4>
                                    <a href="{{ route('courses.index') }}" class="btn btn-white fs-2 fw-semibold text-nowrap">View Details</a>
                                </div>
                            </div>
                        </div>

                        {{-- Lecturers --}}
                        <div class="col">
                            <div class="card secondary-gradient">
                                <div class="card-body text-center px-9 pb-4">
                                    <div class="d-flex align-items-center justify-content-center round-48 rounded text-bg-secondary flex-shrink-0 mb-3 mx-auto">
                                        <iconify-icon icon="mdi:account-tie" class="fs-7 text-white"></iconify-icon>
                                    </div>
                                    <h6 class="fw-normal fs-3 mb-1">Lecturers</h6>
                                    <h4 class="mb-3 d-flex align-items-center justify-content-center gap-1">
                                        {{ $lecturers }}
                                    </h4>
                                    <a href="{{ route('lecturers.index') }}" class="btn btn-white fs-2 fw-semibold text-nowrap">View Details</a>
                                </div>
                            </div>
                        </div>

                        {{-- Income --}}
                        <div class="col">
                            <div class="card success-gradient">
                                <div class="card-body text-center px-9 pb-4">
                                    <div class="d-flex align-items-center justify-content-center round-48 rounded text-bg-success flex-shrink-0 mb-3 mx-auto">
                                        <iconify-icon icon="mdi:currency-usd" class="fs-7 text-white"></iconify-icon>
                                    </div>
                                    <h6 class="fw-normal fs-3 mb-1">Total Revenue</h6>
                                    <h4 class="mb-3 d-flex align-items-center justify-content-center gap-1">
                                        {{ 'Ksh ' . number_format($total_revenue, 2) }}
                                    </h4>
                                    <a href="{{ route('payments.index') }}" class="btn btn-white fs-2 fw-semibold text-nowrap">View Details</a>
                                </div>
                            </div>
                        </div>


                    </div> <!-- end row -->
                </div>
            </div>
        </div>
    </div>
