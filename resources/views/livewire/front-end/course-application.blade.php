<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Intake;
use App\Models\Student;
use App\Models\User;
use App\Notifications\EnrollmentStatus;
use Illuminate\Support\Facades\Hash;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new #[Layout('components.layouts.app.frontend')]
class extends Component {

    use WithFileUploads;

    // Declare variables that will be used in the component
    public $first_name, $last_name, $admission_number, $email, $phone_number, $address, $country, $highest_level_of_education, $status;
    public $selected_course_id, $selected_intake_id, $id_url, $kcse_certificate, $passport_size_url, $date_of_birth;
    public $courses = [], $intakes = [];
    public $terms = false;
    public $step = 1;

    public function mount(?int $course_id = null)
    {
        // Load all courses
        $this->courses = Course::all();
        $this->selected_course_id = !empty($course_id) ? $course_id : $this->courses->first()->id;

        /* $this->intakes = Intake::whereHas('intakeModules.module', function ($query) use ($course_id) {
            $query->where('course_id', $course_id);
        })->distinct()->get(); */

        $this->intakes = Intake::all();


        $this->selected_intake_id = $this->intakes->first()->id ?? null;
    }


    public function addStudent()
    {

        $this->validateStep(1);
        $this->validateStep(2);
        $this->validateStep(3);
        $this->validateStep(4);

        try {
            DB::beginTransaction();

            // Create the user
            $user = User::create([
                'name' => $this->first_name . ' ' . $this->last_name,
                'email' => $this->email,
                'password' => Hash::make($this->phone_number),
            ]);

            $admission_number = Student::generateAdmissionNumber();
            // Set the admission number
            $this->admission_number = $admission_number;

            // Create the student
            $student = Student::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'admission_number' => $this->admission_number,
                'email' => $this->email,
                'phone' => $this->phone_number,
                'dob' => $this->date_of_birth,
                'address' => $this->address,
                'country' => $this->country,
                'highest_level_of_education' => $this->highest_level_of_education,
                'id_url' => $this->id_url ? $this->id_url->store('students/ids', 'public') : null,
                'kcse_certificate' => $this->kcse_certificate ? $this->kcse_certificate->store('students/certificates', 'public') : null,
                'passport_size_url' => $this->passport_size_url ? $this->passport_size_url->store('students/passport_size', 'public') : null,
                'user_id' => $user->id,
            ]);

            // Assign the 'student' role
            $user->assignRole('student');

            // Enroll the student in the selected course
            $enrollment = Enrollment::create([
                'student_id' => $student->id,
                'course_id' => $this->selected_course_id,
                'intake_id' => $this->selected_intake_id,
                'status' => 'pending',
                'remarks' => 'Awaiting approval',
                'enrolled_at' => now(),
            ]);

            DB::commit();

            // Ensure the course relationship is loaded
            $enrollment->load('course');

            if ($user) {
                $notification = new EnrollmentStatus(
                    $this->status,
                    $enrollment->course->title ?? 'Unknown Program'
                );

                $user->notify($notification);
            }

            $this->resetForm();

            /* LivewireAlert::text('Application submitted successfully!')
            ->success()
            ->toast()
            ->position('top-end')
            ->show(); */

            session()->flash('success', 'Application submitted successfully!');
            return redirect()->route('front-end.courses');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding student: ' . $e->getMessage());

