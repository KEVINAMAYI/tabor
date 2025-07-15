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
    <section class="py-7 py-md-5 bg-light-gray">
        <div class="container-fluid">
            <div class="d-flex justify-content-between flex-md-nowrap flex-wrap">
                <h2 class="fs-15 fw-bolder mb-0">
                    We’d love to hear from you
                </h2>
                <div class="d-flex align-items-center gap-6">
                    <a href="../main/frontend-landingpage.html" class="text-muted fw-bolder link-primary fs-3 text-uppercase">
                        Tabor
                    </a>
                    <iconify-icon icon="solar:alt-arrow-right-outline" class="fs-5 text-muted"></iconify-icon>
                    <a href="#" class="text-primary link-primary fw-bolder fs-3 text-uppercase">
                        Contact Us
                    </a>
                </div>
            </div>
            <div class="mt-4 mt-md-5 mt-lg-12">
                <iframe class="rounded-3" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d193595.2527998699!2d-74.14448787425354!3d40.697631233397885!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c24fa5d33f083b%3A0xc80b8f06e177fe62!2sNew%20York%2C%20NY%2C%20USA!5e0!3m2!1sen!2sin!4v1727857429230!5m2!1sen!2sin" width="100%" height="439" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- Banner End -->
    <!-- ------------------------------------- -->

    <!-- ------------------------------------- -->
    <!-- Form Start -->
    <!-- ------------------------------------- -->
    <section class="py-lg-12 py-7 bg-light-gray">
        <div class="container-fluid">
            <div class="row gx-lg-7 gy-lg-0 gy-7">
                <div class="col-lg-4">
                    <div class="bg-primary p-7 rounded-4 position-relative bg-circle overflow-hidden">
                        <!-- Contact Info Heading -->
                        <div class="pb-10 border-bottom border-white border-opacity-10 position-relative z-1">
                            <h3 class="text-white fs-6 fw-bolder mb-3">
                                <iconify-icon icon="mdi:account-box-outline" class="me-2 fs-4"></iconify-icon>
                                Contact Information
                            </h3>
                            <p class="fs-4 mb-0 text-white">
                                We're here to help. Get in touch with us directly.
                            </p>
                        </div>

                        <!-- Address -->
                        <div class="pt-6 position-relative z-1">
                            <h4 class="text-white fs-6 fw-bolder mb-2">
                                <iconify-icon icon="mdi:map-marker-outline" class="me-2 fs-5"></iconify-icon>
                                Address
                            </h4>
                            <p class="fs-4 text-white mb-0">
                                Showbe Plaza, Pangani<br>
                                Thika Highway, Nairobi<br>
                                Kenya
                            </p>
                        </div>

                        <!-- Phone -->
                        <div class="pt-6 position-relative z-1">
                            <h4 class="text-white fs-6 fw-bolder mb-2">
                                <iconify-icon icon="mdi:phone-outline" class="me-2 fs-5"></iconify-icon>
                                Phone Numbers
                            </h4>
                            <p class="fs-4 text-white mb-0">
                                +254 798 496129<br>
                                +254 726 241095
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="pt-6 position-relative z-1">
                            <h4 class="text-white fs-6 fw-bolder mb-2">
                                <iconify-icon icon="mdi:email-outline" class="me-2 fs-5"></iconify-icon>
                                Email
                            </h4>
                            <p class="fs-4 text-white mb-0">
                                office@tabor.ac.ke<br>
                                admissions@tabor.ac.ke
                            </p>
                        </div>

                        <!-- Office Hours -->
                        <div class="pt-6 position-relative z-1">
                            <h4 class="text-white fs-6 fw-bolder mb-2">
                                <iconify-icon icon="mdi:clock-outline" class="me-2 fs-5"></iconify-icon>
                                Office Hours
                            </h4>
                            <p class="fs-4 text-white mb-0">
                                Monday – Friday<br>
                                08:00 – 17:00
                            </p>
                        </div>
                    </div>
                    <!-- Need Help -->
                    <div class="py-9 d-flex flex-column gap-4 border-bottom">
                        <div class="d-flex flex-column gap-3">
                            <a class="btn btn-primary d-block w-100" href="../main/authentication-register.html">
                                Shedule Campus Visit
                            </a>
                            <a class="btn btn-outline-success d-block w-100" href="javascript:void(0)">
                                <iconify-icon icon="mdi:chat-outline" class="me-2"></iconify-icon> Live Chat
                            </a>
                            <a class="btn btn-outline-warning d-block w-100" href="javascript:void(0)">
                                <iconify-icon icon="mdi:phone-in-talk-outline" class="me-2"></iconify-icon> Request Call Back
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="bg-white p-7 rounded-3">
                        <form>
                            <div class="d-flex flex-column gap-sm-7 gap-3">
                                <div class="d-flex flex-sm-row flex-column gap-sm-7 gap-3">
                                    <div class="d-flex flex-column flex-grow-1 gap-2">
                                        <label for="Fname" class="fs-3 fw-semibold">
                                            First Name *
                                        </label>
                                        <input type="text" name="Fname" id="Fname" placeholder="First Name" class="form-control">
                                    </div>
                                    <div class="d-flex flex-column flex-grow-1 gap-2">
                                        <label for="Lname" class="fs-3 fw-semibold">
                                            Last Name *
                                        </label>
                                        <input type="text" name="Lname" id="Lname" placeholder="Last name" class="form-control">
                                    </div>
                                </div>
                                <div class="d-flex flex-sm-row flex-column gap-sm-7 gap-3">
                                    <div class="d-flex flex-column flex-grow-1 gap-2">
                                        <label for="phone" class="fs-3 fw-semibold">
                                            Phone Number *
                                        </label>
                                        <input type="tel" name="phone" id="phone" placeholder="XXX XXX XXXX" class="form-control">
                                    </div>
                                    <div class="d-flex flex-column flex-grow-1 gap-2">
                                        <label for="email" class="fs-3 fw-semibold">
                                            Email *
                                        </label>
                                        <input type="email" name="email" id="email" placeholder="Email" class="form-control">
                                    </div>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <label for="enquire" class="fs-3 fw-semibold">Enquire related to *</label>
                                    <select class="form-select w-auto">
                                        <option value="1">General Enquiry</option>
                                        <option value="2">Customer Service Enquiry</option>
                                        <option value="3">Legal Enquiry</option>
                                        <option value="4">General Enquiry</option>
                                    </select>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <label for="message" class="fs-3 fw-semibold">Message</label>
                                    <textarea name="message" id="message" class="form-control" rows="5"></textarea>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end align-items-center">
                                <button type="submit" class="btn btn-primary mt-sm-7 mt-3 px-9 py-6">Send
                                    Message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ------------------------------------- -->
    <!-- Form End -->
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
