<?php

use App\Exports\CourseExport;
use App\Models\Course;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component {


    use WithFileUploads, WithPagination;

    public $title, $code, $description, $price, $duration, $mode, $level, $certification, $prerequisites, $image, $brochure;

    public $editId = null;

    public $selected = [];

    public $selectAll = false;

    public $search = '';

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:courses,code,' . ($this->editId ?? 'NULL') . ',id', // Ensure unique code except for the current course being edited
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'nullable|string|max:100',
            'mode' => 'nullable|in:online,on-campus,hybrid',
            'level' => 'nullable|string|max:100',
            'certification' => 'nullable|string|max:100',
            'prerequisites' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max
            'brochure' => 'nullable|file|mimes:pdf|max:10240', // max size 10MB, example for brochure
        ];
    }

    public function mount()
    {
        if (!auth()->user()->hasPermissionTo('view-courses')) {
            abort(403, 'Unauthorized action.');
        }
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
        $query = Course::query();

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        }

        $courses = $query->latest()->paginate(10);

        return [
            'courses' => $courses,
        ];
    }

    public function addCourse()
    {
        $this->validate();

        try {
            $imagePath = $this->image ? $this->image->store('courses', 'public') : null;
            $brochurePath = $this->brochure ? $this->brochure->store('brochures', 'public') : null;

            Course::create([
                'title' => $this->title,
                'code' => $this->code,
                'description' => $this->description,
                'price' => $this->price,
                'duration' => $this->duration,
                'mode' => $this->mode,
                'level' => $this->level,
                'certification' => $this->certification,
                'prerequisites' => $this->prerequisites,
                'image_url' => $imagePath,
                'brochure_url' => $brochurePath,
            ]);

            $this->dispatch('hide-course-modal');
            $this->resetForm();
            $this->resetPage();

            LivewireAlert::text('Course added successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
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
        $this->mode = $course->mode;
        $this->level = $course->level;
        $this->certification = $course->certification;
        $this->prerequisites = $course->prerequisites;

        $this->dispatch('show-course-modal');
    }

    public function updateCourse()
    {
        $this->validate();

        try {
            $course = Course::findOrFail($this->editId);

            $imagePath = $this->image ? $this->image->store('courses', 'public') : $course->image_url;
            $brochurePath = $this->brochure ? $this->brochure->store('brochures', 'public') : $course->brochure_url;

            $course->update([
                'title' => $this->title,
                'code' => $this->code,
                'description' => $this->description,
                'price' => $this->price,
                'duration' => $this->duration,
                'mode' => $this->mode,
                'level' => $this->level,
                'certification' => $this->certification,
                'prerequisites' => $this->prerequisites,
                'image_url' => $imagePath,
                'brochure_url' => $brochurePath,
            ]);

            $this->resetForm();
            $this->resetPage();
            $this->dispatch('hide-course-modal');

            LivewireAlert::text('Course updated successfully.!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            Log::error('Error updating course: ' . $e->getMessage());

            LivewireAlert::text('Failed to update Course.!')->error()->toast()->position('top-end')->show();
        }
    }

    public function deleteCourse($id)
    {
        Course::findOrFail($id)->delete();
        $this->resetPage();

        LivewireAlert::text('Course deleted successfully.!')->success()->toast()->position('top-end')->show();
    }

    public function deleteSelected()
    {
        Course::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();

        LivewireAlert::text('Courses deleted successfully.!')->success()->toast()->position('top-end')->show();
    }

    private function resetForm()
    {
        $this->title = $this->code = $this->description = $this->price = $this->duration = $this->mode = $this->level = $this->certification = $this->prerequisites = null;
        $this->image = null;
        $this->editId = null;
        $this->brochure = null;
        $this->search = null;
        $this->dispatch('reset-file-input');
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
                ->map(fn($id) => (string)$id)
                ->toArray();

            $this->selected = $currentPageCourseIds;
        } else {
            $this->selected = [];
        }
    }


    public function exportExcel()
    {
        return Excel::download(new CourseExport(), 'courses.xlsx');
    }

}; ?>


@push('styles')
    <style>
        .pagination {
            margin-left: 10px;
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
                                   wire:model="search"/>
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
                                <div class="row">
                                    <!-- Course Title -->
                                    <div class="col-md-6 mb-3">
                                        <label for="course-title" class="form-label">Course Title</label>
                                        <input id="course-title" type="text" wire:model="title" class="form-control"
                                               placeholder="Enter the course name (e.g., Web Development for Beginners)"/>
                                        @error('title')
                                        <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <!-- Course Code -->
                                    <div class="col-md-6 mb-3">
                                        <label for="course-code" class="form-label">Course Code</label>
                                        <input id="course-code" type="text" wire:model="code" class="form-control"
                                               placeholder="Enter the course code (e.g., WEB101)"/>
                                        @error('code')
                                        <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <!-- Course Fee -->
                                    <div class="col-md-12 mb-3">
                                        <label for="course-fee" class="form-label">Fee</label>
                                        <input id="course-fee" type="number" step="0.01" wire:model="price"
                                               class="form-control" placeholder="Enter course fee (e.g., 20000 KSH)"/>
                                        @error('price')
                                        <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <!-- Course Description -->
                                    <div class="col-md-12 mb-3">
                                        <label for="course-description" class="form-label">Course Description</label>
                                        <textarea id="course-description" wire:model="description" class="form-control"
                                                  placeholder="Provide a brief description of the course"
                                                  rows="4"></textarea>
                                        @error('description')
                                        <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <!-- Duration -->
                                    <div class="col-md-4 mb-3">
                                        <label for="course-duration" class="form-label">Duration</label>
                                        <input id="course-duration" type="text" wire:model="duration"
                                               class="form-control" placeholder="Duration (e.g., 6 weeks, 3 months)"/>
                                    </div>

                                    <!-- Mode -->
                                    <div class="col-md-4 mb-3">
                                        <label for="course-mode" class="form-label">Mode of Learning</label>
                                        <select id="course-mode" wire:model="mode" class="form-control">
                                            <option value="">-- Select Mode --</option>
                                            <option value="online">Online</option>
                                            <option value="on-campus">On-campus</option>
                                            <option value="hybrid">Hybrid</option>
                                        </select>
                                    </div>

                                    <!-- Level -->
                                    <div class="col-md-4 mb-3">
                                        <label for="course-level" class="form-label">Level</label>
                                        <input id="course-level" type="text" wire:model="level"
                                               class="form-control" placeholder="Level (e.g., Beginner, Intermediate)"/>
                                    </div>

                                    <!-- Certification -->
                                    <div class="col-md-4 mb-3">
                                        <label for="course-certification" class="form-label">Certification</label>
                                        <input id="course-certification" type="text" wire:model="certification"
                                               class="form-control"
                                               placeholder="Certification (e.g., Certificate of Completion)"/>
                                    </div>

                                    <!-- Course Image -->
                                    <div class="col-md-4 mb-3">
                                        <label for="course-image" class="form-label">Course Image</label>
                                        <input type="file" wire:model="image"
                                               accept="image/jpeg, image/png, image/jpg, image/gif"
                                               class="form-control"/>
                                        @error('image')
                                        <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <!-- Course Brochure -->
                                    <div class="col-md-4 mb-3">
                                        <label for="course-brochure" class="form-label">Course Brochure (PDF)</label>
                                        <input id="course-brochure" type="file" wire:model="brochure"
                                               class="form-control" accept=".pdf"/>
                                        @error('brochure')
                                        <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <!-- Prerequisites -->
                                    <div class="col-md-12 mb-3">
                                        <label for="course-prerequisites" class="form-label">Prerequisites</label>
                                        <textarea id="course-prerequisites" wire:model="prerequisites"
                                                  class="form-control"
                                                  placeholder="Any prerequisites or prior knowledge required"
                                                  rows="3"></textarea>
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
                        <!-- Title -->
                        <h6 class="mb-0 fw-semibold text-primary d-flex align-items-center">
                            <iconify-icon icon="mdi:book-open-page-variant" class="me-2"
                                          style="font-size: 20px;"></iconify-icon>
                            Courses List
                        </h6>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 flex-wrap">

                            <!-- Export Excel Button -->
                            <button wire:click="exportExcel"
                                    class="btn btn-outline-success btn-sm d-flex align-items-center px-3 py-1 rounded">
                                <iconify-icon icon="mdi:file-excel-outline" class="me-1"
                                              style="font-size: 18px;"></iconify-icon>
                                Excel
                            </button>

                            <!-- Export PDF Button -->
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
                                           class="form-check-input" wire:model="selectAll"/>
                                </div>
                            </th>
                            <th>#</th>
                            <th>Title</th>
                            <th>Code</th>
                            <th>Description</th>
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
                                               value="{{ (string) $course->id }}"/>
                                    </div>
                                </td>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $course->title }}</td>
                                <td>{{ $course->code }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($course->description, 60) }}</td>
                                <td>KES {{ number_format($course->price, 2) }}</td>
                                <td>
                                    <div class="action-btn">
                                        <a href="{{ route('courses.view', $course->id) }}" class="text-info">
                                            <i class="ti ti-eye fs-5"></i>
                                        </a>
                                        @can('edit-courses')
                                            <a href="javascript:void(0)" wire:click="editCourse({{ $course->id }})"
                                               class="text-primary ms-2 ">
                                                <i class="ti ti-pencil  fs-5"></i>
                                            </a>
                                        @endcan
                                        @can('delete-courses')
                                            <a href="javascript:void(0)"
                                               wire:click="deleteCourse({{ $course->id }})"
                                               class="text-dark ms-2">
                                                <i class="ti ti-trash fs-5"></i>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No courses found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Add the pagination links here --}}
                <div class="d-flex justify-content-center mt-4">
                    {{ $courses->links() }}
                </div>

            </div>


        </div>
    </div>
</div>

@push('scripts')
    <script>
        window.addEventListener('show-course-modal', () => {
            new bootstrap.Modal(document.getElementById('addCourseModal')).show();
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
    </script>
@endpush
