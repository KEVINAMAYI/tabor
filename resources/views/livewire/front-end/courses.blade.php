<?php

namespace App\Livewire;

use App\Models\Course;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app.frontend')] class extends Component {

    public $courses = [];
    public $courseCount;

    public function mount()
    {
        $this->courses = Course::latest()->get();
        $this->courseCount = $this->courses->count();

    }

} ?>

@push('styles')
    <style>
        /* Make tab buttons look like Apply Now when active */
        .custom-course-tabs .nav-link {
            color: black !important;
            background-color: transparent !important;
            border: 1px solid transparent !important;
            transition: all 0.2s ease !important;
            border-radius: 8px !important;
            padding: 8px 8px !important;
        }

        .custom-course-tabs .nav-link:hover {
            background-color: #f5f5f5 !important;
            color: #000 !important;
        }

        .custom-course-tabs .nav-link.active {
            background-color: #f79020 !important; /* Match Apply Now */
            color: #fff !important;
            font-weight: 600 !important;
            border-color: #f79020 !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
        }

        /* Optional spacing between tab items */
        .custom-course-tabs .nav-item {
            margin-right: 10px !important;
        }

        /* Add spacing and alignment to icon */
        .btn-outline-primary iconify-icon {
            vertical-align: middle !important;
            margin-right: 8px !important;
            font-size: 1.1rem !important;
        }
    </style>
