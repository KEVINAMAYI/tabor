<?php

use App\Exports\CourseExport;
use App\Models\Course;
use App\Models\CourseTrimester;
use App\Services\CourseTrimesterService;
use Illuminate\Support\Facades\DB;
use App\Models\CourseCategory;
use App\Models\Lecturer;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\CourseFeePlanSyncService;

new class extends Component {
    use WithFileUploads, WithPagination;

    // Base course fields
    public $title, $course_category_id, $code, $description, $price, $duration, $number_of_trimesters, $mode, $level, $certification, $prerequisites, $image, $brochure;

    // STATIC FEES (checkbox + amount)
    public $apply_admin_fee = false;
    public $admin_registration_fee = 0;
    public $admin_student_id_fee = 0;
    public $admin_stationery_fee = 0;
    public $admin_caution_fee = 0;

    public $apply_exam_fee = false;
    public $exam_fee = 0;

    public $apply_attachment_fee = false;
    public $attachment_fee = 0;

    public $apply_graduation_fee = false;
    public $graduation_fee = 0;

    public $apply_certification_fee = false;
    public $certification_fee = 0;

    public $editId = null;

    public $selected = [];
    public $selectAll = false;

    public $search = '';
    public $perPage = 10;

    public $lecturers = [];
    public $categories = [];

    // lecturers selected ids (used by attach/sync)
    public $lecturer_ids = [];

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:courses,code,' . ($this->editId ?? 'NULL') . ',id',
            'course_category_id' => 'required|exists:course_categories,id',
            'description' => 'nullable|string',

            // Tuition
            'price' => 'required|numeric|min:0',

            // Duration / delivery
            'duration' => 'required|string|max:100',
            'number_of_trimesters' => 'required|integer|min:1',
            'mode' => 'nullable|in:online,on-campus,hybrid',
            'level' => 'nullable|string|max:100',
            'certification' => 'nullable|string|max:100',
            'prerequisites' => 'nullable|string',

            // Files
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:10240',
            'brochure' => 'nullable|file|mimes:pdf|max:10240',

            // Static fees
            'apply_admin_fee' => 'boolean',
            'admin_registration_fee' => 'nullable|numeric|min:0',
            'admin_student_id_fee' => 'nullable|numeric|min:0',
            'admin_stationery_fee' => 'nullable|numeric|min:0',
            'admin_caution_fee' => 'nullable|numeric|min:0',

            'apply_exam_fee' => 'boolean',
            'exam_fee' => 'nullable|numeric|min:0',

            'apply_attachment_fee' => 'boolean',
            'attachment_fee' => 'nullable|numeric|min:0',

            'apply_graduation_fee' => 'boolean',
            'graduation_fee' => 'nullable|numeric|min:0',

            'apply_certification_fee' => 'boolean',
            'certification_fee' => 'nullable|numeric|min:0',
        ];
    }

    public function mount()
    {
        if (!auth()->user()->hasPermissionTo('view-courses')) {
            abort(403, 'Unauthorized action.');
        }

        $this->lecturers = Lecturer::all();
        $this->categories = CourseCategory::all();
    }

    #[On('search')]
    public function search()
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function with()
    {
        $query = Course::with('category');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        }

        $courses = $query->orderBy('course_category_id')->paginate(perPage: $this->perPage);

        return [
            'courses' => $courses,
        ];
    }

    private function normalizeFees()
    {
        // If checkbox is off, force fee values to 0 (prevents accidental storage)
        // if (!$this->apply_admin_fee) {
        //     $this->admin_registration_fee = 0;
        //     $this->admin_student_id_fee = 0;
        //     $this->admin_stationery_fee = 0;
        //     $this->admin_caution_fee = 0;
        // }

        if (!$this->apply_exam_fee) {
            $this->exam_fee = 0;
        }
        if (!$this->apply_attachment_fee) {
            $this->attachment_fee = 0;
        }
        if (!$this->apply_graduation_fee) {
            $this->graduation_fee = 0;
        }
        if (!$this->apply_certification_fee) {
            $this->certification_fee = 0;
        }
    }

    public function getTotalAdminFeeProperty()
    {
        return (float) $this->admin_registration_fee + (float) $this->admin_student_id_fee + (float) $this->admin_stationery_fee + (float) $this->admin_caution_fee;
    }

    public function addCourse()
    {
        $this->validate();
        $this->normalizeFees();
        try {
            DB::beginTransaction();

            $imagePath = $this->image ? $this->image->store('courses', 'public') : null;
            $brochurePath = $this->brochure ? $this->brochure->store('brochures', 'public') : null;

            $course = Course::create([
                'title' => $this->title,
                'course_category_id' => $this->course_category_id,
                'code' => $this->code,
                'description' => $this->description,

                // tuition
                'price' => $this->price,

                // static fees (NOTE: requires these columns in your courses table)
                /* 'apply_admin_fee' => (bool) $this->apply_admin_fee,
                'admin_registration_fee' => $this->admin_registration_fee,
                'admin_student_id_fee' => $this->admin_student_id_fee,
                'admin_stationery_fee' => $this->admin_stationery_fee,
                'admin_caution_fee' => $this->admin_caution_fee,
                'total_admin_fee' => $this->totalAdminFee, */

                'apply_exam_fee' => (bool) $this->apply_exam_fee,
                'exam_fee' => $this->exam_fee,

                'apply_attachment_fee' => (bool) $this->apply_attachment_fee,
                'attachment_fee' => $this->attachment_fee,

                'apply_graduation_fee' => (bool) $this->apply_graduation_fee,
                'graduation_fee' => $this->graduation_fee,

                'apply_certification_fee' => (bool) $this->apply_certification_fee,
                'certification_fee' => $this->certification_fee,

                // duration
                'duration' => $this->duration,
                'number_of_trimesters' => $this->number_of_trimesters,
                'mode' => $this->mode,

                'level' => $this->level,
                'certification' => $this->certification,
                'prerequisites' => $this->prerequisites,

                'image_url' => $imagePath,
                'brochure_url' => $brochurePath,
            ]);

            if (!empty($this->lecturer_ids)) {
                $course->lecturers()->attach($this->lecturer_ids);
            }

            // CourseTrimesterService::syncCourseTrimesters($course);

            app(CourseFeePlanSyncService::class)->syncDefaultsForCourse($course);

            $this->dispatch('hide-course-modal');
            DB::commit();

            $this->resetForm();
            $this->resetPage();

            LivewireAlert::text('Course added successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding course: ' . $e->getMessage());
            LivewireAlert::text('There was an error while adding course.')->error()->toast()->position('top-end')->show();
        }
    }

    public function editCourse($id)
    {
        $course = Course::findOrFail($id);

        $this->editId = $course->id;

        $this->title = $course->title;
        $this->code = $course->code;
        $this->description = $course->description;

        $this->price = $course->price;

        $this->duration = $course->duration;
        $this->number_of_trimesters = $course->number_of_trimesters;
        $this->mode = $course->mode;

        $this->level = $course->level;
        $this->certification = $course->certification;
        $this->prerequisites = $course->prerequisites;
        $this->course_category_id = $course->course_category_id;

        // fees
        /* $this->apply_admin_fee = (bool) ($course->apply_admin_fee ?? false);
        $this->admin_registration_fee = $course->admin_registration_fee ?? 0;
        $this->admin_student_id_fee = $course->admin_student_id_fee ?? 0;
        $this->admin_stationery_fee = $course->admin_stationery_fee ?? 0;
        $this->admin_caution_fee = $course->admin_caution_fee ?? 0; */

        $this->apply_exam_fee = (bool) ($course->apply_exam_fee ?? false);
        $this->exam_fee = $course->exam_fee ?? 0;

        $this->apply_attachment_fee = (bool) ($course->apply_attachment_fee ?? false);
        $this->attachment_fee = $course->attachment_fee ?? 0;

        $this->apply_graduation_fee = (bool) ($course->apply_graduation_fee ?? false);
        $this->graduation_fee = $course->graduation_fee ?? 0;

        $this->apply_certification_fee = (bool) ($course->apply_certification_fee ?? false);
        $this->certification_fee = $course->certification_fee ?? 0;

        // lecturers
        $this->lecturer_ids = $course->lecturers()->pluck('lecturers.id')->map(fn($id) => (string) $id)->toArray();

        $this->dispatch('show-course-modal');
    }

    public function updateCourse()
    {
        $this->validate();
        $this->normalizeFees();

        try {
            DB::beginTransaction();

            $course = Course::findOrFail($this->editId);

            $imagePath = $this->image ? $this->image->store('courses', 'public') : $course->image_url;
            $brochurePath = $this->brochure ? $this->brochure->store('brochures', 'public') : $course->brochure_url;

            $course->update([
                'title' => $this->title,
                'course_category_id' => $this->course_category_id,
                'code' => $this->code,
                'description' => $this->description,

                // tuition
                'price' => $this->price,

                'apply_exam_fee' => (bool) $this->apply_exam_fee,
                'exam_fee' => $this->exam_fee,

                'apply_attachment_fee' => (bool) $this->apply_attachment_fee,
                'attachment_fee' => $this->attachment_fee,

                'apply_graduation_fee' => (bool) $this->apply_graduation_fee,
                'graduation_fee' => $this->graduation_fee,

                'apply_certification_fee' => (bool) $this->apply_certification_fee,
                'certification_fee' => $this->certification_fee,

                // duration
                'duration' => $this->duration,
                'number_of_trimesters' => $this->number_of_trimesters,
                'mode' => $this->mode,

                'level' => $this->level,
                'certification' => $this->certification,
                'prerequisites' => $this->prerequisites,

                'image_url' => $imagePath,
                'brochure_url' => $brochurePath,
            ]);

            if (!empty($this->lecturer_ids)) {
                $course->lecturers()->sync($this->lecturer_ids);
            } else {
                $course->lecturers()->detach();
            }

            // CourseTrimesterService::syncCourseTrimesters($course);

            app(CourseFeePlanSyncService::class)->syncDefaultsForCourse($course);

            DB::commit();

            $this->resetForm();
            $this->resetPage();
            $this->dispatch('hide-course-modal');

            LivewireAlert::text('Course updated successfully.!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating course: ' . $e->getMessage());
            LivewireAlert::text('Failed to update Course.!')->error()->toast()->position('top-end')->show();
        }
    }

    #[On('setLecturers')]
    public function setLecturers($lecturers)
    {
        $this->lecturer_ids = $lecturers ?? [];
    }

    public function deleteCourse($id)
    {
        try {
            DB::beginTransaction();
            $course = Course::findOrFail($id);
            $course->trimesters()->delete();
            $course->delete();
            DB::commit();
            LivewireAlert::text('Course deleted successfully.!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting course: ' . $e->getMessage());
            LivewireAlert::text('There was an error while deleting course.')->error()->toast()->position('top-end')->show();
            return;
        }

        $this->resetPage();
    }

    public function deleteSelected()
    {
        CourseTrimester::whereIn('course_id', $this->selected)->delete();
        Course::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();

        LivewireAlert::text('Courses deleted successfully.!')->success()->toast()->position('top-end')->show();
    }

    private function resetForm()
    {
        $this->title = $this->code = $this->description = null;
        $this->course_category_id = null;

        $this->price = 0;

        $this->duration = null;
        $this->number_of_trimesters = null;
        $this->mode = null;

        $this->level = null;
        $this->certification = null;
        $this->prerequisites = null;

        // reset fees
        $this->apply_admin_fee = false;
        $this->admin_registration_fee = 0;
        $this->admin_student_id_fee = 0;
        $this->admin_stationery_fee = 0;
        $this->admin_caution_fee = 0;

        $this->apply_exam_fee = false;
        $this->exam_fee = 0;

        $this->apply_attachment_fee = false;
        $this->attachment_fee = 0;

        $this->apply_graduation_fee = false;
        $this->graduation_fee = 0;

        $this->apply_certification_fee = false;
        $this->certification_fee = 0;

        // files / edit id
        $this->image = null;
        $this->brochure = null;
        $this->editId = null;

        // lecturers
        $this->lecturer_ids = [];

        $this->search = null;

        $this->dispatch('reset-file-input');
        $this->dispatch('reset-select2');
    }

    #[On('select-all')]
    public function selectAll()
    {
        if ($this->selectAll) {
            $currentPageCourseIds = Course::query()
                ->when(!empty($this->search), fn($q) => $q->where('title', 'like', '%' . $this->search . '%')->orWhere('description', 'like', '%' . $this->search . '%'))
                ->latest()
                ->paginate(10)
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->toArray();

            $this->selected = $currentPageCourseIds;
        } else {
            $this->selected = [];
        }
    }

    public function exportExcel()
    {
        return Excel::download(app(CourseExport::class), 'courses.xlsx');
    }

    public function exportPdf()
    {
        $url = route('courses.export.pdf');
        return redirect()->to($url);
    }
}; ?>


