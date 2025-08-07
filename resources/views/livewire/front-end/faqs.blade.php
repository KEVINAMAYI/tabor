<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Intake;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new #[Layout('components.layouts.app.frontend')] class extends Component {};
?>
<div class="main-wrapper overflow-hidden">
    


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
                                <button class="accordion-button fs-5" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    What are the entry requirements?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show"
                                data-bs-parent="#accordionExample1">
                                <div class="accordion-body">
                                    <p>
                                        Entry requirements vary by course. Most certificate programs require a minimum
                                        of KCSE D+, while diploma programs generally require KCSE C-. Some technical or
                                        professional courses may have specific subject requirements or prerequisites.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fs-5" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false"
                                    aria-controls="collapseTwo">
                                    When can I start my course?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse"
                                data-bs-parent="#accordionExample1">
                                <div class="accordion-body">
                                    <p>
                                        We have multiple intakes throughout the year — typically in January, May, and
                                        September. Please refer to the specific course page for upcoming intake dates
                                        and deadlines.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fs-5" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false"
                                    aria-controls="collapseThree">
                                    Can I pay in installments?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse"
                                data-bs-parent="#accordionExample1">
                                <div class="accordion-body">
                                    <p>
                                        Yes! We offer flexible payment plans. You can pay your fees in 2 to 4
                                        installments depending on the course duration and fee structure. For more
                                        information, contact our admissions office or finance department.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fs-5" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false"
                                    aria-controls="collapseFour">
                                    Do you offer online or evening classes?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse"
                                data-bs-parent="#accordionExample1">
                                <div class="accordion-body">
                                    <p>
                                        Yes, selected courses are available through blended learning (online +
                                        in-person) or evening/weekend schedules for working professionals. Availability
                                        depends on the specific program.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fs-5" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false"
                                    aria-controls="collapseFive">
                                    Is the institution accredited?
                                </button>
                            </h2>
                            <div id="collapseFive" class="accordion-collapse collapse"
                                data-bs-parent="#accordionExample1">
                                <div class="accordion-body">
                                    <p>
                                        Absolutely. We are registered and accredited by relevant education and training
                                        authorities, including TVET and NITA, ensuring recognized and credible
                                        certification upon graduation.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fs-5" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false"
                                    aria-controls="collapseSix">
                                    How do I apply for a course?
                                </button>
                            </h2>
                            <div id="collapseSix" class="accordion-collapse collapse"
                                data-bs-parent="#accordionExample1">
                                <div class="accordion-body">
                                    <p>
                                        You can apply online through our application portal, or visit our admissions
                                        office. Simply select your preferred course, submit the required documents, and
                                        complete the registration process. Support is available throughout your
                                        application journey.
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="d-flex justify-content-center align-items-center mt-12">
                        <p class="text-center fs-3 fw-bold dashed-border py-1 px-2 rounded mb-0 text-muted">
                            Still have a question?
                            <a target="_blank" href="mailto:admissions@collegeexample.ac.ke"
                                class="text-underline text-muted link-primary">Email Us</a> or
                            <a target="_blank" href="https://wa.me/254712345678"
                                class="text-underline text-muted link-primary">Chat on WhatsApp</a>.
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

    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="termsModalLabel">Terms and Conditions</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h3 class="fs-4 fw-bold mb-3">Data Privacy Consent Statement</h3>
                    <p class="fs-4 mb-4">
                        By submitting this application, I hereby acknowledge and consent to the collection, use, and
                        processing of my personal data by Tabor Training Institute (TTI) for the purposes of student
                        registration, academic administration, institutional communication, and related educational
                        services.
                    </p>
                    <p class="fs-4 mb-4">
                        I understand and agree that:
                    </p>
                    <ul class="fs-3 mb-4">
                        <li>My personal information, including but not limited to my name, contact details, academic
                            history, and identification documents, will be securely stored and processed in accordance
                            with the Data Protection Act, 2019 (Kenya) and applicable privacy regulations.</li>
                        <li>This data may be shared with relevant government bodies, accrediting agencies, examination
                            councils, or third-party service providers only where necessary and with appropriate
                            safeguards.</li>
                        <li>During the course of my studies, photographs and videos may be taken of me during class
                            activities, events, or institutional functions. These may be used in TTI’s official social
                            media pages, website, newsletters, promotional materials, and other awareness or
                            communication initiatives.</li>
                        <li>Such media will be used respectfully and in a manner that promotes the positive image of the
                            Institute and its programs.</li>
                        <li>I have the right to access, correct, or request the deletion of my personal data, subject to
                            applicable laws and institutional policies.</li>
                        <li>Tabor Training Institute will not use my personal data for unrelated purposes without my
                            explicit consent.</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</div>
