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
                                <a class="btn btn-primary" href="{{ route('front-end.courses') }}">
                                    Explore Programs
                                </a>
                                <a class="btn btn-outline-primary" href="../assets/images/frontend-pages/course_guide.jpeg" target="_blank">
                                    Download Course Guide
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


    <!-- ------------------------------------- -->
    <!-- Team Start -->
    <!-- ------------------------------------- -->
    @include('livewire.front-end.common.team')

    <!-- ------------------------------------- -->
    <!-- Accreditation & Partners Start -->
    <!-- ------------------------------------- -->

    @include('livewire.front-end.common.partners')

    <!-- ------------------------------------- -->
    <!-- Accreditation & Partners End -->
    <!-- ------------------------------------- -->


    <!-- ------------------------------------- -->
    <!-- Testimonial Start -->
    <!-- ------------------------------------- -->
    @include('livewire.front-end.common.testimonials')
    <!-- ------------------------------------- -->
    <!-- Testimonial End -->
    <!-- ------------------------------------- -->

    <!-- ------------------------------------- -->
    <!-- Focus Start -->
    <!-- ------------------------------------- -->
    @include('livewire.front-end.common.focus')
    <!-- ------------------------------------- -->
    <!-- Focus End -->
    <!-- ------------------------------------- -->
</div>