            LivewireAlert::text('Failed to submit application!')
                ->error()
                ->toast()
                ->position('top-end')
                ->show();
        }

    }


    public function resetForm()
    {
        $this->first_name = '';
        $this->last_name = '';
        $this->email = '';
        $this->phone_number = '';
        $this->address = '';
        $this->country = '';
        $this->highest_level_of_education = '';
        $this->selected_course_id = null;
        $this->selected_intake_id = null;
        $this->id_url = null;
        $this->kcse_certificate = null;
        $this->passport_size_url = null;
        $this->terms = false;
        $this->step = 1;
    }


    public function validateStep($step)
    {
        if ($step === 1) {
            $this->validate([
                'selected_course_id' => 'required|exists:courses,id',
                'selected_intake_id' => 'required|exists:intakes,id',
            ]);
        }

        if ($step === 2) {
            $this->validate([
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|email|unique:users,email',
                'phone_number' => 'required|string',
                'address' => 'required|string',
                'country' => 'required|string',
                'date_of_birth' => 'required|date',
                'highest_level_of_education' => 'required|string',
            ]);
        }

        if ($step === 3) {
            $this->validate([
                'id_url' => 'nullable|file|mimes:jpg,png,pdf',
                'kcse_certificate' => 'nullable|file|mimes:jpg,png,pdf',
                'passport_size_url' => 'nullable|file|mimes:jpg,png,pdf',
            ]);
        }

        if ($step === 4) {
            $this->validate([
                'terms' => 'accepted',
            ]);
        }

    }


    public function nextStep()
    {
        $this->validateStep($this->step);
        $this->step++;
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }


}
?>
@push('styles')
    <style>

        .modal-header {
            background-color: #f8f9fa;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .modal-body {
            font-size: 1.1rem;
            line-height: 1.6;
            padding-top: 1.5rem;
        }

        .modal-footer {
            font-size: 1rem;
        }

        ul li {
            margin-bottom: 1.2rem;
        }

        .fw-bold {
            font-weight: 700;
        }

        .fs-5 {
            font-size: 1.125rem;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        [x-cloak] {
            display: none !important;
        }

        .step-indicator {
            padding-top: 1rem;
            padding-bottom: 2rem;
            position: relative;
        }

        .step-wizard-wrapper {
            padding: 2rem 0;
        }

        .step-wizard {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .step-line {
            height: 2px;
            background-color: #ced4da;
            top: 40%;
            transform: translateY(-50%);
            z-index: 0;
        }

        .step-circle {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background-color: #dee2e6;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s ease;
            z-index: 1;
        }


        .btn-info {
            background-color: #0a2540;
            border: none;
        }

        .btn-info:hover {
            background-color: #0a2540;
            border: none;
        }


        .step-btn {
            border: none;
            background-color: #dee2e6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 300;
            font-size: 14px;
            transition: all 0.3s ease;
            z-index: 1;
        }

        .step-btn:hover {
            border: none;
            background-color: #dee2e6;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 300;
            font-size: 14px;
            transition: all 0.3s ease;
            z-index: 1;
        }

        .step-circle.active {
            background-color: #004080;
            color: white;
            box-shadow: 0 0 0 6px rgba(0, 64, 128, 0.15);
        }

        .step-label {
            color: #6c757d;
        }

    </style>
@endpush
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


                            <form x-data="{ step: @entangle('step') }" wire:submit.prevent="addStudent"
                                  class="wizard-form mt-5">


                                <!-- Step Wizard Header -->
                                <div class="step-wizard-wrapper position-relative mb-5 px-2 px-md-5">
                                    <div
                                        class="step-wizard d-flex justify-content-between align-items-center position-relative">
                                        <div
                                            class="step-line position-absolute top-35 start-0 w-100 translate-middle-y"></div>

                                        <template x-for="(label, index) in ['Course', 'Info', 'Submit']"
                                                  :key="index">
                                            <div class="text-center z-1 flex-fill">
                                                <div class="step-circle mx-auto"
                                                     :class="{ 'active': step === index + 1 || step > index + 1 }">
                                                    <span x-text="index + 1"></span>
                                                </div>
                                                <div class="step-label mt-2 small fw-semibold" x-text="label"></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Step 1: Choose Course -->
                                <div x-show="step === 1" x-cloak class="card shadow-sm p-4 mb-4">
                                    <h5 class="fw-bold mb-4 text-primary">Step 1: Choose Course</h5>
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label">Preferred Intake</label>
                                            <select wire:model="selected_intake_id" class="form-select">
                                                <option value="">Select Intake</option>
                                                @foreach($intakes as $intake)
                                                    <option value="{{ $intake->id }}">{{ $intake->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('selected_intake_id') <span
                                                class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Select Course</label>
                                            <select wire:model="selected_course_id" class="form-select" disabled>
                                                <option value="">Select Course</option>
                                                @foreach($courses as $course)
                                                    <option value="{{ $course->id }}">{{ $course->title }}
                                                        - {{ $course->level ?? '' }}</option>
                                                @endforeach
                                            </select>
                                            @error('selected_course_id')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="mt-4 text-end">
                                        <button type="button"
                                                class="btn btn-primary px-4 d-flex align-items-center gap-2"
                                                wire:click="nextStep">
                                            <span>Next</span>
                                            <iconify-icon icon="mdi:arrow-right" class="fs-5"></iconify-icon>
                                        </button>
                                    </div>
                                </div>

                                <!-- Step 2: Personal Info -->
                                <div x-show="step === 2" x-cloak class="card shadow-sm p-4 mb-4">
                                    <h5 class="fw-bold mb-4 text-primary">Step 2: Personal Information</h5>
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label>First Name</label>
                                            <input type="text" wire:model="first_name" class="form-control"
                                                   placeholder="John">
                                            @error('first_name') <span
                                                class="text-danger">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label>Last Name</label>
                                            <input type="text" wire:model="last_name" class="form-control"
                                                   placeholder="Doe">
                                            @error('last_name') <span
                                                class="text-danger">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label>Email</label>
                                            <input type="email" wire:model="email" class="form-control"
                                                   placeholder="john@example.com">
                                            @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label>Phone Number</label>
                                            <input type="text" wire:model="phone_number" class="form-control"
                                                   placeholder="+254 712 345678">
                                            @error('phone_number') <span
                                                class="text-danger">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label>Address</label>
                                            <input type="text" wire:model="address" class="form-control"
                                                   placeholder="123 Street Name">
                                            @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label>Country</label>
                                            <input type="text" wire:model="country" class="form-control"
                                                   placeholder="Kenya">
                                            @error('country') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label>Highest Level of Education</label>
                                            <select wire:model="highest_level_of_education" class="form-select">
                                                <option value="">-- Select --</option>
                                                <option>KCSE</option>
                                                <option>Diploma</option>
                                                <option>Degree</option>
                                                <option>Other</option>
                                            </select>
                                            @error('highest_level_of_education') <span
                                                class="text-danger">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label>Date of Birth</label>
                                            <input type="date" wire:model="date_of_birth" class="form-control">
                                            @error('date_of_birth') <span
                                                class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div class="mt-4 d-flex justify-content-between">
                                        <button type="button" class="btn btn-info px-4 d-flex align-items-center gap-2"
                                                wire:click="previousStep">
                                            <iconify-icon icon="mdi:arrow-left"></iconify-icon>
                                            Back
                                        </button>
                                        <button type="button" class="btn btn-primary d-flex align-items-center gap-2"
                                                wire:click="nextStep">
                                            Next
                                            <iconify-icon icon="mdi:arrow-right"></iconify-icon>
                                        </button>
                                    </div>
                                </div>


                                <!-- Step 3: Upload Documents -->
                               {{--  <div x-show="step === 3" x-cloak class="card shadow-sm p-4 mb-4">
                                    <h5 class="fw-bold mb-4 text-primary">Step 3: Upload Documents</h5>

                                    <div class="row g-4">
                                        <!-- National ID or Passport -->
                                        <div class="col-md-12">
                                            <label>National ID / Passport</label>
                                            <input type="file" wire:model.lazy="id_url" class="form-control">
                                            @error('id_url') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- KCSE Certificate -->
                                        <div class="col-md-12">
                                            <label>KCSE Certificate</label>
                                            <input type="file" wire:model.lazy="kcse_certificate" class="form-control">
                                            @error('kcse_certificate') <span
                                                class="text-danger">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- Passport Size Photo -->
                                        <div class="col-md-12">
                                            <label>Passport-size Photo</label>
                                            <input type="file" wire:model.lazy="passport_size_url" class="form-control">
                                            @error('passport_size_url') <span
                                                class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div class="mt-4 d-flex justify-content-between">
                                        <button type="button" class="btn btn-info px-4 d-flex align-items-center gap-2"
                                                wire:click="previousStep" style="background-color: #004080;">
                                            <iconify-icon icon="mdi:arrow-left" class="fs-5"></iconify-icon>
                                            Back
                                        </button>

                                        <button type="button"
                                                class="btn btn-primary px-4 d-flex align-items-center gap-2"
                                                wire:click="nextStep">
                                            Next
                                            <iconify-icon icon="mdi:arrow-right" class="fs-5"></iconify-icon>
                                        </button>
                                    </div>
                                </div> --}}


                                <!-- Step 4: Submit -->
                                <div x-show="step === 3" x-cloak class="card shadow-sm p-4 mb-4">
                                    <h5 class="fw-bold mb-4 text-primary">Step 4: Review & Submit</h5>

                                    <div class="mb-3">
                                        <h6>Course Information</h6>
                                        <p>
                                            <strong>Course:</strong> {{ optional($courses->find($selected_course_id))->title ?? '-' }}
                                        </p>
                                        <p>
                                            <strong>Intake:</strong> {{ optional($intakes->find($selected_intake_id))->name ?? '-' }}
                                        </p>
                                    </div>

                                    <div class="mb-3">
                                        <h6>Personal Information</h6>
                                        <p><strong>Name:</strong> {{ $first_name }} {{ $last_name }}</p>
                                        <p><strong>Email:</strong> {{ $email }}</p>
                                        <p><strong>Phone:</strong> {{ $phone_number }}</p>
                                        <p><strong>Address:</strong> {{ $address }}</p>
                                        <p><strong>Country:</strong> {{ $country }}</p>
                                        <p><strong>DOB:</strong> {{ $date_of_birth }}</p>
                                        <p><strong>Education:</strong> {{ $highest_level_of_education }}</p>
                                    </div>

                                    {{-- <div class="mb-3">
                                        <h6>Uploaded Documents</h6>
                                        <p>
                                            <strong>ID/Passport:</strong> {{ $id_url ? $id_url->getClientOriginalName() : 'Not Uploaded' }}
                                        </p>
                                        <p><strong>KCSE
                                                Certificate:</strong> {{ $kcse_certificate ? $kcse_certificate->getClientOriginalName() : 'Not Uploaded' }}
                                        </p>
                                        <p><strong>Passport
                                                Photo:</strong> {{ $passport_size_url ? $passport_size_url->getClientOriginalName() : 'Not Uploaded' }}
                                        </p>
                                    </div> --}}

                                    <!-- Terms and Conditions Checkbox -->
                                    <div class="form-check my-3">
                                        <input class="form-check-input" type="checkbox" wire:model="terms" id="terms">
                                        <label class="form-check-label" for="terms">
                                            By checking the box below and submitting my application, I confirm that I
                                            have read and understood this consent statement and voluntarily agree to the
                                            processing of my data and media as described.
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and
                                                Conditions</a>.
                                        </label>
                                        @error('terms')
                                        <span class="text-danger d-block mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-info px-4 d-flex align-items-center gap-2"
                                                wire:click="previousStep">
                                            <iconify-icon icon="mdi:arrow-left"></iconify-icon>
                                            Back
                                        </button>
                                        <button type="submit"
                                                class="btn btn-success px-5 d-flex align-items-center gap-2">Submit
                                            Application
                                        </button>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <div class="col-lg-3 mb-7 mb-lg-0">
                    <div class="d-flex flex-column gap-3 bg-white p-7 rounded-3">
                        <div class="py-9 d-flex flex-column gap-3 border-bottom">
                            <h4 class="fs-3 fw-bold  text-muted mb-0 ">Quick Links</h4>
                            <div>
                                <a class="btn btn-primary d-block w-100 mb-3"
                                   href="{{ route('front-end.course-application') }}">
                                    Apply Now
                                </a>
                                <a class="btn btn-outline-primary d-block w-100"
                                   href="../assets/images/frontend-pages/course_guide.jpeg" target="_blank">
                                    Download Course Guide
                                </a>
                            </div>
                        </div>
                        <div class="py-9 d-flex flex-column gap-4 border-bottom">
                            <h4 class="fs-3 fw-bold text-muted mb-0">Need Help</h4>

                            <!-- Contact Info -->
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <iconify-icon icon="mdi:phone-outline" class="text-primary fs-4"></iconify-icon>
                                    <span class="fs-3 text-dark">+254 798496129</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <iconify-icon icon="mdi:phone-outline" class="text-primary fs-4"></iconify-icon>
                                    <span class="fs-3 text-dark">+254 726241095</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <iconify-icon icon="mdi:email-outline" class="text-primary fs-4"></iconify-icon>
                                    <span class="fs-3 text-dark">office@tabor.ac.ke</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex flex-column gap-2">
                                <a class="btn btn-primary d-block w-100 mb-3"
                                   href="{{ route('front-end.course-application') }}">
                                    Apply Now
                                </a>
                                <a class="btn btn-outline-success d-block w-100" target="_blank"
                                   href="https://api.whatsapp.com/send?phone=254798496129&text=Welcome to Tabor Training Institute, How may we help you?">
                                    <iconify-icon icon="mdi:whatsapp"></iconify-icon>
                                    <span class="mb-1">Whatsapp</span>
                                </a>
                            </div>
                        </div>

                        <div class="py-9">
                            <h4 class="text-uppercase fs-3 fw-bold">Share</h4>
                            <div class="d-flex gap-6">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=https://www.tabor.ac.ke/"
                                   class="border rounded-circle round-40 hstack justify-content-center"
                                   data-bs-toggle="tooltip" data-bs-title="Facebook">
                                    <img src="../assets/images/frontend-pages/icon-facebook-dark.svg" alt="facebook">
                                </a>
                                <a href="https://www.instagram.com/"
                                   class="border rounded-circle round-40 hstack justify-content-center"
                                   data-bs-toggle="tooltip" data-bs-title="Instagram">
                                    <img src="../assets/images/frontend-pages/icon-instagram-dark.svg" alt="instagram">
                                </a>
                                <a href="https://www.youtube.com/"
                                   class="border rounded-circle round-40 hstack justify-content-center"
                                   data-bs-toggle="tooltip" data-bs-title="YouTube">
                                    <img src="../assets/images/frontend-pages/icon-youtube-dark.svg" alt="youtube">
                                </a>
                                <a href="https://www.linkedin.com/"
                                   class="border rounded-circle round-40 hstack justify-content-center"
                                   data-bs-toggle="tooltip" data-bs-title="Linckedin">
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
                    <h2 class="fs-15 fw-bolder mb-0 text-center mb-md-12">
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
                                <button class="accordion-button collapsed fs-5" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
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
                                <button class="accordion-button collapsed fs-5" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseThree" aria-expanded="false"
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
                                <button class="accordion-button collapsed fs-5" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseFour" aria-expanded="false"
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
                                <button class="accordion-button collapsed fs-5" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseFive" aria-expanded="false"
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
                                <button class="accordion-button collapsed fs-5" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
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
                            with the Data Protection Act, 2019 (Kenya) and applicable privacy regulations.
                        </li>
                        <li>This data may be shared with relevant government bodies, accrediting agencies, examination
                            councils, or third-party service providers only where necessary and with appropriate
                            safeguards.
                        </li>
                        <li>During the course of my studies, photographs and videos may be taken of me during class
                            activities, events, or institutional functions. These may be used in TTI’s official social
                            media pages, website, newsletters, promotional materials, and other awareness or
                            communication initiatives.
                        </li>
                        <li>Such media will be used respectfully and in a manner that promotes the positive image of the
                            Institute and its programs.
                        </li>
                        <li>I have the right to access, correct, or request the deletion of my personal data, subject to
                            applicable laws and institutional policies.
                        </li>
                        <li>Tabor Training Institute will not use my personal data for unrelated purposes without my
                            explicit consent.
                        </li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</div>



