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
        $this->courses = Course::with('category')->orderBy('course_category_id')->get();
        $this->courseCount = $this->courses->count();
    }
}; ?>

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
            background-color: #f79020 !important;
            /* Match Apply Now */
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
                <h4 class="fs-10 fw-bolder ">
                    Our Courses
                </h4>
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
            <div class="row">
                <!-- Check if there are any courses -->
                @if ($courses->isEmpty())
                    <div class="col-12">
                        <div class="alert alert-info text-center py-5">
                            <h3 class="fw-bold">Currently, there are no courses available.</h3>
                            <p class="fs-5">
                                We are constantly adding new courses. Please check back later or <a
                                    href="{{ route('front-end.contact') }}" class="text-primary">contact us</a> if you
                                have any questions.
                            </p>
                        </div>
                    </div>
                @else
                    <!-- Course 1 -->
                    @foreach ($courses as $course)
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card rounded-3 overflow-hidden h-100">
                                <a href="#" class="position-relative">
                                    <img height="50%" width="50%"
                                    src="{{ $course->image_url ? asset('storage/' . $course->image_url) : asset('assets/images/frontend-pages/blog-3.jpg') }}"
                                        alt="{{ $course->title }}" class="w-100 img-fluid" />
                                </a>
                                <div class="mt-7 px-7 pb-7 h-100">
                                    <div class="d-flex gap-3 flex-column h-100 justify-content-between">
                                        <a href="#" class="fs-5 fw-bolder">{{ $course->title }}
                                            {{ $course->level ? ' - ' . $course->level : '' }} </a>
                                        <p>{{ 'Category: ' . ($course->category?->name ?? 'Uncategorized') }}</p>
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2 d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:calendar-clock"
                                                    class="text-primary fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">Duration:
                                                    {{ $course->duration ?? 'N/A' }}</span>
                                            </li>
                                            <li class="mb-2 d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:laptop"
                                                    class="text-success fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">Mode:
                                                    {{ ucfirst($course->mode) ?? 'N/A' }}</span>
                                            </li>
                                            <li class="mb-2 d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:school-outline"
                                                    class="text-warning fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">Level: {{ $course->level ?? 'N/A' }}</span>
                                            </li>
                                            <li class="d-flex align-items-start gap-2">
                                                <iconify-icon icon="mdi:certificate-outline"
                                                    class="text-info fs-4 mt-1"></iconify-icon>
                                                <span class="text-dark fs-3">Certification:
                                                    {{ $course->certification ?? 'N/A' }}</span>
                                            </li>
                                        </ul>
                                        <div class="mt-0" id="details-{{ $course->id }}">
                                            <ol class="list-unstyled">
                                                <p class="mb-1 text-primary"><strong>Prerequisites:</strong></p>
                                                @foreach (explode("\n", $course->prerequisites ?? '') as $prerequisite)
                                                    @if (trim($prerequisite) !== '')
                                                        <li class="d-flex align-items-start gap-2">
                                                            <i class="ti ti-check text-success mt-1"></i>
                                                            <span>{{ $prerequisite }}</span>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ol>
                                        </div>

                                        <div>
                                            <a class="btn btn-primary d-block w-100 mb-3"
                                                href="{{ route('front-end.course-application', ['course_id' => $course->id]) }}">Apply
                                                Now</a>
                                            @if ($course->brochure_url)
                                                <a href="{{ asset('storage/' . $course->brochure_url) }}"
                                                    class="btn btn-outline-primary d-block w-100 mb-3" target="_blank">
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
    @include('livewire.front-end.common.focus')
    <!-- ------------------------------------- -->
    <!-- Focus End -->
    <!-- ------------------------------------- -->

</div>
