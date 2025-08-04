<?php

namespace App\Livewire;

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app.frontend')] class extends Component {
} ?>

@push('styles')
    <style>
        .accreditation-section {
            background-color: #f9f9f9;
        }

        .partner-card {
            transition: all 0.3s ease-in-out;
            border: 1px solid #eee;
        }

        .partner-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.07);
            border-color: #f79020;
        }

        .icon-wrapper {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bg-light-orange {
            background-color: #f79020;
        }
    </style>
@endpush

<div class="main-wrapper overflow-hidden">
    <!-- ------------------------------------- -->
    <!-- banner Start -->
    <!-- ------------------------------------- -->
    <section class="pt-lg-11 pt-md-5 pt-7 pb-7 pb-md-5 pb-md-12 bg-light-gray overflow-hidden">
        <div class="container-fluid">
            <div class="row mb-lg-7">
                <div class="col-lg-6 mb-7 mb-md-5 mb-lg-0">
                    <h2 class="fs-16 fw-normal text-lg-start text-center mb-4">
                        About Tabor Training Institute
                    </h2>
                    <div class="d-flex justify-content-lg-start justify-content-center gap-3">
                        <a class="btn btn-primary" href="../main/authentication-register.html">
                            Join our Community
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <p class="fs-4 mb-0 text-muted lh-lg">
                        Established as Kenya's premier TVET institution,
                        we bridge the gap between education and employment through quality training and global
                        opportunities.
                    </p>
                </div>
            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- banner End -->
    <!-- ------------------------------------- -->

    <!-- ------------------------------------- -->
    <!-- Details Start -->
    <!-- ------------------------------------- -->
    <section class="pt-md-13 pb-md-11">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body wizard-content">
                            <p class="fs-4 mb-sm-4 mb-3 text-muted">
                                "Tabor" represents transformation and elevation - much like the biblical Mount Tabor
                                where transformation occurred. Our institution embodies this spirit by transforming
                                lives through education and elevating careers through practical skills training.
                                <br><br>
                                We believe in the power of technical and vocational education to create sustainable
                                livelihoods and contribute to national development. Every student who walks through our
                                doors is on a journey of professional transformation.
                            </p>
                            <div class="d-flex justify-content-lg-start justify-content-center gap-3">
                                <a class="btn btn-primary" href="../main/authentication-register.html">
                                    Explore Programs
                                </a>
                                <a class="btn btn-outline-primary" href="javascript:void(0)">
                                    Download TVET Guide
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-7 mb-lg-0">
                    <div class="d-flex flex-column gap-3 bg-white p-7 rounded-3">
                        <div class="d-flex gap-3 pb-2 border-bottom ">
                            <div class="">
                                <h2 class="mb-0 text-dark fs-4 fw-semibold">Our Philosophy</h2>
                            </div>
                        </div>
                        <!-- Practical Learning -->
                        <div class="mb-4">
                            <a href="#practical-learning" class="text-dark text-decoration-none">
                                <h5 class="fs-4 fw-semibold mb-1 link-primary">Practical Learning</h5>
                                <p class="mb-0 text-muted fs-6">Hands-on training that mirrors real workplace
                                    environments.</p>
                            </a>
                        </div>

                        <!-- Industry Relevance -->
                        <div class="mb-4">
                            <a href="#industry-relevance" class="text-dark text-decoration-none">
                                <h5 class="fs-4 fw-semibold mb-1 link-primary">Industry Relevance</h5>
                                <p class="mb-0 text-muted fs-6">Curriculum designed with input from leading
                                    employers.</p>
                            </a>
                        </div>

                        <!-- Global Standards -->
                        <div>
                            <a href="#global-standards" class="text-dark text-decoration-none">
                                <h5 class="fs-4 fw-semibold mb-1 link-primary">Global Standards</h5>
                                <p class="mb-0 text-muted fs-6">Training that meets international certification
                                    requirements.</p>
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- Details End -->
    <!-- ------------------------------------- -->


    <section class="bg-dark py-7 py-md-14 py-lg-11">
        <div class="container-fluid">
            <div class="row mb-7 mb-lg-0">
                <div class="col-lg-7">
                    <h2 class="text-white fs-15 fw-bolder mb-lg-0 lh-sm">
                        Meet our team
                    </h2>
                </div>
            </div>
            <div class="owl-carousel leadership-carousel owl-theme mt-lg-5 mb-lg-7">
                <div class="item">
                    <div class="meet-our-team position-relative rounded-3 overflow-hidden">
                        <img src="../assets/images/frontend-pages/Jacklyne.jpg" alt="leader" style="width: 280px; height: 360px;">
                        <div class="leadership-card z-1 bg-white rounded py-3 px-8 mx-6 my-6 w-90 text-center">
                            <h4 class="fs-5 fw-bold mb-2">Jacklyne Nekesa Wangwe</h4>
                            <p class="fs-3 mb-0">Lead Trainer – Caregiving and Health Services Support</p>
                        </div>
                    </div>
                </div>
                <div class="item">
                    <div class="meet-our-team position-relative rounded-3 overflow-hidden">
                        <img src="../assets/images/frontend-pages/Slyvia.jpg" alt="leader" style="width: 280px; height: 360px;" class="">
                        <div class="leadership-card z-1 bg-white rounded py-3 px-8 mx-6 my-6 w-90 text-center">
                            <h4 class="fs-5 fw-bold mb-2">Slyvia Njeri Kimani</h4>
                            <p class="fs-3 mb-0">Head of Training</p>
                        </div>
                    </div>
                </div>
                <div class="item">
                    <div class="meet-our-team position-relative rounded-3 overflow-hidden">
                        <img style="height:360px; width:280px;" src="../assets/images/frontend-pages/Ann.jpeg" alt="leader" class="">
                        <div class="leadership-card z-1 bg-white rounded py-3 px-8 mx-6 my-6 w-90 text-center">
                            <h4 class="fs-5 fw-bold mb-2">Anne Ngumo</h4>
                            <p class="fs-3 mb-0">Academic Director</p>
                        </div>
                    </div>
                </div>
                <div class="item">
                    <div class="meet-our-team position-relative rounded-3 overflow-hidden">
                        <img src="../assets/images/frontend-pages/Jotham.jpeg" alt="leader" class="">
                        <div class="leadership-card z-1 bg-white rounded py-3 px-8 mx-6 my-6 w-90 text-center">
                            <h4 class="fs-5 fw-bold mb-8">Dr. Jotham Mukundi</h4>
                            <p class="fs-3 mb-0">Director & Chief Executive</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="owl-carousel leadership-carousel owl-theme mt-lg-5 mb-lg-7">
                <div class="item">
                </div>
                <div class="item">
                </div>
                <div class="item">
                    <div class="meet-our-team position-relative rounded-3 overflow-hidden">
                        <img src="../assets/images/frontend-pages/Felix.jpeg" alt="leader" style="width: 280px; height: 360px;">
                        <div class="leadership-card z-1 bg-white rounded py-3 px-8 mx-6 my-6 w-90 text-center">
                            <h4 class="fs-5 fw-bold mb-2">Felix Wakhu Murule</h4>
                            <p class="fs-3 mb-0">Lead Trainer Orthopedic & Trauma Medicine</p>
                        </div>
                    </div>
                </div>
                <div class="item">
                    <div class="meet-our-team position-relative rounded-3 overflow-hidden">
                        <img src="../assets/images/frontend-pages/Victoria.jpeg" alt="leader" style="width: 280px; height: 360px;">
                        <div class="leadership-card z-1 bg-white rounded py-3 px-8 mx-6 my-6 w-90 text-center">
                            <h4 class="fs-5 fw-bold mb-2">Victoria Wanjiku</h4>
                            <p class="fs-3 mb-0">Wellness Officer</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>


{{--    <div class="col-lg-12 mt-md-0 mt-7">--}}
{{--        <div class="owl-carousel testimonial-carousel owl-theme">--}}
{{--            <!-- Carousel Item 1 with 3 images -->--}}
{{--            <div class="item">--}}
{{--                <img src="../assets/images/logos/logo-1.png" alt="Logo 1" class="img-fluid">--}}
{{--                <img src="../assets/images/logos/logo-2.png" alt="Logo 2" class="img-fluid">--}}
{{--                <img src="../assets/images/logos/logo-3.png" alt="Logo 3" class="img-fluid">--}}
{{--            </div>--}}

{{--            <!-- Carousel Item 2 with 3 images -->--}}
{{--            <div class="item">--}}
{{--                <img src="../assets/images/logos/logo-4.png" alt="Logo 4" class="img-fluid">--}}
{{--                <img src="../assets/images/logos/logo-5.png" alt="Logo 5" class="img-fluid">--}}
{{--                <img src="../assets/images/logos/logo-6.png" alt="Logo 6" class="img-fluid">--}}
{{--            </div>--}}

{{--            <!-- Carousel Item 3 with 3 images -->--}}
{{--            <div class="item">--}}
{{--                <img src="../assets/images/logos/logo-7.png" alt="Logo 7" class="img-fluid">--}}
{{--                <img src="../assets/images/logos/logo-8.png" alt="Logo 8" class="img-fluid">--}}
{{--                <img src="../assets/images/logos/logo-9.png" alt="Logo 9" class="img-fluid">--}}
{{--            </div>--}}

{{--            <!-- Carousel Item 4 with 3 images -->--}}
{{--            <div class="item">--}}
{{--                <img src="../assets/images/logos/logo-10.png" alt="Logo 10" class="img-fluid">--}}
{{--                <img src="../assets/images/logos/logo-11.png" alt="Logo 11" class="img-fluid">--}}
{{--                <img src="../assets/images/logos/logo-12.png" alt="Logo 12" class="img-fluid">--}}
{{--            </div>--}}

{{--            <!-- Carousel Item 5 with 3 images -->--}}
{{--            <div class="item">--}}
{{--                <img src="../assets/images/logos/logo-13.png" alt="Logo 13" class="img-fluid">--}}
{{--                <img src="../assets/images/logos/logo-14.png" alt="Logo 14" class="img-fluid">--}}
{{--                <img src="../assets/images/logos/logo-15.png" alt="Logo 15" class="img-fluid">--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}



    <!-- ------------------------------------- -->
    <!-- Accreditation & Partners Start -->
    <!-- ------------------------------------- -->
    <section class="pt-7 pt-md-14 pt-lg-11 pb-9 pb-lg-12 border-bottom accreditation-section">
        <div class="container-fluid">
            <h2 class="fs-8 fw-bold text-center mb-3 text-dark">
                Accreditation & Partners
            </h2>
            <p class="text-center text-muted mb-5 fs-5">
                Recognized by leading institutions and regulatory bodies
            </p>

            <div class="row g-4">
                <!-- Item Template -->
                <div class="col-lg-4 col-md-6">
                    <div
                        class="partner-card bg-white shadow-sm rounded-4 p-5 h-100 d-flex flex-column justify-content-start align-items-start">
                        <div class="icon-wrapper bg-light-orange text-white mb-4">
                            <iconify-icon icon="mdi:school-outline" class="fs-7"></iconify-icon>
                        </div>
                        <h5 class="fw-semibold text-dark mb-1">TVETA</h5>
                        <p class="text-muted fs-3 mb-0">Technical and Vocational Education and Training Authority</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div
                        class="partner-card bg-white shadow-sm rounded-4 p-5 h-100 d-flex flex-column justify-content-start align-items-start">
                        <div class="icon-wrapper bg-light-orange text-white mb-4">
                            <iconify-icon icon="fluent:certificate-24-regular" class="fs-7"></iconify-icon>
                        </div>
                        <h5 class="fw-semibold text-dark mb-1">NITA</h5>
                        <p class="text-muted fs-3 mb-0">National Industrial Training Authority</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div
                        class="partner-card bg-white shadow-sm rounded-4 p-5 h-100 d-flex flex-column justify-content-start align-items-start">
                        <div class="icon-wrapper bg-light-orange text-white mb-4">
                            <iconify-icon icon="emojione:flag-for-germany" class="fs-7"></iconify-icon>
                        </div>
                        <h5 class="fw-semibold text-dark mb-1">German Embassy</h5>
                        <p class="text-muted fs-3 mb-0">International collaboration and recognition</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div
                        class="partner-card bg-white shadow-sm rounded-4 p-5 h-100 d-flex flex-column justify-content-start align-items-start">
                        <div class="icon-wrapper bg-light-orange text-white mb-4">
                            <iconify-icon icon="mdi:anchor" class="fs-7"></iconify-icon>
                        </div>
                        <h5 class="fw-semibold text-dark mb-1">Maritime Training Center</h5>
                        <p class="text-muted fs-3 mb-0">Certified training for seafarers and maritime professionals</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div
                        class="partner-card bg-white shadow-sm rounded-4 p-5 h-100 d-flex flex-column justify-content-start align-items-start">
                        <div class="icon-wrapper bg-light-orange text-white mb-4">
                            <iconify-icon icon="healthicons:health-worker-outline" class="fs-7"></iconify-icon>
                        </div>
                        <h5 class="fw-semibold text-dark mb-1">Healthcare Consortium</h5>
                        <p class="text-muted fs-3 mb-0">Advancing medical training & certifications</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div
                        class="partner-card bg-white shadow-sm rounded-4 p-5 h-100 d-flex flex-column justify-content-start align-items-start">
                        <div class="icon-wrapper bg-light-orange text-white mb-4">
                            <iconify-icon icon="mdi:silverware-fork-knife" class="fs-7"></iconify-icon>
                        </div>
                        <h5 class="fw-semibold text-dark mb-1">Hospitality Guild</h5>
                        <p class="text-muted fs-3 mb-0">Hospitality standards and professional development</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- Accreditation & Partners End -->
    <!-- ------------------------------------- -->


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