@endpush
<div class="main-wrapper overflow-hidden">
    <!-- ------------------------------------- -->
    <!-- Banner Start -->
    <!-- ------------------------------------- -->
    <section class="py-5 bg-light-gray">
        <div class="container-fluid">
            <div class="d-flex justify-content-between flex-md-nowrap flex-wrap">
                <h2 class="fs-15 fw-bolder ">
                    Our Courses
                </h2>
                <div class="d-flex align-items-center gap-6">
                    <a href="../main/frontend-landingpage.html"
                       class="text-muted fw-bolder link-primary fs-3 text-uppercase">
                        Tabor
                    </a>
                    <iconify-icon icon="solar:alt-arrow-right-outline" class="fs-5 text-muted"></iconify-icon>
                    <a href="#" class="text-primary link-primary fw-bolder fs-3 text-uppercase">
                        Courses
                    </a>
                </div>
            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- Banner End -->
    <!-- ------------------------------------- -->

    <!-- ------------------------------------- -->
    <!-- List Start -->
    <!-- ------------------------------------- -->
    <section class="bg-light-gray pb-3 pb-md-7 pb-lg-12">
        <div class="container-fluid">
            <div class="card data-shadow rounded-3 overflow-hidden mb-7">
                <div class="row">
                    <div class="col-lg-6 order-last order-lg-first">
                        <div class="p-7 p-lg-5 flex-grow-1">
                            <div class="py-lg-4 d-flex flex-column gap-3">
                                <a href="../main/frontend-blogdetailpage.html">
                                    <h4 class="fw-bolder fs-6">
                                        Discover our comprehensive range of TVET programs designed to prepare you for
                                        successful careers in growing industries.
                                    </h4>
                                </a>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex gap-9">
                                        <div class="d-flex gap-2">
                                            <i class="ti ti-book fs-5 text-dark"></i>
                                            <p class="mb-0 fs-2 fw-semibold text-dark">{{ $courseCount }} Courses</p>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 order-first order-lg-last">
                        <div class="blog-bg d-flex flex-column justify-content-between p-9 h-100 flex-grow-1">
                            <img src="../assets/images/profile/user-6.jpg" alt="user" width="44" height="44"
                                 class="rounded-circle">
                            <div class="d-flex justify-content-end">
                                <p class="fs-2 py-1 px-2 bg-white rounded-1 fw-semibold mb-0 text-dark">2 min Read
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">

                <!-- Check if there are any courses -->
                @if ($courses->isEmpty())
                    <div class="col-12">
                        <div class="alert alert-info text-center py-5">
                            <h3 class="fw-bold">Currently, there are no courses available.</h3>
                            <p class="fs-5">
                                We are constantly adding new courses. Please check back later or <a href="{{ route('front-end.contact') }}" class="text-primary">contact us</a> if you have any questions.
                            </p>
                        </div>
                    </div>
                @else
                    <!-- Course 1 -->
                    @foreach ($courses as $course)
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card rounded-3 overflow-hidden h-100">
                                <a href="#" class="position-relative">
                                    <img
                                        src="{{ $course->image_url ? asset('storage/' . $course->image_url) : asset('assets/images/frontend-pages/blog-3.jpg') }}"
                                        alt="{{ $course->title }}"
                                        class="w-100 img-fluid"
                                    />
                                </a>
                                <div class="mt-7 px-7 pb-7 h-100">
                                    <div class="d-flex gap-3 flex-column h-100 justify-content-between">
                                        <a href="#" class="fs-5 fw-bolder">{{ $course->title }}</a>
                                        <p>{{ \Illuminate\Support\Str::limit($course->description, 100) }}</p>
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2 d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:calendar-clock" class="text-primary fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">Duration: {{ $course->duration ?? 'N/A' }}</span>
                                            </li>
                                            <li class="mb-2 d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:laptop" class="text-success fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">Mode: {{ ucfirst($course->mode) ?? 'N/A' }}</span>
                                            </li>
                                            <li class="mb-2 d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:school-outline" class="text-warning fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">Level: {{ $course->level ?? 'N/A' }}</span>
                                            </li>
                                            <li class="d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:certificate-outline" class="text-info fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">Certification: {{ $course->certification ?? 'N/A' }}</span>
                                            </li>
                                        </ul>

                                        <ul class="nav nav-pills custom-course-tabs nav-fill mt-4" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" data-bs-toggle="tab" href="#overview-{{ $course->id }}" role="tab"><span>Overview</span></a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" href="#details-{{ $course->id }}" role="tab"><span>Details</span></a>
                                            </li>
                                        </ul>

                                        <div class="tab-content mt-2">
                                            <div class="tab-pane active p-3" id="overview-{{ $course->id }}" role="tabpanel">
                                                <h5 class="fw-bold mb-3">Course Highlights:</h5>
                                                @if (!empty($course->modules))
                                                    <ul class="list-unstyled">
                                                        @foreach ($course->modules as $module)
                                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>{{ $module->title }}</span></li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <p>{{ $course->description }}</p>
                                                @endif
                                            </div>
                                            <div class="tab-pane p-3" id="details-{{ $course->id }}" role="tabpanel">
                                                <h5 class="fw-bold mb-3">Course Details:</h5>
                                                <ul class="list-unstyled">
                                                    <li class="mb-2 d-flex gap-2">
                                                        <strong>Fee:</strong><span>KES {{ number_format($course->price, 2) }}</span>
                                                    </li>
                                                    <li class="mb-2 d-flex gap-2"><strong>Next Intake:</strong><span>January 2025</span></li>
                                                    <li class="d-flex gap-2"><strong>Prerequisites:</strong><span>{{ $course->prerequisites ?? 'N/A' }}</span></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div>
                                            <a class="btn btn-primary d-block w-100 mb-3" href="#">Apply Now</a>
                                            @if($course->brochure_url)
                                                <a href="{{ asset('storage/' . $course->brochure_url) }}" class="btn btn-outline-primary d-block w-100 mb-3" target="_blank">
                                                    <i class="ti ti-download me-1"></i> Download Brochure
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif


            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- List End  -->
    <!-- ------------------------------------- -->

    <!-- ------------------------------------- -->
    <!-- Focus Start -->
    <!-- ------------------------------------- -->
    <section class="bg-primary py-lg-11 py-5 position-relative">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-9 text-center">
                    <a href="../main/frontend-landingpage.html">
                        <img width="140" height="140" src="../assets/images/logos/tabor_logo_transparent.png"
                             alt="logo">
                    </a>
                    <h4 class="fs-7 my-9 fw-bolder text-white text-center lh-sm">
                        Join thousands of successful graduates who have transformed their careers with Tabor Training
                        Institute..
                    </h4>
                    <a href="{{ route('front-end.course-application') }}" class="btn px-5 btn-outline-light">
                        Register
                    </a>
                </div>
            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- Focus End -->
    <!-- ------------------------------------- -->

</div>
