<?php

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\Student;
use Livewire\Volt\Component;

new class extends Component {
}; ?>

<div class="col-12">
    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-space-between">
                        <h4 class="mb-4 mb-sm-0 card-title">Computer Science</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="../main/index.html">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="breadcrumb-item" aria-current="page">
                        <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                          Computer Science
                        </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-body p-0">
                <img src="../assets/images/backgrounds/profilebg.jpg" alt="matdash-img" class="img-fluid">
                <div class="row align-items-center">
                    <div class="col-lg-4 order-lg-1 order-2">
                        <div class="d-flex align-items-center justify-content-around m-4">
                            <div class="text-center">
                                <i class="ti ti-file-description fs-6 d-block mb-2"></i>
                                <h4 class="mb-0 fw-semibold lh-1">6</h4>
                                <p class="mb-0 ">Modules</p>
                            </div>
                            <div class="text-center">
                                <i class="ti ti-user-circle fs-6 d-block mb-2"></i>
                                <h4 class="mb-0 fw-semibold lh-1">100</h4>
                                <p class="mb-0 ">Students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mt-n3 order-lg-2 order-1">
                        <div class="mt-n5">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <div class="d-flex align-items-center justify-content-center round-110">
                                    <div
                                        class="border border-4 border-white d-flex align-items-center justify-content-center rounded-circle overflow-hidden"
                                        style="background-color: #f0f0f0; width: 100px; height: 100px;">
                                        <i class="ti ti-book text-primary" style="font-size: 3rem !important; line-height: 1;"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center">
                                <h5 class="mb-0">Computer Science</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 order-last">
                    </div>
                </div>
                <ul class="nav nav-pills user-profile-tab justify-content-end mt-2 bg-primary-subtle rounded-2 rounded-top-0"
                    id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active hstack gap-2 rounded-0 fs-12 py-6" id="pills-modules-tab"
                                data-bs-toggle="pill" data-bs-target="#pills-modules" type="button" role="tab"
                                aria-controls="pills-modules" aria-selected="true">
                            <i class="ti ti-book fs-5"></i> <!-- Book icon for Courses -->
                            <span class="d-none d-md-block">Modules</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link hstack gap-2 rounded-0 fs-12 py-6" id="pills-students-tab"
                                data-bs-toggle="pill" data-bs-target="#pills-students" type="button" role="tab"
                                aria-controls="pills-students" aria-selected="false">
                            <i class="ti ti-calendar-check fs-5"></i>
                            <span class="d-none d-md-block">Attendance</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link hstack gap-2 rounded-0 fs-12 py-6" id="pills-payments-tab"
                                data-bs-toggle="pill" data-bs-target="#pills-payments" type="button" role="tab"
                                aria-controls="pills-payments" aria-selected="false">
                            <i class="ti ti-credit-card fs-5"></i> <!-- Credit card icon for Payments -->
                            <span class="d-none d-md-block">Payments</span>
                        </button>
                    </li>
                </ul>

            </div>
        </div>
        <div class="tab-content" id="pills-tabContent">
            <div class="tab-pane fade show active" id="pills-modules" role="tabpanel"
                 aria-labelledby="pills-modules-tab" tabindex="0">

            </div>
            <div class="tab-pane fade" id="pills-students" role="tabpanel" aria-labelledby="pills-students-tab"
                 tabindex="0">

            </div>
            <div class="tab-pane fade" id="pills-payments" role="tabpanel" aria-labelledby="pills-payments-tab"
                 tabindex="0">

            </div>
        </div>
    </div>
</div>


@push('scripts')
    <script>

        window.addEventListener('show-enrollment-modal', () => {
            new bootstrap.Modal(document.getElementById('enrollCourseModal')).show();
        });

        window.addEventListener('hide-enrollment-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('enrollCourseModal'))?.hide();
        });
    </script>
@endpush




