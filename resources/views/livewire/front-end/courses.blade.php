<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\CourseCategory;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app.frontend')] class extends Component {
    public $courses = [];
    public $categories = [];
    public $selectedCategory = 'all';
    public $courseCount;

    public function mount()
    {
        $this->categories = CourseCategory::all();
        // $this->courseCount = $this->courses->count();
        $this->loadCourses();
    }

    public function loadCourses()
    {
        $query = Course::with('category');

        if ($this->selectedCategory !== 'all') {
            $query->where('course_category_id', $this->selectedCategory);
        }

        $this->courses = $query->orderBy('course_category_id')->get();
        $this->courseCount = $this->courses->count();
    }

    public function selectCategory($category_id)
    {
        $this->selectedCategory = $category_id;
        $this->loadCourses();
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

    @if (session()->has('success'))
        <script>
            window.dispatchEvent(new CustomEvent('alert', {
                detail: {
                    type: 'success',
                    message: "{{ session('success') }}"
                }
            }));
        </script>
    @endif
    <!-- ------------------------------------- -->
    <!-- Banner Start -->
    <!-- ------------------------------------- -->
    {{-- <section class="py-5 bg-light-gray">

        <div class="container-fluid">
            <div class="d-flex justify-content-between flex-md-nowrap flex-wrap">
                <h6 class="fs-10 fw-bolder ">
                    Our Courses
                    </h5>
                    <div class="d-flex align-items-center gap-6">
                        <a href="{{ route('front-end.home') }}"
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
    </section> --}}
    <!-- ------------------------------------- -->
    <!-- Banner End -->
    <!-- ------------------------------------- -->

    <!-- ------------------------------------- -->
    <!-- List Start -->
    <!-- ------------------------------------- -->
    <section class="bg-light-gray pb-3 pb-md-7 pb-lg-12">
        <div class="container-fluid">
            <div class="row">
                <div class="col-3">
                    @include('livewire.front-end.filters')
                </div>
                <div class="col-9">
                    <!-- Check if there are any courses -->
                    @if ($courses->isEmpty())
                        <div class="col-12">
                            <div class="alert alert-info text-center py-5 mt-4">
                                <h3 class="fw-bold">Currently, there are no courses available under that category.</h3>
                                <p class="fs-5">
                                    We are constantly adding new courses. Please check back later or <a
                                        href="{{ route('front-end.contact') }}" class="text-primary">contact us</a>
                                    if you
                                    have any questions.
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="row mt-4">
                            @foreach ($courses as $course)
                                <div class="col-lg-4 mt-4">
                                    <div class="card rounded-3 overflow-hidden h-100">
                                        {{-- <a href="#" class="position-relative">
                                    <img height="50%" width="50%"
                                    src="{{ $course->image_url ? asset('storage/' . $course->image_url) : asset('assets/images/frontend-pages/blog-3.jpg') }}"
                                        alt="{{ $course->title }}" class="w-100 img-fluid" />
                                </a> --}}
                                        <div class="mt-7 px-7 pb-7 h-100">
                                            <div class="d-flex flex-column h-100 justify-content-between">
                                                <a href="javascript:void(0);" class="fs-5 fw-bolder">{{ $course->title }}
                                                    {{ $course->level ? ' - ' . $course->level : '' }} </a>
                                                <p class="mt-1">Category: {{ $course->category?->name ?? 'Uncategorized' }}</p>
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
                                                        <span class="text-dark fs-3">Level:
                                                            {{ $course->level ?? 'N/A' }}</span>
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
                                                        <p class="mb-1 text-primary"><strong>Prerequisites:</strong>
                                                        </p>
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
                                                            class="btn btn-outline-primary d-block w-100 mb-3"
                                                            target="_blank">
                                                            <i class="ti ti-download me-1"></i> Download Brochure
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                </div>

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

@push('scripts')
    <script>
        window.addEventListener('alert', event => {
            Swal.fire({
                toast: true,
                icon: event.detail.type,
                title: event.detail.message,
                position: 'top-end',
                timer: 4000,
                showConfirmButton: false
            });
        });
    </script>
@endpush
