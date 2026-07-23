<?php

namespace App\Livewire;

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app.frontend')] class extends Component {}; ?>

@push('styles')
    <style>
        .count-section {
            background: #f9f9f9;
            color: #0E2F44;
            font-family: 'Poppins', sans-serif;
        }

        .count-title {
            font-weight: 700;
            font-size: 2.25rem;
            line-height: 1.3;
        }

        .count-title .highlight {
            color: #f79020;
        }

        .count-subtitle {
            font-size: 1.125rem;
            color: #555;
            max-width: 400px;
        }

        .btn-request-callback {
            display: inline-block;
            font-size: 1.125rem;
            font-weight: 700;
            padding-bottom: 6px;
            border-bottom: 3px solid #f79020;
            color: #f79020;
            text-decoration: none;
            transition: color 0.3s, border-color 0.3s;
        }

        .btn-request-callback:hover {
            color: #d86e07;
            border-color: #d86e07;
        }

        .count-card {
            background: #fff;
            padding: 1rem 1rem;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
            text-align: left;
            transition: box-shadow 0.3s;
        }

        .count-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .icon {
            font-size: 1.5rem;
        }

        .icon-warning {
            background-color: #fff3e0;
            color: #f79020;
        }

        .icon-success {
            background-color: #e6f4ea;
            color: #4caf50;
        }

        .icon-info {
            background-color: #e0f0ff;
            color: #2196f3;
        }

        .icon-primary {
            background-color: #dbeeff;
            color: #0e2f44;
        }

        .count-card h4 {
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: #0E2F44;
        }

        .count-card p {
            color: #666;
            font-size: 1.125rem;
            margin-bottom: 0;
        }

        /* Background and typography */
        .bg-light-gray {
            background-color: #f9f9f9 !important;
        }

        .tabs-section {
            font-family: 'Poppins', sans-serif;
            color: #0E2F44;
        }

        /* Tabs styling */
        .tabs-pills .nav-link {
            border-radius: 12px;
            color: #0e2f44;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            padding-top: 1.5rem;
            padding-bottom: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tabs-pills .nav-link .icon-accent {
            color: #f79020;
            font-size: 1.25rem;
        }

        .tabs-pills .nav-link.active,
        .tabs-pills .nav-link:hover {
            background-color: #0a2540 !important;
            color: white !important;
            box-shadow: 0 6px 15px rgba(247, 144, 32, 0.4);
        }

        /* Tab content styling */
        .image-wrapper {
            background-color: #f0f6ff;
        }

        .shadow-soft {
            box-shadow: 0 8px 20px rgba(14, 47, 68, 0.08);
        }

        .rounded-24 {
            border-radius: 24px !important;
        }

        .content-wrapper h2.accent-color {
            color: #f79020;
        }

        .content-wrapper p {
            color: #555555;
            line-height: 1.6;
        }

        .btn-shadow {
            box-shadow: 0 4px 15px rgba(247, 144, 32, 0.4);
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-shadow:hover {
            background-color: #d86e07;
            box-shadow: 0 6px 18px rgba(216, 110, 7, 0.6);
            border-color: #d86e07;
        }

        /* List styles */
        .list-group-numbered .list-group-item {
            font-weight: 500;
            color: #0e2f44;
            background: transparent;
            border-radius: 6px;
        }

        .list-group-numbered .list-group-item:not(:last-child) {
            margin-bottom: 0.5rem;
        }

        .carousel-img {
            max-height: 550px;
            object-fit: cover;
        }

        .carousel-inner .carousel-item {
            height: 100%;
        }

        .carousel-inner .carousel-item img {
            object-fit: cover;
        }
    </style>
@endpush
<div class="main-wrapper overflow-hidden">
    <!-- ------------------------------------- -->
    <!-- banner Start -->
    <!-- ------------------------------------- -->
    <section class="bg-light-gray overflow-hidden">
        <div class="row align-items-center">
            <!-- ------------------------------------------ -->
            <div class="col-lg-12">
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-0">
                        <div id="carouselExampleCaptions" class="carousel slide carousel-dark" data-bs-ride="carousel">
                            <ol class="carousel-indicators">
                                <li data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active"></li>
                                <li data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1"></li>
                                <li data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2"></li>
                            </ol>
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <img src="../assets/images/frontend-pages/carousel/medical.jpg"
                                        class="d-block w-100 carousel-img" alt="TVET Slide 2" />
                                    <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 p-3 rounded">
                                        <h2 class="fw-bold text-white">Medical Courses</h2>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="../assets/images/frontend-pages/foreign_language_2.jpeg"
                                        class="d-block w-100 carousel-img" alt="TVET Slide 3" />
                                    <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 p-3 rounded">
                                        <h2 class="fw-bold text-white">Foreign Language Courses</h2>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="../assets/images/frontend-pages/food_production.jpeg"
                                        class="d-block w-100 carousel-img" alt="TVET Slide 3" />
                                    <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 p-3 rounded">
                                        <h2 class="fw-bold text-white">Food Production Courses</h2>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="../assets/images/frontend-pages/carousel/success.jpg"
                                        class="d-block w-100 carousel-img" alt="TVET Slide 1" />
                                    <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 p-3 rounded">
                                        <h2 class="fw-bold text-white">Success on the Mountain Top</h2>
                                    </div>
                                </div>
                            </div>
                            <a class="carousel-control-prev" href="#carouselExampleCaptions" role="button"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </a>
                            <a class="carousel-control-next" href="#carouselExampleCaptions" role="button"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ------------------------------------------ -->
        </div>
    </section>

    <!-- ------------------------------------- -->
    <!-- Count Start -->
    <!-- ------------------------------------- -->
    <section class="count-section py-7 py-md-14 py-lg-11">
        <div class="container-fluid">
            <div class="row justify-content-between align-items-center">
                <!-- Left Text -->
                <div class="col-lg-5 mb-5 mb-lg-0">
                    <h2 class="count-title mb-4">
                        Over <span class="highlight">5,000</span> students and counting.
                    </h2>
                    <p class="count-subtitle mb-4">
                        Join Kenya's leading Technical and Vocational Education Training institute with global placement
                        opportunities.
                    </p>
                    <a href="{{ route('front-end.contact') }}" class="btn-request-callback">
                        Request a Callback
                    </a>
                </div>

                <!-- Right Stats -->
                <div class="col-lg-6">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="count-card">
                                <div class="icon-wrapper icon-warning">
                                    <iconify-icon icon="mdi:account-group" class="icon"></iconify-icon>
                                </div>
                                <h4>Students</h4>
                                <p>2500+ Students enrolled.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="count-card">
                                <div class="icon-wrapper icon-success">
                                    <iconify-icon icon="mdi:book-open-page-variant" class="icon"></iconify-icon>
                                </div>
                                <h4>Courses</h4>
                                <p>25+ Active Courses.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="count-card">
                                <div class="icon-wrapper icon-info">
                                    <iconify-icon icon="mdi:certificate-outline" class="icon"></iconify-icon>
                                </div>
                                <h4>Certifications</h4>
                                <p>1800+ Certifications Issued.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="count-card">
                                <div class="icon-wrapper icon-primary">
                                    <iconify-icon icon="mdi:earth" class="icon"></iconify-icon>
                                </div>
                                <h4>Partners</h4>
                                <p>15+ Global Partners.</p>
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
    <section class="tabs-section py-7 py-md-14 py-lg-11 bg-light-gray">
        <div class="container-fluid">

            <ul class="nav nav-pills tabs-pills justify-content-between gap-3" id="pills-tab" role="tablist">
                <li class="nav-item flex-grow-1" role="presentation">
                    <button class="nav-link active fs-5 fw-semibold px-4 py-3 tabs-shadow" id="pills-vision-tab"
                        data-bs-toggle="pill" data-bs-target="#pills-vision" type="button" role="tab"
                        aria-controls="pills-vision" aria-selected="false">
                        <iconify-icon icon="mdi:eye-outline" class="fs-6 me-2 icon-accent"></iconify-icon>
                        Vision
                    </button>
                </li>
                <li class="nav-item flex-grow-1" role="presentation">
                    <button class="nav-link fs-5 fw-semibold px-4 py-3 tabs-shadow" id="pills-mission-tab"
                        data-bs-toggle="pill" data-bs-target="#pills-mission" type="button" role="tab"
                        aria-controls="pills-mission" aria-selected="true">
                        <iconify-icon icon="mdi:target" class="fs-6 me-2 icon-accent"></iconify-icon>
                        Mission
                    </button>
                </li>
                <li class="nav-item flex-grow-1" role="presentation">
                    <button class="nav-link fs-5 fw-semibold px-4 py-3 tabs-shadow" id="pills-values-tab"
                        data-bs-toggle="pill" data-bs-target="#pills-values" type="button" role="tab"
                        aria-controls="pills-values" aria-selected="false">
                        <iconify-icon icon="mdi:handshake-outline" class="fs-6 me-2 icon-accent"></iconify-icon>
                        Core Values
                    </button>
                </li>
            </ul>

            <div class="tab-content mt-7 mt-lg-12 pb-lg-9" id="myTabContent">

                <!-- Vision Tab -->
                <div class="tab-pane active" id="pills-vision" role="tabpanel" aria-labelledby="pills-vision-tab"
                    tabindex="0">
                    <div class="row gap-lg-0 gap-7 align-items-center">
                        <div class="col-lg-6">
                            <div class="image-wrapper rounded-24 shadow-soft p-4">
                                <img src="../assets/images/frontend-pages/vision.jpeg" alt="Our Vision"
                                    class="w-100 rounded-24">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="content-wrapper ps-lg-7 ms-lg-8">
                                <h2 class="fs-5 fw-bold mb-3 accent-color">Our Vision</h2>
                                <p class="fs-5 text-muted mb-5">
                                    To be a leading Institute and a model of excellence in the training and development
                                    of globally competent Professionals.</p>
                                <a href="{{ route('front-end.about') }}">
                                    <button class="btn btn-primary px-4 py-2 btn-shadow">Learn More</button>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mission Tab -->
                <div class="tab-pane fade show" id="pills-mission" role="tabpanel"
                    aria-labelledby="pills-mission-tab" tabindex="0">
                    <div class="row gap-lg-0 gap-7 align-items-center">
                        <div class="col-lg-6">
                            <div class="image-wrapper rounded-24 shadow-soft p-4">
                                <img src="../assets/images/frontend-pages/mission.jpeg" alt="Our Mission"
                                    class="w-100 rounded-24">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="content-wrapper ps-lg-7 ms-lg-8">
                                <h2 class="fs-5 fw-bold mb-3 accent-color">Our Mission</h2>
                                <p class="fs-5 text-muted mb-5">
                                    Providing holistic education that fosters competence, compassionate and positive
                                    attitude to meet both local and international markets.
                                </p>
                                <a href="{{ route('front-end.about') }}">
                                    <button class="btn btn-primary px-4 py-2 btn-shadow">Learn More</button>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Core Values Tab -->
                <div class="tab-pane fade" id="pills-values" role="tabpanel" aria-labelledby="pills-values-tab"
                    tabindex="0">
                    <div class="row gap-lg-0 gap-7 align-items-center">
                        <div class="col-lg-6">
                            <div class="image-wrapper rounded-24 shadow-soft p-4">
                                <img src="../assets/images/frontend-pages/core_values.jpeg" alt="Our Core Values"
                                    class="w-100 rounded-24">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="content-wrapper ps-lg-7 ms-lg-8">
                                <h2 class="fs-5 fw-bold mb-3 accent-color">Our Core Values</h2>
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-4">
                                        <p class="fs-5 text-muted mb-4">
                                            These are the deeply held beliefs about what is important or desirable at
                                            Tabor Training Institute
                                        </p>
                                        <ol class="list-group list-group-numbered fs-5">
                                            <li class="list-group-item border-0 px-0 py-2"><b>Commitment to
                                                    excellence:</b> Commitment to Excellence: Demonstrating a consistent
                                                drive to achieve the highest standards in teaching, learning, and
                                                professional conduct.
                                            </li>
                                            <li class="list-group-item border-0 px-0 py-2"><b>Stewardship:</b>
                                                Responsibly managing resources, time, and talents entrusted to us, with
                                                accountability and integrity.
                                            </li>
                                            <li class="list-group-item border-0 px-0 py-2"><b>Responsiveness:</b>
                                                Adapting swiftly and effectively to the needs of students, the
                                                healthcare industry, and societal challenges through innovation and
                                                action.
                                            </li>
                                            <li class="list-group-item border-0 px-0 py-2"><b>Compassion:</b>
                                                Demonstrating empathy, respect, and genuine care in all interactions by
                                                responding to the needs of students, patients, staff, and the community
                                                with understanding, kindness, and professionalism.
                                            </li>
                                        </ol>
                                    </div>
                                </div>
                                <a href="{{ route('front-end.about') }}">
                                    <button class="btn btn-primary px-4 py-2 btn-shadow">Learn More</button>
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
    @include('livewire.front-end.common.team')
    <!-- ------------------------------------- -->

    <!-- Team End -->
    <!-- ------------------------------------- -->



    <!-- ------------------------------------- -->
    <!-- Testimonial Start -->
    <!-- ------------------------------------- -->
    @include('livewire.front-end.common.testimonials')
    <!-- ------------------------------------- -->
    <!-- Testimonial End -->


    <!-- ------------------------------------- -->
    <!-- more of tabor -->
    <!-- ------------------------------------- -->
    <section class="pt-7 pt-md-14 pt-lg-11" style="background-color: #0b2c45">
        <div class="container-fluid">
            <div class="row justify-content-between pb-12 border-bottom">
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-body">
                            <ol class="list-group list-group-numbered">

                                <li class="mt-3 d-flex justify-content-between align-items-start m-0">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-semibold text-dark fs-5">Online Learning</div>
                                        <div class="text-muted fs-6">Interactive digital courses with virtual labs and
                                            simulations
                                        </div>
                                    </div>
                                    <span
                                        class="badge bg-success rounded-circle fs-6 d-flex align-items-center justify-content-center"
                                        style="width: 28px; height: 28px;">✓</span>
                                </li>

                                <li class="mt-3 d-flex justify-content-between align-items-start m-0">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-semibold text-dark fs-5">In-Person Training</div>
                                        <div class="text-muted fs-6">Hands-on practical training in state-of-the-art
                                            facilities
                                        </div>
                                    </div>
                                    <span
                                        class="badge bg-success rounded-circle fs-6 d-flex align-items-center justify-content-center"
                                        style="width: 28px; height: 28px;">✓</span>
                                </li>

                                <li class="mt-3 d-flex justify-content-between align-items-start m-0">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-semibold text-dark fs-5">Blended Learning</div>
                                        <div class="text-muted fs-6">Combination of online theory and practical
                                            workshops
                                        </div>
                                    </div>
                                    <span
                                        class="badge bg-success rounded-circle fs-6 d-flex align-items-center justify-content-center"
                                        style="width: 28px; height: 28px;">✓</span>
                                </li>

                                <li class="mt-3 d-flex justify-content-between align-items-start m-0">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-semibold text-dark fs-5">Global Placement</div>
                                        <div class="text-muted fs-6">International job placement assistance and career
                                            support
                                        </div>
                                    </div>
                                    <span
                                        class="badge bg-success rounded-circle fs-6 d-flex align-items-center justify-content-center"
                                        style="width: 28px; height: 28px;">✓</span>
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
    @include('livewire.front-end.common.focus')
    <!-- ------------------------------------- -->
    <!-- Focus End -->
    <!-- ------------------------------------- -->

    <!-- ------------------------------------- -->
    <!-- Partners & Accreditation Start -->
    <!-- ------------------------------------- -->
    @include('livewire.front-end.common.partners')
    <!-- ------------------------------------- -->
    <!-- Partners & Accreditation End -->
    <!-- ------------------------------------- -->
</div>
