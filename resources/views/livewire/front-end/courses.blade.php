<?php

namespace App\Livewire;

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app.frontend')] class extends Component {
} ?>

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
                                            <p class="mb-0 fs-2 fw-semibold text-dark">40 Courses</p>
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

                <div class="col-lg-4 col-md-6">
                    <div class="card rounded-3 overflow-hidden">
                        <a href="../main/frontend-blogdetailpage.html" class="position-relative">
                            <img src="../assets/images/frontend-pages/blog-1.jpg" alt="blog image"
                                 class="w-100 img-fluid">
                        </a>
                        <div class="mt-7 px-7 pb-7 h-100">
                            <div class="d-flex gap-3 flex-column h-100 justify-content-between">
                                <a href="../main/frontend-blogdetailpage.html" class="fs-5 fw-bolder">
                                    Food Production Culinary Arts
                                </a>
                                <p>Comprehensive culinary training covering food preparation,
                                    kitchen management, and international cuisine techniques.
                                </p>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2  d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:calendar-clock"
                                                      class="text-primary fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Duration: 8 months</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:laptop" class="text-success fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Mode: Blended Learning</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:school-outline"
                                                      class="text-warning fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Level: Level 5</span>
                                    </li>
                                    <li class="d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:certificate-outline"
                                                      class="text-info fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Certification: TVET Level 5 Diploma</span>
                                    </li>
                                </ul>
                                <ul class="nav nav-pills nav-fill mt-4" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#pill-overview"
                                           role="tab">
                                            <span>Overview</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#pill-details" role="tab">
                                            <span>Details</span>
                                        </a>
                                    </li>
                                </ul>
                                <div class="tab-content mt-2">
                                    <div class="tab-pane active p-3" id="pill-overview" role="tabpanel">
                                        <div class="row">
                                            <h5 class="fw-bold mb-3">Course Highlights:</h5>
                                            <ul class="list-unstyled">
                                                <li class="mb-2 d-flex gap-2">
                                                    <span class="text-primary">•</span>
                                                    <span>Professional kitchen training</span>
                                                </li>
                                                <li class="mb-2 d-flex gap-2">
                                                    <span class="text-primary">•</span>
                                                    <span>International cuisine</span>
                                                </li>
                                                <li class="mb-2 d-flex gap-2">
                                                    <span class="text-primary">•</span>
                                                    <span>Food safety certification</span>
                                                </li>
                                                <li class="d-flex gap-2">
                                                    <span class="text-primary">•</span>
                                                    <span>Industry attachment</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="tab-pane p-3" id="pill-details" role="tabpanel">
                                        <div class="row">
                                            <h5 class="fw-bold mb-3">Course Details:</h5>
                                            <ul class="list-unstyled">
                                                <li class="mb-2 d-flex gap-2">
                                                    <strong>Fee:</strong>
                                                    <span>KES 85,000</span>
                                                </li>
                                                <li class="mb-2 d-flex gap-2">
                                                    <strong>Next Intake:</strong>
                                                    <span>March 2025</span>
                                                </li>
                                                <li class="d-flex gap-2">
                                                    <strong>Prerequisites:</strong>
                                                    <span>KCSE C- or equivalent</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a class="btn btn-primary d-block w-100 mb-3" href="../main/authentication-register.html">
                                        Apply Now
                                    </a>
                                    <a class="btn btn-outline-primary d-block w-100" href="javascript:void(0)">
                                        Download Brochure
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course 1 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card rounded-3 overflow-hidden">
                        <a href="#" class="position-relative">
                            <img src="../assets/images/frontend-pages/blog-2.jpg" alt="blog image" class="w-100 img-fluid">
                        </a>
                        <div class="mt-7 px-7 pb-7 h-100">
                            <div class="d-flex gap-3 flex-column h-100 justify-content-between">
                                <a href="#" class="fs-5 fw-bolder">Hospitality & Hotel Management</a>
                                <p>Training in guest services, hotel operations, event planning, and front office procedures.</p>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:calendar-clock" class="text-primary fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Duration: 12 months</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:laptop" class="text-success fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Mode: On-Campus</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:school-outline" class="text-warning fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Level: Level 6</span>
                                    </li>
                                    <li class="d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:certificate-outline" class="text-info fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Certification: Diploma in Hospitality</span>
                                    </li>
                                </ul>
                                <ul class="nav nav-pills nav-fill mt-4" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#pill-overview1" role="tab"><span>Overview</span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#pill-details1" role="tab"><span>Details</span></a>
                                    </li>
                                </ul>
                                <div class="tab-content mt-2">
                                    <div class="tab-pane active p-3" id="pill-overview1" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Highlights:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Hotel internship</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Event planning basics</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Customer service training</span></li>
                                            <li class="d-flex gap-2"><span class="text-primary">•</span><span>Front office skills</span></li>
                                        </ul>
                                    </div>
                                    <div class="tab-pane p-3" id="pill-details1" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Details:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><strong>Fee:</strong><span>KES 110,000</span></li>
                                            <li class="mb-2 d-flex gap-2"><strong>Next Intake:</strong><span>January 2025</span></li>
                                            <li class="d-flex gap-2"><strong>Prerequisites:</strong><span>KCSE C or above</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div>
                                    <a class="btn btn-primary d-block w-100 mb-3" href="#">Apply Now</a>
                                    <a class="btn btn-outline-primary d-block w-100" href="#">Download Brochure</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course 2 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card rounded-3 overflow-hidden">
                        <a href="#" class="position-relative">
                            <img src="../assets/images/frontend-pages/blog-2.jpg" alt="blog image" class="w-100 img-fluid">
                        </a>
                        <div class="mt-7 px-7 pb-7 h-100">
                            <div class="d-flex gap-3 flex-column h-100 justify-content-between">
                                <a href="#" class="fs-5 fw-bolder">Hospitality & Hotel Management</a>
                                <p>Training in guest services, hotel operations, event planning, and front office procedures.</p>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:calendar-clock" class="text-primary fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Duration: 12 months</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:laptop" class="text-success fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Mode: On-Campus</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:school-outline" class="text-warning fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Level: Level 6</span>
                                    </li>
                                    <li class="d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:certificate-outline" class="text-info fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Certification: Diploma in Hospitality</span>
                                    </li>
                                </ul>
                                <ul class="nav nav-pills nav-fill mt-4" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#pill-overview2" role="tab"><span>Overview</span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#pill-details2" role="tab"><span>Details</span></a>
                                    </li>
                                </ul>
                                <div class="tab-content mt-2">
                                    <div class="tab-pane active p-3" id="pill-overview2" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Highlights:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Hotel internship</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Event planning basics</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Customer service training</span></li>
                                            <li class="d-flex gap-2"><span class="text-primary">•</span><span>Front office skills</span></li>
                                        </ul>
                                    </div>
                                    <div class="tab-pane p-3" id="pill-details2" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Details:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><strong>Fee:</strong><span>KES 110,000</span></li>
                                            <li class="mb-2 d-flex gap-2"><strong>Next Intake:</strong><span>January 2025</span></li>
                                            <li class="d-flex gap-2"><strong>Prerequisites:</strong><span>KCSE C or above</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div>
                                    <a class="btn btn-primary d-block w-100 mb-3" href="#">Apply Now</a>
                                    <a class="btn btn-outline-primary d-block w-100" href="#">Download Brochure</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course 3 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card rounded-3 overflow-hidden">
                        <a href="#" class="position-relative">
                            <img src="../assets/images/frontend-pages/blog-3.jpg" alt="blog image" class="w-100 img-fluid">
                        </a>
                        <div class="mt-7 px-7 pb-7 h-100">
                            <div class="d-flex gap-3 flex-column h-100 justify-content-between">
                                <a href="#" class="fs-5 fw-bolder">Information Technology Essentials</a>
                                <p>Foundational course in computer systems, software applications, networking, and basic coding.</p>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:calendar-clock" class="text-primary fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Duration: 6 months</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:laptop" class="text-success fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Mode: Online</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:school-outline" class="text-warning fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Level: Level 4</span>
                                    </li>
                                    <li class="d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:certificate-outline" class="text-info fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Certification: Certificate in IT</span>
                                    </li>
                                </ul>
                                <ul class="nav nav-pills nav-fill mt-4" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#pill-overview3" role="tab"><span>Overview</span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#pill-details3" role="tab"><span>Details</span></a>
                                    </li>
                                </ul>
                                <div class="tab-content mt-2">
                                    <div class="tab-pane active p-3" id="pill-overview3" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Highlights:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Basic computer skills</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Networking concepts</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Web design intro</span></li>
                                            <li class="d-flex gap-2"><span class="text-primary">•</span><span>Hands-on labs</span></li>
                                        </ul>
                                    </div>
                                    <div class="tab-pane p-3" id="pill-details3" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Details:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><strong>Fee:</strong><span>KES 45,000</span></li>
                                            <li class="mb-2 d-flex gap-2"><strong>Next Intake:</strong><span>February 2025</span></li>
                                            <li class="d-flex gap-2"><strong>Prerequisites:</strong><span>KCSE D+ or equivalent</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div>
                                    <a class="btn btn-primary d-block w-100 mb-3" href="#">Apply Now</a>
                                    <a class="btn btn-outline-primary d-block w-100" href="#">Download Brochure</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course 4 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card rounded-3 overflow-hidden">
                        <a href="#" class="position-relative">
                            <img src="../assets/images/frontend-pages/blog-4.jpg" alt="blog image" class="w-100 img-fluid">
                        </a>
                        <div class="mt-7 px-7 pb-7 h-100">
                            <div class="d-flex gap-3 flex-column h-100 justify-content-between">
                                <a href="#" class="fs-5 fw-bolder">Early Childhood Development</a>
                                <p>Focus on child psychology, learning environments, health and nutrition, and classroom management.</p>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:calendar-clock" class="text-primary fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Duration: 9 months</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:laptop" class="text-success fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Mode: Evening Classes</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:school-outline" class="text-warning fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Level: Level 5</span>
                                    </li>
                                    <li class="d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:certificate-outline" class="text-info fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Certification: ECDE Diploma</span>
                                    </li>
                                </ul>
                                <ul class="nav nav-pills nav-fill mt-4" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#pill-overview4" role="tab"><span>Overview</span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#pill-details4" role="tab"><span>Details</span></a>
                                    </li>
                                </ul>
                                <div class="tab-content mt-2">
                                    <div class="tab-pane active p-3" id="pill-overview4" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Highlights:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Child psychology</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Classroom management</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Creative activities</span></li>
                                            <li class="d-flex gap-2"><span class="text-primary">•</span><span>Internship in schools</span></li>
                                        </ul>
                                    </div>
                                    <div class="tab-pane p-3" id="pill-details4" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Details:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><strong>Fee:</strong><span>KES 70,000</span></li>
                                            <li class="mb-2 d-flex gap-2"><strong>Next Intake:</strong><span>May 2025</span></li>
                                            <li class="d-flex gap-2"><strong>Prerequisites:</strong><span>KCSE C- minimum</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div>
                                    <a class="btn btn-primary d-block w-100 mb-3" href="#">Apply Now</a>
                                    <a class="btn btn-outline-primary d-block w-100" href="#">Download Brochure</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course 5 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card rounded-3 overflow-hidden">
                        <a href="#" class="position-relative">
                            <img src="../assets/images/frontend-pages/blog-5.jpg" alt="blog image" class="w-100 img-fluid">
                        </a>
                        <div class="mt-7 px-7 pb-7 h-100">
                            <div class="d-flex gap-3 flex-column h-100 justify-content-between">
                                <a href="#" class="fs-5 fw-bolder">Graphic Design & Multimedia</a>
                                <p>Learn creative design principles, software tools, branding, and motion graphics for media industries.</p>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:calendar-clock" class="text-primary fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Duration: 10 months</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:laptop" class="text-success fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Mode: Blended</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:school-outline" class="text-warning fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Level: Level 6</span>
                                    </li>
                                    <li class="d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:certificate-outline" class="text-info fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Certification: Diploma in Design</span>
                                    </li>
                                </ul>
                                <ul class="nav nav-pills nav-fill mt-4" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#pill-overview5" role="tab"><span>Overview</span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#pill-details5" role="tab"><span>Details</span></a>
                                    </li>
                                </ul>
                                <div class="tab-content mt-2">
                                    <div class="tab-pane active p-3" id="pill-overview5" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Highlights:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Photoshop & Illustrator</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Brand design</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Motion graphics</span></li>
                                            <li class="d-flex gap-2"><span class="text-primary">•</span><span>Portfolio project</span></li>
                                        </ul>
                                    </div>
                                    <div class="tab-pane p-3" id="pill-details5" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Details:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><strong>Fee:</strong><span>KES 95,000</span></li>
                                            <li class="mb-2 d-flex gap-2"><strong>Next Intake:</strong><span>April 2025</span></li>
                                            <li class="d-flex gap-2"><strong>Prerequisites:</strong><span>KCSE C plain or higher</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div>
                                    <a class="btn btn-primary d-block w-100 mb-3" href="#">Apply Now</a>
                                    <a class="btn btn-outline-primary d-block w-100" href="#">Download Brochure</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course 6 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card rounded-3 overflow-hidden">
                        <a href="#" class="position-relative">
                            <img src="../assets/images/frontend-pages/blog-6.jpg" alt="blog image" class="w-100 img-fluid">
                        </a>
                        <div class="mt-7 px-7 pb-7 h-100">
                            <div class="d-flex gap-3 flex-column h-100 justify-content-between">
                                <a href="#" class="fs-5 fw-bolder">Business Management</a>
                                <p>Gain knowledge in business operations, marketing, finance, and entrepreneurship to manage or start a business.</p>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:calendar-clock" class="text-primary fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Duration: 12 months</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:laptop" class="text-success fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Mode: Day Classes</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:school-outline" class="text-warning fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Level: Level 6</span>
                                    </li>
                                    <li class="d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:certificate-outline" class="text-info fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Certification: Diploma in Business</span>
                                    </li>
                                </ul>
                                <ul class="nav nav-pills nav-fill mt-4" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#pill-overview6" role="tab"><span>Overview</span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#pill-details6" role="tab"><span>Details</span></a>
                                    </li>
                                </ul>
                                <div class="tab-content mt-2">
                                    <div class="tab-pane active p-3" id="pill-overview6" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Highlights:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Financial literacy</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Marketing principles</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Business plan development</span></li>
                                            <li class="d-flex gap-2"><span class="text-primary">•</span><span>Internship program</span></li>
                                        </ul>
                                    </div>
                                    <div class="tab-pane p-3" id="pill-details6" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Details:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><strong>Fee:</strong><span>KES 88,000</span></li>
                                            <li class="mb-2 d-flex gap-2"><strong>Next Intake:</strong><span>March 2025</span></li>
                                            <li class="d-flex gap-2"><strong>Prerequisites:</strong><span>KCSE C- minimum</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div>
                                    <a class="btn btn-primary d-block w-100 mb-3" href="#">Apply Now</a>
                                    <a class="btn btn-outline-primary d-block w-100" href="#">Download Brochure</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course 7 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card rounded-3 overflow-hidden">
                        <a href="#" class="position-relative">
                            <img src="../assets/images/frontend-pages/blog-7.jpg" alt="blog image" class="w-100 img-fluid">
                        </a>
                        <div class="mt-7 px-7 pb-7 h-100">
                            <div class="d-flex gap-3 flex-column h-100 justify-content-between">
                                <a href="#" class="fs-5 fw-bolder">Automotive Engineering</a>
                                <p>Hands-on training in vehicle maintenance, diagnostics, engine systems, and auto electronics.</p>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:calendar-clock" class="text-primary fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Duration: 18 months</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:laptop" class="text-success fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Mode: Workshop Based</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:school-outline" class="text-warning fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Level: Level 6</span>
                                    </li>
                                    <li class="d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:certificate-outline" class="text-info fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Certification: Diploma in Automotive</span>
                                    </li>
                                </ul>
                                <ul class="nav nav-pills nav-fill mt-4" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#pill-overview7" role="tab"><span>Overview</span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#pill-details7" role="tab"><span>Details</span></a>
                                    </li>
                                </ul>
                                <div class="tab-content mt-2">
                                    <div class="tab-pane active p-3" id="pill-overview7" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Highlights:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Engine diagnostics</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Transmission systems</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Electrical repairs</span></li>
                                            <li class="d-flex gap-2"><span class="text-primary">•</span><span>Garage internship</span></li>
                                        </ul>
                                    </div>
                                    <div class="tab-pane p-3" id="pill-details7" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Details:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><strong>Fee:</strong><span>KES 130,000</span></li>
                                            <li class="mb-2 d-flex gap-2"><strong>Next Intake:</strong><span>June 2025</span></li>
                                            <li class="d-flex gap-2"><strong>Prerequisites:</strong><span>KCSE C plain or above</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div>
                                    <a class="btn btn-primary d-block w-100 mb-3" href="#">Apply Now</a>
                                    <a class="btn btn-outline-primary d-block w-100" href="#">Download Brochure</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course 8 -->
                <div class="col-lg-4 col-md-6">
                    <div class="card rounded-3 overflow-hidden">
                        <a href="#" class="position-relative">
                            <img src="../assets/images/frontend-pages/blog-8.jpg" alt="blog image" class="w-100 img-fluid">
                        </a>
                        <div class="mt-7 px-7 pb-7 h-100">
                            <div class="d-flex gap-3 flex-column h-100 justify-content-between">
                                <a href="#" class="fs-5 fw-bolder">Fashion Design & Tailoring</a>
                                <p>Master the art of garment creation, textile handling, fashion illustration, and tailoring techniques.</p>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:calendar-clock" class="text-primary fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Duration: 9 months</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:laptop" class="text-success fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Mode: Studio Practical</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:school-outline" class="text-warning fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Level: Level 5</span>
                                    </li>
                                    <li class="d-flex align-items-start gap-2">
                                        <iconify-icon icon="mdi:certificate-outline" class="text-info fs-4 mt-1"></iconify-icon>
                                        <span class="text-dark fs-3">Certification: TVET Diploma</span>
                                    </li>
                                </ul>
                                <ul class="nav nav-pills nav-fill mt-4" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#pill-overview8" role="tab"><span>Overview</span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#pill-details8" role="tab"><span>Details</span></a>
                                    </li>
                                </ul>
                                <div class="tab-content mt-2">
                                    <div class="tab-pane active p-3" id="pill-overview8" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Highlights:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Sketching & illustration</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Pattern making</span></li>
                                            <li class="mb-2 d-flex gap-2"><span class="text-primary">•</span><span>Fabric cutting</span></li>
                                            <li class="d-flex gap-2"><span class="text-primary">•</span><span>Runway project</span></li>
                                        </ul>
                                    </div>
                                    <div class="tab-pane p-3" id="pill-details8" role="tabpanel">
                                        <h5 class="fw-bold mb-3">Course Details:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2 d-flex gap-2"><strong>Fee:</strong><span>KES 78,000</span></li>
                                            <li class="mb-2 d-flex gap-2"><strong>Next Intake:</strong><span>August 2025</span></li>
                                            <li class="d-flex gap-2"><strong>Prerequisites:</strong><span>KCSE D+ minimum</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div>
                                    <a class="btn btn-primary d-block w-100 mb-3" href="#">Apply Now</a>
                                    <a class="btn btn-outline-primary d-block w-100" href="#">Download Brochure</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
                        <img width="140" height="140" src="../assets/images/logos/tabor_logo_transparent.png" alt="logo" >
                    </a>
                    <h4 class="fs-7 my-9 fw-bolder text-white text-center lh-sm">
                        Join thousands of successful graduates who have transformed their careers with Tabor Training Institute..
                    </h4>
                    <a href="../main/authentication-register.html" class="btn px-5 btn-outline-light">
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