@push('styles')
    <style>
        .pagination {
            margin-left: 10px;
        }

        /* For multiple select */
        .select2-container .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
        }

        table.search-table td.course-title {
            color: #446076 !important;
            font-weight: 600;
        }

        table.search-table td.course-fee {
            color: #f69121 !important;
            font-weight: 600;
        }

        table.search-table .action-btn a:hover {
            color: #f69121 !important;
        }

        table.search-table .form-check-input {
            border-color: #446076 !important;
        }

        table.search-table .form-check-input:checked {
            background-color: #f69121 !important;
            border-color: #f69121 !important;
        }

        table.search-table .text-ellipsis {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 250px;
            display: inline-block;
            color: #6c757d;
        }

        table.search-table tbody tr:hover {
            background-color: #fff6ee !important;
        }

        .section-card {
            border: 1px solid #e9ecef;
            border-radius: .5rem;
            padding: 1rem;
        }

        .section-title text-center {
            font-weight: 700;
            font-size: .95rem;
            margin: 0;
        }

        .section-hr {
            margin: .5rem 0 1rem 0;
        }

        .fee-row {
            border: 1px solid #e9ecef;
            border-radius: .5rem;
            padding: .75rem;
        }
    </style>
@endpush

<div class="row">
    <div class="col-12">
        <div class="widget-content searchable-container list">
            <div class="card card-body">
                <div class="row">
                    <div class="col-md-4 col-xl-3">
                        <form class="position-relative" autocomplete="off">
                            <input wire:keyup.debounce.100ms="$dispatch('search')" type="text"
                                class="form-control product-search ps-5" placeholder="Search Courses..."
                                wire:model="search" />
                            <i
                                class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div
                        class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                        @can('delete-courses')
                            @if (count($selected) > 0)
                                <div class="action-btn">
                                    <a href="javascript:void(0)" wire:click.prevent="deleteSelected"
                                        class="delete-multiple bg-danger-subtle btn me-2 text-danger">
                                        <i class="ti ti-trash me-1 fs-5"></i> Delete Selected
                                    </a>
                                </div>
                            @endif
                        @endcan

                        @can('add-courses')
                            <a href="javascript:void(0)" wire:click="$dispatch('show-course-modal')"
                                class="btn btn-primary d-flex align-items-center">
                                <i class="ti ti-book text-white me-1 fs-5"></i> Add Course
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="addCourseModal" tabindex="-1" role="dialog"
                aria-labelledby="addCourseModalTitle" aria-hidden="true" wire:ignore.self>
                <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header d-flex align-items-center">
                            <h5 class="modal-title">{{ $editId ? 'Update' : 'Add' }} Course</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>

                        <form wire:submit.prevent="{{ $editId ? 'updateCourse' : 'addCourse' }}">
                            <div class="modal-body">
                                <div class="row g-3">

                                    {{-- ===================== COURSE INFO ===================== --}}
                                    <div class="col-12">
                                        <div class="section-card">
                                            <p class="section-title text-center text-primary">Course Information</p>
                                            <hr class="section-hr">

                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="course-title" class="form-label">Course Title</label>
                                                    <input id="course-title" type="text" wire:model="title"
                                                        class="form-control"
                                                        placeholder="Enter the course name (e.g., Web Development for Beginners)" />
                                                    @error('title')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="course-category" class="form-label">Course
                                                        Category</label>
                                                    <select id="course-category" wire:model="course_category_id"
                                                        class="form-control">
                                                        <option value="">-- Select Category --</option>
                                                        @foreach ($categories as $category)
                                                            <option value="{{ $category->id }}">{{ $category->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('course_category_id')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="course-code" class="form-label">Course Code</label>
                                                    <input id="course-code" type="text" wire:model="code"
                                                        class="form-control"
                                                        placeholder="Enter the course code (e.g., WEB101)" />
                                                    @error('code')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6" wire:ignore>
                                                    <label class="form-label">Course Lecturers</label>
                                                    <select id="lecturersSelect"
                                                        style="padding-top:10px; padding-bottom:10px;"
                                                        class="form-control select2" multiple
                                                        data-placeholder="Select lecturers">
                                                        @foreach ($lecturers as $lecturer)
                                                            <option value="{{ $lecturer->id }}">
                                                                {{ $lecturer->first_name . ' ' . $lecturer->last_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-12">
                                                    <label for="course-description" class="form-label">Course
                                                        Description</label>
                                                    <textarea id="course-description" wire:model="description" class="form-control"
                                                        placeholder="Provide a brief description of the course" rows="4"></textarea>
                                                    @error('description')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ===================== DURATION & DELIVERY ===================== --}}
                                    <div class="col-12">
                                        <div class="section-card">
                                            <p class="section-title text-center text-primary">Duration & Delivery</p>
                                            <hr class="section-hr">

                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label for="course-duration" class="form-label">Total Course
                                                        Duration</label>
                                                    <input id="course-duration" type="text" wire:model="duration"
                                                        class="form-control"
                                                        placeholder="Duration (e.g., 6 weeks, 3 months, 3 years...)" />
                                                    @error('duration')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="course-trimesters" class="form-label">Number of
                                                        Trimesters</label>
                                                    <input id="course-trimesters" type="number"
                                                        wire:model="number_of_trimesters" class="form-control"
                                                        placeholder="e.g., 3, 4, 6..." />
                                                    @error('number_of_trimesters')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="course-mode" class="form-label">Mode of
                                                        Learning</label>
                                                    <select id="course-mode" wire:model="mode" class="form-control">
                                                        <option value="">-- Select Mode --</option>
                                                        <option value="online">Online</option>
                                                        <option value="on-campus">On-campus</option>
                                                        <option value="hybrid">Hybrid</option>
                                                    </select>
                                                    @error('mode')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="course-level" class="form-label">Level</label>
                                                    <input id="course-level" type="text" wire:model="level"
                                                        class="form-control"
                                                        placeholder="Level (e.g., Beginner, Intermediate)" />
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="course-certification"
                                                        class="form-label">Certification</label>
                                                    <input id="course-certification" type="text"
                                                        wire:model="certification" class="form-control"
                                                        placeholder="Certification (e.g., Certificate of Completion)" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ===================== PAYMENTS ===================== --}}
                                    <div class="col-12" x-data="{
                                        apply_exam_fee: @entangle('apply_exam_fee').live,
                                        apply_attachment_fee: @entangle('apply_attachment_fee').live,
                                        apply_graduation_fee: @entangle('apply_graduation_fee').live,
                                        apply_certification_fee: @entangle('apply_certification_fee').live
                                    }">

                                        <div class="section-card">
                                            <div class="d-flex justify-content-center align-items-center">
                                                <p class="section-title text-center mb-0 mx-1 text-primary">Payments
                                                </p>
                                                <small class="text-muted"> (Tick what applies, enter the
                                                    amounts)</small>
                                            </div>
                                            <hr class="section-hr">

                                            <div class="row g-3 mb-2">
                                                <div class="col-md-4">
                                                    <label class="form-label">Tuition Fee (Per Trimester)</label>
                                                    <input type="number" wire:model="price" class="form-control"
                                                        placeholder="Enter tuition fee (Per Trimester)" />
                                                    @error('price')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="row g-3">


                                                {{-- Exam Fee --}}
                                                <div class="col-md-6">
                                                    <div class="fee-row">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="apply_exam_fee" x-model="apply_exam_fee" />
                                                            <label class="form-check-label fw-semibold"
                                                                for="apply_exam_fee">Exam Fee</label>
                                                        </div>
                                                        <input type="number" wire:model="exam_fee"
                                                            class="form-control" :disabled="!apply_exam_fee" />
                                                    </div>
                                                </div>

                                                {{-- Attachment Fee --}}
                                                <div class="col-md-6">
                                                    <div class="fee-row">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="apply_attachment_fee"
                                                                x-model="apply_attachment_fee" />
                                                            <label class="form-check-label fw-semibold"
                                                                for="apply_attachment_fee">Attachment Fee</label>
                                                        </div>
                                                        <input type="number" wire:model="attachment_fee"
                                                            class="form-control" :disabled="!apply_attachment_fee" />
                                                    </div>
                                                </div>

                                                {{-- Graduation Fee --}}
                                                <div class="col-md-6">
                                                    <div class="fee-row">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="apply_graduation_fee"
                                                                x-model="apply_graduation_fee" />
                                                            <label class="form-check-label fw-semibold"
                                                                for="apply_graduation_fee">Graduation Fee</label>
                                                        </div>
                                                        <input type="number" step="0.01"
                                                            wire:model="graduation_fee" class="form-control"
                                                            :disabled="!apply_graduation_fee" />
                                                    </div>
                                                </div>

                                                {{-- Certification Fee --}}
                                                <div class="col-md-6">
                                                    <div class="fee-row">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="apply_certification_fee"
                                                                x-model="apply_certification_fee" />
                                                            <label class="form-check-label fw-semibold"
                                                                for="apply_certification_fee">Certification Fee</label>
                                                        </div>
                                                        <input type="number" wire:model="certification_fee"
                                                            class="form-control"
                                                            :disabled="!apply_certification_fee" />
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    {{-- ===================== REQUIREMENTS ===================== --}}
                                    <div class="col-12">
                                        <div class="section-card">
                                            <p class="section-title text-center text-primary">Requirements</p>
                                            <hr class="section-hr">

                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label for="course-prerequisites"
                                                        class="form-label">Prerequisites</label>
                                                    <textarea id="course-prerequisites" wire:model="prerequisites" class="form-control"
                                                        placeholder="Any prerequisites or prior knowledge required" rows="3"></textarea>
                                                    @error('prerequisites')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ===================== MEDIA & DOCUMENTS ===================== --}}
                                    <div class="col-12">
                                        <div class="section-card">
                                            <p class="section-title text-center text-primary">Media & Documents</p>
                                            <hr class="section-hr">

                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label for="course-image" class="form-label">Course Image</label>
                                                    <input id="course-image" type="file" wire:model="image"
                                                        accept="image/jpeg, image/png, image/jpg, image/gif"
                                                        class="form-control" />
                                                    @error('image')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="course-brochure" class="form-label">Course Brochure
                                                        (PDF)</label>
                                                    <input id="course-brochure" type="file" wire:model="brochure"
                                                        class="form-control" accept=".pdf" />
                                                    @error('brochure')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="modal-footer">
                                <div class="d-flex gap-1 m-0">
                                    <button type="submit" class="btn btn-success">
                                        {{ $editId ? 'Save' : 'Add' }}
                                    </button>
                                    <button type="button" class="btn bg-danger-subtle text-danger"
                                        data-bs-dismiss="modal">
                                        Discard
                                    </button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

            <div class="card card-body">
                <div class="table-responsive">

                    <!-- Top Bar Inside the Card -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 px-2">
                        <div class="d-flex align-items-center">
                            <label for="perPage" class="form-label me-2">Show</label>
                            <select wire:model.live="perPage" id="perPage" class="form-select form-select-sm">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="ms-2">entries</span>
                        </div>

                        <h6 class="mb-0 fw-semibold text-primary d-flex align-items-center">
                            <iconify-icon icon="mdi:book-open-page-variant" class="me-2"
                                style="font-size: 20px;"></iconify-icon>
                            Courses List
                        </h6>

                        <div class="d-flex gap-2 flex-wrap">
                            <button wire:click="exportExcel"
                                class="btn btn-outline-success btn-sm d-flex align-items-center px-3 py-1 rounded">
                                <iconify-icon icon="mdi:file-excel-outline" class="me-1"
                                    style="font-size: 18px;"></iconify-icon>
                                Excel
                            </button>

                            <button wire:click="exportPdf"
                                class="btn btn-outline-danger btn-sm d-flex align-items-center px-3 py-1 rounded">
                                <iconify-icon icon="mdi:file-pdf-box" class="me-1"
                                    style="font-size: 18px;"></iconify-icon>
                                PDF
                            </button>
                        </div>
                    </div>

                    <table class="table search-table align-middle text-nowrap">
                        <thead class="header-item">
                            <tr>
                                <th>
                                    <div class="form-check text-center">
                                        <input wire:click="$dispatch('select-all')" type="checkbox"
                                            class="form-check-input" wire:model="selectAll" />
                                    </div>
                                </th>
                                <th>#</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Code</th>
                                <th>Duration</th>
                                <th>No. of trimesters</th>
                                <th>Fee</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($courses as $course)
                                <tr wire:key="{{ $course->id }}" class="search-items">
                                    <td>
                                        <div class="form-check text-center">
                                            <input type="checkbox" class="form-check-input" wire:model="selected"
                                                value="{{ (string) $course->id }}" />
                                        </div>
                                    </td>
                                    <td class="text-muted">{{ $loop->iteration }}</td>
                                    <td class="course-title">{{ $course->title }} - {{ $course->level }}</td>
                                    <td class="course-title">{{ $course->category?->name ?? 'N/A' }}</td>
                                    <td class="text-muted">{{ $course->code }}</td>
                                    <td class="text-muted">{{ $course->duration }}</td>
                                    <td class="text-muted">{{ $course->number_of_trimesters }}</td>
                                    <td class="course-fee">KES {{ number_format($course->price, 2) }}</td>
                                    <td>
                                        <div class="action-btn">
                                            <a href="{{ route('courses.view', $course->id) }}" class="text-info"
                                                title="View">
                                                <i class="ti ti-eye fs-5"></i>
                                            </a>
                                            @can('edit-courses')
                                                <a href="javascript:void(0)" wire:click="editCourse({{ $course->id }})"
                                                    class="text-primary" title="Edit">
                                                    <i class="ti ti-pencil fs-5"></i>
                                                </a>
                                            @endcan
                                            @can('delete-courses')
                                                <a href="javascript:void(0)"
                                                    wire:click="deleteCourse({{ $course->id }})" class="text-danger"
                                                    title="Delete">
                                                    <i class="ti ti-trash fs-5"></i>
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No courses found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>

                <div class="d-flex justify-content-center mt-4">
                    {{ $courses->links() }}
                </div>

            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="assets/libs/select2/dist/js/select2.full.min.js"></script>

    <script>
        function initializeMultiSelect(context = document) {
            $(context).find('select.select2[multiple]').each(function() {
                const $el = $(this);
                const parentModal = $el.closest('.modal');

                // avoid double init
                if ($el.hasClass("select2-hidden-accessible")) return;

                $el.select2({
                    width: '100%',
                    dropdownParent: parentModal.length ? parentModal : $(document.body),
                    placeholder: $el.data('placeholder') || 'Select options',
                    allowClear: $el.data('allow-clear') === "true",
                    minimumResultsForSearch: 0
                });

                // Sync values to Livewire on change
                $el.on('change', function() {
                    let selected = $(this).val();
                    Livewire.dispatch('setLecturers', {
                        lecturers: selected
                    });
                });
            });
        }

        function setSelect2ValuesFromLivewire() {
            try {
                const selected = (Livewire?.find(document.querySelector('[wire\\:id]')?.getAttribute('wire:id'))?.get(
                    'lecturer_ids')) || [];
                $('#lecturersSelect').val(selected).trigger('change.select2');
            } catch (e) {
                // fallback: do nothing
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeMultiSelect();
        });

        window.addEventListener('show-course-modal', () => {
            const modalEl = document.getElementById('addCourseModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            // initialize + apply selected values after modal opens
            setTimeout(() => {
                initializeMultiSelect(modalEl);
                setSelect2ValuesFromLivewire();
            }, 150);
        });

        window.addEventListener('hide-course-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('addCourseModal'))?.hide();
        });

        window.addEventListener('reset-file-input', () => {
            let fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                input.value = '';
            });
        });

        window.addEventListener('reset-select2', () => {
            // Clear select2 selections visually
            if ($('#lecturersSelect').length) {
                $('#lecturersSelect').val(null).trigger('change.select2');
            }
        });
    </script>
@endpush
