<?php

namespace App\Livewire;

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app.frontend')] class extends Component {
} ?>

<div class="main-wrapper overflow-hidden">
    <!-- ------------------------------------- -->
    <!-- banner Start -->
    <!-- ------------------------------------- -->
    <section class="py-lg-7 pt-lg-12 bg-light-gray overflow-hidden">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div>
                        <h2 class="fs-14 fw-medium lh-sm mb-4">
                            <b>Empowering Careers</b> Through Quality TVET Education
                        </h2>
                        <a href="javascript:void(0)">
                            <a href="../main/authentication-login.html" class="btn btn-primary">Apply Now</a>
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="banner-image position-relative overflow-hidden">
                        <img src="../assets/images/frontend-pages/hero-students.jpg"
                             class="img-fluid w-100 h-100 object-fit-cover" alt="banner">
                    </div>
                </div>

            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- banner End -->
    <!-- ------------------------------------- -->

    <!-- ------------------------------------- -->
    <!-- Count Start -->
    <!-- ------------------------------------- -->
    <section class="pt-7 pt-md-14 pt-lg-11 pb-7 pb-lg-5">
        <div class="container-fluid">
            <div class="row justify-content-between">
                <div class="col-lg-5 mb-5 mb-lg-0">
                    <h2 class="fs-15 fw-bolder mb-4">
                        Over 5,000 students and counting.
                    </h2>
                    <p class="fs-5 text-muted mb-4">
                        Join Kenya's leading Technical and Vocational Education Training institute with global placement
                        opportunities
                    </p>
                    <a href="javascript:void(0)" class="fs-4 fw-bolder pb-2 border-dark border-2 border-bottom">
                        Request a Callback
                    </a>
                </div>
                <div class="col-lg-6">
                    <div class="row">
                        <div class="col-md-6 mb-7 mb-lg-5">
                            <div class="d-flex flex-column align-items-start gap-3">
                                <div class="bg-danger-subtle rounded-2 round-48 hstack justify-content-center">
                                    <iconify-icon icon="mdi:account-group" class="fs-4 text-warning"></iconify-icon>
                                </div>
                                <h4 class="fw-bolder mb-0">
                                    Students
                                </h4>
                                <p class="fs-4 text-muted mb-0">
                                    2500+ Students enrolled.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-7 mb-lg-5">
                            <div class="d-flex flex-column align-items-start gap-3">
                                <div class="bg-danger-subtle rounded-2 round-48 hstack justify-content-center">
                                    <iconify-icon icon="mdi:book-open-page-variant"
                                                  class="fs-4 text-success"></iconify-icon>
                                </div>
                                <h4 class="fw-bolder mb-0">
                                    Courses
                                </h4>
                                <p class="fs-4 text-muted mb-0">
                                    25+ Active Courses.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-7 mb-lg-5">
                            <div class="d-flex flex-column align-items-start gap-3">
                                <div class="bg-primary-subtle rounded-2 round-48 hstack justify-content-center">
                                    <iconify-icon icon="mdi:certificate-outline" class="fs-4 text-info"></iconify-icon>
                                </div>
                                <h4 class="fw-bolder mb-0">
                                    Certifications
                                </h4>
                                <p class="fs-4 text-muted mb-0">
                                    1800+ Certifications Issued.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-7 mb-lg-5">
                            <div class="d-flex flex-column align-items-start gap-3">
                                <div class="bg-info-subtle rounded-2 round-48 hstack justify-content-center">
                                    <iconify-icon icon="mdi:earth" class="fs-4 text-primary"></iconify-icon>
                                </div>
                                <h4 class="fw-bolder mb-0">
                                    Partners
                                </h4>
                                <p class="fs-4 text-muted mb-0">
                                    15+ Global Partners.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- Count End -->
    <!-- ------------------------------------- -->

    <!-- ------------------------------------- -->
    <!-- Tabs Start -->
    <!-- ------------------------------------- -->
    <section class="py-7 py-md-14 py-lg-11 bg-light-gray">
        <div class="container-fluid">
            <ul class="nav nav-pills tabs-pills justify-content-between gap-3" id="pills-tab" role="tablist">
                <li class="nav-item flex-grow-1" role="presentation">
                    <button class="nav-link active fs-4 fw-semibold px-4 py-6 tabs-shadow" id="pills-mission-tab"
                            data-bs-toggle="pill" data-bs-target="#pills-mission" type="button" role="tab"
                            aria-controls="pills-mission" aria-selected="true">
                        <iconify-icon icon="mdi:target" class="fs-7 me-2"></iconify-icon>
                        Mission
                    </button>
                </li>
                <li class="nav-item flex-grow-1" role="presentation">
                    <button class="nav-link fs-4 fw-semibold px-4 py-6 tabs-shadow" id="pills-vision-tab"
                            data-bs-toggle="pill" data-bs-target="#pills-vision" type="button" role="tab"
                            aria-controls="pills-vision" aria-selected="false">
                        <iconify-icon icon="mdi:eye-outline" class="fs-7 me-2"></iconify-icon>
                        Vision
                    </button>
                </li>
                <li class="nav-item flex-grow-1" role="presentation">
                    <button class="nav-link fs-4 fw-semibold px-4 py-6 tabs-shadow" id="pills-values-tab"
                            data-bs-toggle="pill" data-bs-target="#pills-values" type="button" role="tab"
                            aria-controls="pills-values" aria-selected="false">
                        <iconify-icon icon="mdi:handshake-outline" class="fs-7 me-2"></iconify-icon>
                        Core Values
                    </button>
                </li>
            </ul>
            <div class="tab-content mt-7 mt-lg-12 pb-lg-9" id="myTabContent">
                <div class="tab-pane fade show active" id="pills-mission" role="tabpanel" aria-labelledby="team-tab"
                     tabindex="0">
                    <div class="row gap-lg-0 gap-7">
                        <div class="col-lg-6">
                            <div class="bg-primary-subtle rounded-24 p-13">
                                <img src="../assets/images/frontend-pages/mission.jpg" alt="icon" class="w-100">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div
                                class="d-flex flex-column h-100 justify-content-center align-items-start ps-lg-7 ms-lg-8">
                                <h2 class="fs-10 fw-bolder mb-0">Our Mission</h2>
                                <div class=" my-4 w-100" id="">
                                    <div class="border-0 border-bottom">
                                        <div class="px-0 fs-4">
                                            To provide quality, relevant, and innovative technical and vocational
                                            education and training that meets industry standards and global market
                                            demands.
                                        </div>
                                    </div>
                                </div>
                                <a href="javascript:void(0)">
                                    <button class="btn btn-primary px-9 py-6">Learn More</button>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-vision" role="tabpanel" aria-labelledby="payments-tab"
                     tabindex="0">
                    <div class="row gap-lg-0 gap-7">
                        <div class="col-lg-6">
                            <div class="bg-primary-subtle rounded-24 p-13">
                                <img src="../assets/images/frontend-pages/vision-tabor.jpg" alt="icon" class="w-100">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div
                                class="d-flex flex-column h-100 justify-content-center align-items-start ps-lg-7 ms-lg-8">
                                <h2 class="fs-10 fw-bolder mb-0">Our Vision</h2>
                                <div class=" my-4 w-100" id="">
                                    <div class="border-0 border-bottom">
                                        <div class="px-0 fs-4">
                                            To be the leading TVET institution in East Africa, producing globally
                                            competitive graduates who contribute to sustainable development.
                                        </div>
                                    </div>
                                    <a href="javascript:void(0)">
                                        <button class="btn btn-primary px-9 py-6">Learn More</button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="pills-values" role="tabpanel" aria-labelledby="embedding-tab"
                     tabindex="0">
                    <div class="row gap-lg-0 gap-7">
                        <div class="col-lg-6">
                            <div class="bg-primary-subtle rounded-24 p-13">
                                <img src="../assets/images/frontend-pages/hero-students.jpg" alt="icon" class="w-100">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div
                                class="d-flex flex-column h-100 justify-content-center align-items-start ps-lg-7 ms-lg-8">
                                <h2 class="fs-10 fw-bolder mb-0">Our Core Values</h2>
                                <div class=" my-4 w-100" id="">
                                    <div class="border-0 border-bottom">
                                        <div class="card">
                                            <div class="card-body">
                                                <h4 class="card-title mb-3">Core Values</h4>
                                                <ol class="list-group list-group-numbered">
                                                    <li class="list-group-item m-0">Excellence in Education</li>
                                                    <li class="list-group-item m-0">Innovation & Creativity</li>
                                                    <li class="list-group-item m-0">Integrity & Accountability</li>
                                                    <li class="list-group-item m-0">Global Competitiveness</li>
                                                </ol>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <a href="javascript:void(0)">
                                    <button class="btn btn-primary px-9 py-6">Learn More</button>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- Tabs End -->
    <!-- ------------------------------------- -->

    <!-- ------------------------------------- -->
    <!-- Team Start -->
    <!-- ------------------------------------- -->
    <section class="bg-dark py-7 py-md-14 py-lg-11">
        <div class="container-fluid">
            <div class="row mb-7 mb-lg-0">
                <div class="col-lg-7">
                    <h2 class="text-white fs-15 fw-bolder mb-lg-0 lh-sm">
                        Meet our team
                    </h2>
                </div>
                <div class="col-lg-5">
                    <p class="mb-0 fs-4">
                        Meet the experienced professionals guiding our institution
                    </p>
                </div>
            </div>
            <div class="owl-carousel leadership-carousel owl-theme mt-lg-5 mb-lg-7">
                <div class="item">
                    <div class="meet-our-team position-relative rounded-3 overflow-hidden">
                        <img src="../assets/images/frontend-pages/morgan.jpg" alt="leader" class="">
                        <div class="leadership-card z-1 bg-white rounded py-3 px-8 mx-6 my-6 w-90 text-center">
                            <h4 class="fs-5 fw-bold mb-2">Sarah Wanjiku</h4>
                            <p class="fs-3 mb-0">Student Affairs Director</p>
                            <p class="fs-3 mb-0">Dedicated to student success and comprehensive support services.</p>
                        </div>
                    </div>
                </div>
                <div class="item">
                    <div class="meet-our-team position-relative rounded-3 overflow-hidden">
                        <img src="../assets/images/frontend-pages/taylor.jpg" alt="leader" class="">
                        <div class="leadership-card z-1 bg-white rounded py-3 px-8 mx-6 my-6 w-90 text-center">
                            <h4 class="fs-5 fw-bold mb-2">James Kipchoge</h4>
                            <p class="fs-3 mb-0">International Relations Manager</p>
                            <p class="fs-3 mb-0">Specialist in global partnerships and student placement programs.</p>
                        </div>
                    </div>
                </div>
                <div class="item">
                    <div class="meet-our-team position-relative rounded-3 overflow-hidden">
                        <img src="../assets/images/frontend-pages/jordan.jpg" alt="leader" class="">
                        <div class="leadership-card z-1 bg-white rounded py-3 px-8 mx-6 my-6 w-90 text-center">
                            <h4 class="fs-5 fw-bold mb-2">Anne Ngumo</h4>
                            <p class="fs-3 mb-0">Academic Director</p>
                            <p class="fs-3 mb-0">Expert in curriculum development and quality assurance with extensive
                                industry experience.</p>
                        </div>
                    </div>
                </div>
                <div class="item">
                    <div class="meet-our-team position-relative rounded-3 overflow-hidden">
                        <img src="../assets/images/frontend-pages/alex.jpg" alt="leader" class="">
                        <div class="leadership-card z-1 bg-white rounded py-3 px-8 mx-6 my-6 w-90 text-center">
                            <h4 class="fs-5 fw-bold mb-8">Dr. Jotham Mukundi</h4>
                            <p class="fs-3 mb-0">Director & Chief Executive</p>
                            <p class="fs-3 mb-0">Educational leader with over 15 years in TVET development and
                                international partnerships.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- Team End -->
    <!-- ------------------------------------- -->

    <section class="bg-primary py-3 ">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-center flex-md-nowrap flex-wrap gap-3">
                <ul class="hstack mb-0">
                    <li class="ms-n8">
                        <a href="javascript:void(0)" class="me-1">
                            <img src="../assets/images/profile/user-5.jpg"
                                 class="rounded-circle border border-2 border-white" width="44" height="44"
                                 alt="Matdash-img">
                        </a>
                    </li>
                    <li class="ms-n8">
                        <a href="javascript:void(0)" class="me-1">
                            <img src="../assets/images/profile/user-2.jpg"
                                 class="rounded-circle border border-2 border-white" width="44" height="44"
                                 alt="Matdash-img">
                        </a>
                    </li>
                </ul>
                <p class="mb-0 text-white fs-4">Join thousands of successful graduates who have transformed their
                    careers with Tabor Training Institute.</p>
                <a href="javascript:void(0)" class="text-white fs-4 fw-semibold text-underline">Apply Now</a>
            </div>
        </div>
    </section>

    <!-- ------------------------------------- -->
    <!-- Testimonial Start -->
    <!-- ------------------------------------- -->
    <section class="pt-7 pt-md-14 pt-lg-11">
        <div class="container-fluid">
            <div class="row justify-content-between pb-12 border-bottom">
                <div class="col-lg-5">
                    <h2 class="fw-bolder fs-15 mb-4 lh-1">
                        Words from students.
                    </h2>
                    <p class="fs-5 mb-0 text-muted">
                        What do our students say.
                    </p>
                </div>
                <div class="col-lg-6 mt-md-0 mt-7">
                    <div class="owl-carousel testimonial-carousel owl-theme">
                        <div class="item">
                            <p class="fs-6 text-dark mb-13">
                                I want to sincerely thank the entire institution for the incredible support and quality
                                training I’ve received. This opportunity has truly changed my life, and I feel more
                                confident stepping into my career. Forever grateful!
                            </p>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../assets/images/profile/user-12.jpg" alt="user" width="56px"
                                         height="56px" class="rounded-circle w-auto">
                                    <div>
                                        <p class="mb-1 fs-5 fw-bolder text-dark">Angela Muthoni</p>
                                        <p class="mb-0 fs-4">Student - Information Technology</p>
                                    </div>
                                </div>
                                <span class="bg-primary round-48 rounded-circle hstack justify-content-center">
            <img src="../assets/images/frontend-pages/icon-quotes.svg" alt="user" class="w-auto">
        </span>
                            </div>
                        </div>

                        <div class="item">
                            <p class="fs-6 text-dark mb-13">
                                I wanna say I am so grateful for this opportunity you have given me. The support and
                                mentorship here have been amazing. Thank you for believing in us.
                            </p>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../assets/images/profile/user-7.jpg" alt="user" width="56px" height="56px"
                                         class="rounded-circle w-auto">
                                    <div>
                                        <p class="mb-1 fs-5 fw-bolder text-dark">Kevin Amayi</p>
                                        <p class="mb-0 fs-4">Student - Nursing</p>
                                    </div>
                                </div>
                                <span class="bg-primary round-48 rounded-circle hstack justify-content-center">
            <img src="../assets/images/frontend-pages/icon-quotes.svg" alt="user" class="w-auto">
        </span>
                            </div>
                        </div>

                        <div class="item">
                            <p class="fs-6 text-dark mb-13">
                                From day one, I felt welcomed and supported. The instructors are highly experienced and
                                always willing to help. I'm proud to be part of this institution.
                            </p>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../assets/images/profile/user-14.jpg" alt="user" width="56px"
                                         height="56px" class="rounded-circle w-auto">
                                    <div>
                                        <p class="mb-1 fs-5 fw-bolder text-dark">Samuel Kiprotich</p>
                                        <p class="mb-0 fs-4">Student - Electrical Engineering</p>
                                    </div>
                                </div>
                                <span class="bg-primary round-48 rounded-circle hstack justify-content-center">
            <img src="../assets/images/frontend-pages/icon-quotes.svg" alt="user" class="w-auto">
        </span>
                            </div>
                        </div>

                        <div class="item">
                            <p class="fs-6 text-dark mb-13">
                                The facilities, training resources, and hands-on experience here have exceeded my
                                expectations. Thank you for preparing us for real-world challenges!
                            </p>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../assets/images/profile/user-9.jpg" alt="user" width="56px" height="56px"
                                         class="rounded-circle w-auto">
                                    <div>
                                        <p class="mb-1 fs-5 fw-bolder text-dark">Mercy Nyambura</p>
                                        <p class="mb-0 fs-4">Student - Hospitality Management</p>
                                    </div>
                                </div>
                                <span class="bg-primary round-48 rounded-circle hstack justify-content-center">
            <img src="../assets/images/frontend-pages/icon-quotes.svg" alt="user" class="w-auto">
        </span>
                            </div>
                        </div>

                        <div class="item">
                            <p class="fs-6 text-dark mb-13">
                                I'm grateful for the personalized guidance I’ve received here. It’s more than just a
                                school — it’s a community that truly wants students to succeed.
                            </p>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../assets/images/profile/user-11.jpg" alt="user" width="56px"
                                         height="56px" class="rounded-circle w-auto">
                                    <div>
                                        <p class="mb-1 fs-5 fw-bolder text-dark">Brian Otieno</p>
                                        <p class="mb-0 fs-4">Student - Mechanical Engineering</p>
                                    </div>
                                </div>
                                <span class="bg-primary round-48 rounded-circle hstack justify-content-center">
            <img src="../assets/images/frontend-pages/icon-quotes.svg" alt="user" class="w-auto">
        </span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- Testimonial End -->
    <!-- ------------------------------------- -->


    <!-- ------------------------------------- -->
    <!-- more of tabor -->
    <!-- ------------------------------------- -->
    <section class="pt-7 pt-md-14 pt-lg-11">
        <div class="container-fluid">
            <div class="row justify-content-between pb-12 border-bottom">
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-body">
                            <ol class="list-group list-group-numbered">

                                <li class="mt-3 d-flex justify-content-between align-items-start m-0">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-semibold text-dark fs-5">Online Learning</div>
                                        <div class="text-muted fs-6">Interactive digital courses with virtual labs and simulations</div>
                                    </div>
                                    <span class="badge bg-success rounded-circle fs-6 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">✓</span>
                                </li>

                                <li class="mt-3 d-flex justify-content-between align-items-start m-0">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-semibold text-dark fs-5">In-Person Training</div>
                                        <div class="text-muted fs-6">Hands-on practical training in state-of-the-art facilities</div>
                                    </div>
                                    <span class="badge bg-success rounded-circle fs-6 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">✓</span>
                                </li>

                                <li class="mt-3 d-flex justify-content-between align-items-start m-0">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-semibold text-dark fs-5">Blended Learning</div>
                                        <div class="text-muted fs-6">Combination of online theory and practical workshops</div>
                                    </div>
                                    <span class="badge bg-success rounded-circle fs-6 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">✓</span>
                                </li>

                                <li class="mt-3 d-flex justify-content-between align-items-start m-0">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-semibold text-dark fs-5">Global Placement</div>
                                        <div class="text-muted fs-6">International job placement assistance and career support</div>
                                    </div>
                                    <span class="badge bg-success rounded-circle fs-6 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">✓</span>
                                </li>

                            </ol>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mt-md-0 mt-7">
                    <div class="item">
                        <div class="card border-0">
                            <div class="card-body">
                                <h4 class="fs-4 fw-bold text-dark mb-4">Why Choose Tabor?</h4>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-3 d-flex align-items-start gap-2">
                                        <span class="text-success fs-5">✓</span>
                                        <span class="fs-6 text-dark">TVETA & NITA Accredited Programs</span>
                                    </li>
                                    <li class="mb-3 d-flex align-items-start gap-2">
                                        <span class="text-success fs-5">✓</span>
                                        <span class="fs-6 text-dark">International Certification Pathways</span>
                                    </li>
                                    <li class="mb-3 d-flex align-items-start gap-2">
                                        <span class="text-success fs-5">✓</span>
                                        <span class="fs-6 text-dark">Industry-Experienced Instructors</span>
                                    </li>
                                    <li class="mb-3 d-flex align-items-start gap-2">
                                        <span class="text-success fs-5">✓</span>
                                        <span class="fs-6 text-dark">Job Placement Assistance</span>
                                    </li>
                                    <li class="mb-3 d-flex align-items-start gap-2">
                                        <span class="text-success fs-5">✓</span>
                                        <span class="fs-6 text-dark">Flexible Payment Options</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- more of tabor -->
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


