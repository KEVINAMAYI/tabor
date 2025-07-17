<?php

namespace App\Livewire;

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app.frontend')] class extends Component
{} ?>

<div class="main-wrapper overflow-hidden">
    <!-- ------------------------------------- -->
    <!-- Banner Start -->
    <!-- ------------------------------------- -->
    <section class="pt-1  bg-light-gray">
        <div class="container-fluid">
            <div class="mt-3 px-4 py-5 rounded-3 text-center" style="background-color: #004080; color: #ffffff;">
                <h2 class="text-white fw-bold mb-3">Apply Now</h2>
                <p class="fs-5 mb-0">
                    Take the first step towards your career transformation with our streamlined application process.
                </p>
            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- Banner End -->
    <!-- ------------------------------------- -->

    <!-- ------------------------------------- -->
    <!-- Details Start -->
    <!-- ------------------------------------- -->
    <section class="pt-md-13 pb-md-11 bg-light-gray">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-9">
                    <div class="card">
                        <div class="card-body wizard-content">
                            <h4 class="card-title mb-0">Online Course Application</h4>

                            <form action="#" class="tab-wizard wizard-circle">
                                <!-- Step 1: Choose Course -->
                                <h6>Choose Course</h6>
                                <section>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Select Course:</label>
                                                <select class="form-select">
                                                    <option value="">Select Course</option>
                                                    <option>Diploma in ICT</option>
                                                    <option>Certificate in Business Management</option>
                                                    <option>Diploma in Culinary Arts</option>
                                                    <option>Certificate in Accounting</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Preferred Intake:</label>
                                                <select class="form-select">
                                                    <option>March 2025</option>
                                                    <option>June 2025</option>
                                                    <option>September 2025</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">Preferred Mode of Study:</label>
                                                <select class="form-select">
                                                    <option>In Person</option>
                                                    <option>Hybrid</option>
                                                    <option>Online</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <!-- Step 2: Personal Info -->
                                <h6>Personal Information</h6>
                                <section>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">First Name:</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Last Name:</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Email:</label>
                                                <input type="email" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Phone:</label>
                                                <input type="tel" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Address:</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Country:</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">Highest Level of Education:</label>
                                                <select class="form-select">
                                                    <option>KCSE</option>
                                                    <option>Diploma</option>
                                                    <option>Degree</option>
                                                    <option>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <!-- Step 3: Upload Documents -->
                                <h6>Upload Documents</h6>
                                <section>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">National ID / Passport:</label>
                                                <input type="file" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">KCSE Certificate:</label>
                                                <input type="file" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">Passport-size Photo:</label>
                                                <input type="file" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <!-- Step 4: Payment and Submit -->
                                <h6>Payment & Submit</h6>
                                <section>
                                    <div class="mb-4">
                                        <h5 class="fw-bold">Confirm Your Details</h5>
                                        <p>Please review all information entered above before selecting a payment option.</p>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="terms">
                                        <label class="form-check-label" for="terms">
                                            I agree to the <a href="#" class="text-decoration-underline">Terms and Conditions</a> and confirm that all information provided is accurate.
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-success">Submit Application</button>
                                </section>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 mb-7 mb-lg-0">
                    <div class="d-flex flex-column gap-3 bg-white p-7 rounded-3">
                        <div class="py-9 d-flex flex-column gap-3 border-bottom">
                            <h4 class="fs-3 fw-bold  text-muted mb-0 ">Quick Links</h4>
                            <div>
                                <a class="btn btn-primary d-block w-100 mb-3" href="../main/authentication-register.html">
                                    Apply Now
                                </a>
                                <a class="btn btn-outline-primary d-block w-100" href="javascript:void(0)">
                                    Download Brochure
                                </a>
                            </div>
                        </div>
                        <div class="py-9 d-flex flex-column gap-4 border-bottom">
                            <h4 class="fs-3 fw-bold text-muted mb-0">Need Help</h4>

                            <!-- Contact Info -->
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <iconify-icon icon="mdi:phone-outline" class="text-primary fs-4"></iconify-icon>
                                    <span class="fs-3 text-dark">+254 712 345 678</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <iconify-icon icon="mdi:email-outline" class="text-primary fs-4"></iconify-icon>
                                    <span class="fs-3 text-dark">info@collegeexample.ac.ke</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex flex-column gap-3">
                                <a class="btn btn-primary d-block w-100" href="../main/authentication-register.html">
                                    Apply Now
                                </a>
                                <a class="btn btn-outline-success d-block w-100" href="javascript:void(0)">
                                    <iconify-icon icon="mdi:chat-outline" class="me-2"></iconify-icon> Live Chat
                                </a>
                                <a class="btn btn-outline-warning d-block w-100" href="javascript:void(0)">
                                    <iconify-icon icon="mdi:phone-in-talk-outline" class="me-2"></iconify-icon> Request Call Back
                                </a>
                            </div>
                        </div>

                        <div class="py-9">
                            <h4 class="text-uppercase fs-3 fw-bold">Share</h4>
                            <div class="d-flex gap-6">
                                <a href="#" class="border rounded-circle round-40 hstack justify-content-center" data-bs-toggle="tooltip" data-bs-title="Facebook">
                                    <img src="../assets/images/frontend-pages/icon-facebook-dark.svg" alt="facebook">
                                </a>
                                <a href="#" class="border rounded-circle round-40 hstack justify-content-center" data-bs-toggle="tooltip" data-bs-title="Instagram">
                                    <img src="../assets/images/frontend-pages/icon-instagram-dark.svg" alt="instagram">
                                </a>
                                <a href="#" class="border rounded-circle round-40 hstack justify-content-center" data-bs-toggle="tooltip" data-bs-title="YouTube">
                                    <img src="../assets/images/frontend-pages/icon-youtube-dark.svg" alt="youtube">
                                </a>
                                <a href="#" class="border rounded-circle round-40 hstack justify-content-center" data-bs-toggle="tooltip" data-bs-title="Linckedin">
                                    <img src="../assets/images/frontend-pages/icon-linckedin-dark.svg" alt="linckedin">
                                </a>
                            </div>
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
    <!-- FAQ Start -->
    <!-- ------------------------------------- -->
    <section class="pb-lg-11 py-5 pb-5">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="fs-15 fw-bolder mb-0 text-center mb-5 mb-md-12">
                        Frequently asked questions
                    </h2>
                    <div class="accordion faq-accordion" id="accordionExample1">

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button fs-5" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    What are the entry requirements?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionExample1">
                                <div class="accordion-body">
                                    <p>
                                        Entry requirements vary by course. Most certificate programs require a minimum of KCSE D+, while diploma programs generally require KCSE C-. Some technical or professional courses may have specific subject requirements or prerequisites.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fs-5" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    When can I start my course?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample1">
                                <div class="accordion-body">
                                    <p>
                                        We have multiple intakes throughout the year — typically in January, May, and September. Please refer to the specific course page for upcoming intake dates and deadlines.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fs-5" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Can I pay in installments?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionExample1">
                                <div class="accordion-body">
                                    <p>
                                        Yes! We offer flexible payment plans. You can pay your fees in 2 to 4 installments depending on the course duration and fee structure. For more information, contact our admissions office or finance department.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fs-5" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    Do you offer online or evening classes?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#accordionExample1">
                                <div class="accordion-body">
                                    <p>
                                        Yes, selected courses are available through blended learning (online + in-person) or evening/weekend schedules for working professionals. Availability depends on the specific program.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fs-5" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    Is the institution accredited?
                                </button>
                            </h2>
                            <div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#accordionExample1">
                                <div class="accordion-body">
                                    <p>
                                        Absolutely. We are registered and accredited by relevant education and training authorities, including TVET and NITA, ensuring recognized and credible certification upon graduation.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fs-5" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                    How do I apply for a course?
                                </button>
                            </h2>
                            <div id="collapseSix" class="accordion-collapse collapse" data-bs-parent="#accordionExample1">
                                <div class="accordion-body">
                                    <p>
                                        You can apply online through our application portal, or visit our admissions office. Simply select your preferred course, submit the required documents, and complete the registration process. Support is available throughout your application journey.
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="d-flex justify-content-center align-items-center mt-12">
                        <p class="text-center fs-3 fw-bold dashed-border py-1 px-2 rounded mb-0 text-muted">
                            Still have a question?
                            <a target="_blank" href="mailto:admissions@collegeexample.ac.ke" class="text-underline text-muted link-primary">Email Us</a> or
                            <a target="_blank" href="https://wa.me/254712345678" class="text-underline text-muted link-primary">Chat on WhatsApp</a>.
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- FAQ End -->
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

@push('scripts')
    <script src="../assets/libs/jquery-steps/build/jquery.steps.min.js"></script>
    <script src="../assets/libs/jquery-validation/dist/jquery.validate.min.js"></script>
    <script src="../assets/js/forms/form-wizard.js"></script>
@endpush

