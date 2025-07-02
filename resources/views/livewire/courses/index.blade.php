<?php

use App\Models\Course;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Log;

new class extends Component {

    public $courses = [];

    public $title, $description, $price;

    public $editId = null;

    public $selected = [];

    public $selectAll = false;

    public $search = '';

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ];
    }

    public function mount()
    {
        $this->loadCourses();
    }

    #[On('search')]
    public function search()
    {
        $this->loadCourses();
    }

    public function loadCourses()
    {
        $this->courses = Course::query()
            ->when($this->search, fn($q) => $q->where(function ($query) {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->get();
    }

    public function addCourse()
    {
        $this->validate();

        try {
            Course::create([
                'title' => $this->title,
                'description' => $this->description,
                'price' => $this->price,
            ]);

            $this->resetForm();
            $this->loadCourses();
            $this->dispatch('hide-course-modal');

        } catch (\Exception $e) {
            Log::error('Error adding course: ' . $e->getMessage());
            session()->flash('error', 'Failed to add course.');
        }
    }

    public function editCourse($id)
    {
        $course = Course::findOrFail($id);

        $this->editId = $course->id;
        $this->title = $course->title;
        $this->description = $course->description;
        $this->price = $course->price;

        $this->dispatch('show-course-modal');
    }

    public function updateCourse()
    {
        $this->validate();

        try {
            $course = Course::findOrFail($this->editId);

            $course->update([
                'title' => $this->title,
                'description' => $this->description,
                'price' => $this->price,
            ]);

            $this->resetForm();
            $this->loadCourses();
            $this->dispatch('hide-course-modal');

        } catch (\Exception $e) {
            Log::error('Error updating course: ' . $e->getMessage());
            session()->flash('error', 'Update failed.');
        }
    }

    public function deleteCourse($id)
    {
        Course::findOrFail($id)->delete();
        $this->loadCourses();
    }

    public function deleteSelected()
    {
        Course::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
        $this->loadCourses();
    }

    private function resetForm()
    {
        $this->title = $this->description = $this->price = null;
        $this->editId = null;
    }

    #[On('select-all')]
    public function selectAll()
    {
        if ($this->selectAll) {
            $this->selected = $this->courses->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selected = [];
        }
    }

}; ?>

<div class="row">
    <div class="col-12">
        <div class="widget-content searchable-container list">
            <div class="card card-body">
                <div class="row">
                    <div class="col-md-4 col-xl-3">
                        <form class="position-relative">
                            <input wire:keyup.debounce.100ms="$dispatch('search')" type="text"
                                   class="form-control product-search ps-5" placeholder="Search Courses..."
                                   wire:model="search"/>
                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                        @if (count($selected) > 0)
                            <div class="action-btn">
                                <a href="javascript:void(0)" wire:click.prevent="deleteSelected"
                                   class="delete-multiple bg-danger-subtle btn me-2 text-danger">
                                    <i class="ti ti-trash me-1 fs-5"></i> Delete Selected
                                </a>
                            </div>
                        @endif
                        <a href="javascript:void(0)" wire:click="$dispatch('show-course-modal')"
                           class="btn btn-primary d-flex align-items-center">
                            <i class="ti ti-book text-white me-1 fs-5"></i> Add Course
                        </a>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="addCourseModal" tabindex="-1" role="dialog"
                 aria-labelledby="addCourseModalTitle" aria-hidden="true" wire:ignore.self>
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header d-flex align-items-center">
                            <h5 class="modal-title">Course</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form wire:submit.prevent="{{ $editId ? 'updateCourse' : 'addCourse' }}">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <input type="text" wire:model.live="title" class="form-control"
                                               placeholder="Course Title"/>
                                        @error('title') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="number" step="0.01" wire:model.live="price" class="form-control"
                                               placeholder="Fee"/>
                                        @error('price') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <textarea wire:model.live="description" class="form-control"
                                                  placeholder="Course Description" rows="4"></textarea>
                                        @error('description') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="d-flex gap-6 m-0">
                                    <button type="submit" class="btn btn-success">
                                        {{ $editId ? 'Save' : 'Add' }}
                                    </button>
                                    <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">
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
                    <table class="table search-table align-middle text-nowrap">
                        <thead class="header-item">
                        <tr>
                            <th>
                                <div class="form-check text-center">
                                    <input wire:click="$dispatch('select-all')" type="checkbox" class="form-check-input"
                                           wire:model="selectAll"/>
                                </div>
                            </th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Fee</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($courses as $course)
                            <tr class="search-items">
                                <td>
                                    <div class="form-check text-center">
                                        <input type="checkbox" class="form-check-input"
                                               wire:model="selected" value="{{ (string) $course->id }}"/>
                                    </div>
                                </td>
                                <td>{{ $course->title }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($course->description, 60) }}</td>
                                <td>KES {{ number_format($course->price, 2) }}</td>
                                <td>
                                    <div class="action-btn">
                                        <a href="{{ route('courses.view',$course->id) }}"
                                           class="text-info">
                                            <i class="ti ti-eye fs-5"></i>
                                        </a>
                                        <a href="javascript:void(0)" wire:click="editCourse({{ $course->id }})"
                                           class="text-primary ms-2 ">
                                            <i class="ti ti-pencil  fs-5"></i>
                                        </a>
                                        <a href="javascript:void(0)" wire:click="deleteCourse({{ $course->id }})"
                                           class="text-dark ms-2">
                                            <i class="ti ti-trash fs-5"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No courses found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
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
    </script>
@endpush




