<?php

namespace App\Livewire;

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app.frontend')] class extends Component {}; ?>

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
                    <a href="../main/frontend-landingpage.html"
                        class="text-muted fw-bolder link-primary fs-3 text-uppercase">
                        Tabor
                    </a>
                    <iconify-icon icon="solar:alt-arrow-right-outline" class="fs-5 text-muted"></iconify-icon>
                    <a href="#" class="text-primary link-primary fw-bolder fs-3 text-uppercase">
                        Contact Us
                    </a>
                </div>
            </div>
            <div class="mt-4 mt-md-5 mt-lg-12">
                <iframe class="rounded-3"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3874.5782400289413!2d36.83165577482915!3d-1.2675908987203388!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f16dc9f2039cb%3A0x970dde5d44f9d97d!2sShowbe%20Plaza%20-%20Block%20C!5e1!3m2!1sen!2ske!4v1754067363436!5m2!1sen!2ske"
                    width="100%" height="439" style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
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
                                +254 798496129<br>
                                +254 726241095
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
                            <hr>
                            <p class="fs-4 text-white mb-0">
                                A Saturday class Training option is also available on special arrangements (8am-4pm)
                            </p>
                        </div>
                    </div>
                    <!-- Need Help -->
                    <div class="py-9 d-flex flex-column gap-4 border-bottom">
                        <div class="d-flex flex-column gap-3">
                            {{-- <a class="btn btn-primary d-block w-100" href="../main/authentication-register.html">
                                Shedule Campus Visit
                            </a> --}}
                            <a class="btn btn-outline-success d-block w-100" target="_blank"
                                href="https://api.whatsapp.com/send?phone=254798496129&text=Hello Tabor Training Institute.
I'm reaching out to enquire more about the courses you offer.
Thanks!">
                                <iconify-icon icon="mdi:whatsapp"></iconify-icon>
                                <span class="mb-1">Whatsapp</span>
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
                                        <input type="text" name="Fname" id="Fname" placeholder="First Name"
                                            class="form-control">
                                    </div>
                                    <div class="d-flex flex-column flex-grow-1 gap-2">
                                        <label for="Lname" class="fs-3 fw-semibold">
                                            Last Name *
                                        </label>
                                        <input type="text" name="Lname" id="Lname" placeholder="Last name"
                                            class="form-control">
                                    </div>
                                </div>
                                <div class="d-flex flex-sm-row flex-column gap-sm-7 gap-3">
                                    <div class="d-flex flex-column flex-grow-1 gap-2">
                                        <label for="phone" class="fs-3 fw-semibold">
                                            Phone Number *
                                        </label>
                                        <input type="tel" name="phone" id="phone" placeholder="XXX XXX XXXX"
                                            class="form-control">
                                    </div>
                                    <div class="d-flex flex-column flex-grow-1 gap-2">
                                        <label for="email" class="fs-3 fw-semibold">
                                            Email *
                                        </label>
                                        <input type="email" name="email" id="email" placeholder="Email"
                                            class="form-control">
                                    </div>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <label for="enquire" class="fs-3 fw-semibold">Enquire related to *</label>
                                    <select class="form-select w-auto">
                                        <option value="1">General Enquiry</option>
                                        <option value="2">Customer Service Enquiry</option>
                                        <option value="3">Available Courses & Intakes</option>
                                    </select>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <label for="message" class="fs-3 fw-semibold">Message</label>
                                    <textarea name="message" id="message" class="form-control" rows="5"></textarea>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end align-items-center">
                                <button type="submit" class="btn btn-primary mt-sm-7 mt-3 px-9 py-6">Send
                                    Message
                                </button>
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
    <!-- Focus Start -->
    <!-- ------------------------------------- -->
    @include('livewire.front-end.common.focus')
    <!-- ------------------------------------- -->
    <!-- Focus End -->
    <!-- ------------------------------------- -->
</div>
