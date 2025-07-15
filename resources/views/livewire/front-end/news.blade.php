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
                <h2 class="text-white fw-bold mb-3">News and Events</h2>
                <p class="fs-5 mb-0">
                    Stay updated with the latest news, announcements, and upcoming events at Tabor Training Institute.
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
                <!-- Card 1 -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="position-relative">
                            <a href="javascript:void(0)">
                                <img src="../assets/images/blog/blog-img2.jpg" class="card-img-top" alt="tech-news">
                            </a>
                            <span class="badge text-bg-light text-dark fs-2 lh-sm mb-9 me-9 py-1 px-2 fw-semibold position-absolute bottom-0 end-0">3 min Read</span>
                            <img src="../assets/images/profile/user-2.jpg" alt="author" class="img-fluid rounded-circle position-absolute bottom-0 start-0 mb-n9 ms-9" width="40" height="40" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Daniel Kipkoech">
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                            <span class="badge text-bg-light fs-2 py-1 px-2 lh-sm mt-3">Technology</span>
                            <a class="d-block my-3 fs-5 text-dark fw-semibold link-primary" href="#">
                                Kenya’s Tech Startups Disrupting the Education Sector
                            </a>
                            <p class="mb-3 text-muted">
                                Local startups are leveraging mobile platforms and AI to revolutionize how students access quality education.
                            </p>
                            <a href="#" class="btn btn-outline-primary w-50 mt-auto">Read More</a>
                            <div class="d-flex align-items-center gap-4 mt-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ti ti-eye text-dark fs-5"></i>5,210
                                </div>
                                <div class="d-flex align-items-center fs-2 ms-auto">
                                    <i class="ti ti-point text-dark"></i>Thu, Jan 4
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Card 2 -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="position-relative">
                            <a href="javascript:void(0)">
                                <img src="../assets/images/blog/blog-img3.jpg" class="card-img-top" alt="education-news">
                            </a>
                            <span class="badge text-bg-light text-dark fs-2 lh-sm mb-9 me-9 py-1 px-2 fw-semibold position-absolute bottom-0 end-0">4 min Read</span>
                            <img src="../assets/images/profile/user-5.jpg" alt="author" class="img-fluid rounded-circle position-absolute bottom-0 start-0 mb-n9 ms-9" width="40" height="40" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Nelly Muthoni">
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                            <span class="badge text-bg-light fs-2 py-1 px-2 lh-sm mt-3">Education</span>
                            <a class="d-block my-3 fs-5 text-dark fw-semibold link-primary" href="#">
                                How Online Learning is Empowering Rural Students in Kenya
                            </a>
                            <p class="mb-3 text-muted">
                                E-learning platforms are bridging the gap for students in marginalized areas by providing flexible, affordable learning.
                            </p>
                            <a href="#" class="btn btn-outline-primary w-50 mt-auto">Read More</a>
                            <div class="d-flex align-items-center gap-4 mt-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ti ti-eye text-dark fs-5"></i>7,840
                                </div>
                                <div class="d-flex align-items-center fs-2 ms-auto">
                                    <i class="ti ti-point text-dark"></i>Sun, Feb 11
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                        <!-- Card 1 -->
                        <div class="col-12">
                            <div class="card flex-row">
                                <img src="../assets/images/blog/blog-img2.jpg" class="img-fluid rounded-start" alt="Card image" style="width: 200px; object-fit: cover;">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fw-bold">Kenyan Startups Revolutionizing EdTech</h5>
                                    <p class="card-text text-muted">
                                        Discover how local innovators are making digital learning more accessible across Kenya’s schools.
                                    </p>
                                    <a href="#" class="btn btn-outline-primary w-50 mt-2">Read More</a>
                                    <div class="d-flex gap-4 mt-4">
                                        <div><i class="ti ti-eye text-dark fs-5"></i> 5,210</div>
                                        <div><i class="ti ti-message-2 text-dark fs-5"></i> 12</div>
                                        <div class="ms-auto"><i class="ti ti-point text-dark"></i> Jan 10, 2025</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2 -->
                        <div class="col-12">
                            <div class="card flex-row">
                                <img src="../assets/images/blog/blog-img3.jpg" class="img-fluid rounded-start" alt="Card image" style="width: 200px; object-fit: cover;">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fw-bold">Online Degrees Empowering Women in STEM</h5>
                                    <p class="card-text text-muted">
                                        More women are now pursuing science and tech careers thanks to flexible online programs.
                                    </p>
                                    <a href="#" class="btn btn-outline-primary w-50 mt-2">Read More</a>
                                    <div class="d-flex gap-4 mt-4">
                                        <div><i class="ti ti-eye text-dark fs-5"></i> 4,870</div>
                                        <div><i class="ti ti-message-2 text-dark fs-5"></i> 8</div>
                                        <div class="ms-auto"><i class="ti ti-point text-dark"></i> Feb 2, 2025</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3 -->
                        <div class="col-12">
                            <div class="card flex-row">
                                <img src="../assets/images/blog/blog-img4.jpg" class="img-fluid rounded-start" alt="Card image" style="width: 200px; object-fit: cover;">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fw-bold">Rural Students Benefit from Digital Libraries</h5>
                                    <p class="card-text text-muted">
                                        Offline-first mobile libraries are changing the way students in remote regions learn.
                                    </p>
                                    <a href="#" class="btn btn-outline-primary w-50 mt-2">Read More</a>
                                    <div class="d-flex gap-4 mt-4">
                                        <div><i class="ti ti-eye text-dark fs-5"></i> 6,110</div>
                                        <div><i class="ti ti-message-2 text-dark fs-5"></i> 15</div>
                                        <div class="ms-auto"><i class="ti ti-point text-dark"></i> Mar 5, 2025</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4 -->
                        <div class="col-12">
                            <div class="card flex-row">
                                <img src="../assets/images/blog/blog-img5.jpg" class="img-fluid rounded-start" alt="Card image" style="width: 200px; object-fit: cover;">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fw-bold">University Partners with Google for Tech Upskilling</h5>
                                    <p class="card-text text-muted">
                                        A new collaboration brings cloud and AI training to university students across Africa.
                                    </p>
                                    <a href="#" class="btn d-block btn-outline-primary w-50 mt-2">Read More</a>
                                    <div class="d-flex gap-4 mt-4">
                                        <div><i class="ti ti-eye text-dark fs-5"></i> 8,900</div>
                                        <div><i class="ti ti-message-2 text-dark fs-5"></i> 20</div>
                                        <div class="ms-auto"><i class="ti ti-point text-dark"></i> Apr 18, 2025</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
                <div class="col-lg-4 mb-7 mb-lg-0">
                    <div class="d-flex flex-column gap-3 bg-white p-7 rounded-3">

                        <!-- Upcoming Events -->
                        <div class="py-9 d-flex flex-column gap-4 border-bottom">
                            <h4 class="fs-3 fw-bold text-muted mb-0">Upcoming Events</h4>

                            <div class="d-flex flex-column gap-2">
                                <h5 class="fs-4 fw-semibold text-dark">Career Open Day</h5>
                                <p class="mb-2 text-muted">Explore career paths and meet industry professionals at our annual Career Day.</p>
                                <div class="d-flex align-items-center gap-2">
                                    <iconify-icon icon="mdi:calendar" class="text-primary fs-4"></iconify-icon>
                                    <span class="fs-3 text-dark">Friday, Sept 20, 2025</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <iconify-icon icon="mdi:clock-outline" class="text-success fs-4"></iconify-icon>
                                    <span class="fs-3 text-dark">10:00 AM – 3:00 PM</span>
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-2 border-top pt-4 mt-3">
                                <h5 class="fs-4 fw-semibold text-dark">Digital Skills Bootcamp</h5>
                                <p class="mb-2 text-muted">Join our hands-on workshop and learn key digital tools in just 2 days.</p>
                                <div class="d-flex align-items-center gap-2">
                                    <iconify-icon icon="mdi:calendar" class="text-primary fs-4"></iconify-icon>
                                    <span class="fs-3 text-dark">Monday, Oct 7, 2025</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <iconify-icon icon="mdi:clock-outline" class="text-success fs-4"></iconify-icon>
                                    <span class="fs-3 text-dark">9:00 AM – 4:00 PM</span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Links -->
                        <div class="py-9 d-flex flex-column gap-3 border-bottom">
                            <h4 class="fs-3 fw-bold text-muted mb-0">Quick Links</h4>
                            <div>
                                <a class="btn btn-primary d-block w-100 mb-3" href="../main/authentication-register.html">
                                    Apply January Intake
                                </a>
                                <a class="btn btn-outline-primary d-block w-100 mb-3" href="javascript:void(0)">
                                    Scholarship Information
                                </a>
                                <a class="btn btn-outline-primary d-block w-100" href="javascript:void(0)">
                                    Browse Course
                                </a>
                            </div>
                        </div>

                        <!-- Need Help -->
                        <div class="py-9 d-flex flex-column gap-4 border-bottom">
                            <h4 class="fs-3 fw-bold text-muted mb-0">Need Help</h4>
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

                        <!-- Social Share -->
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

