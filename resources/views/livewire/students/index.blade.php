<?php

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


new class extends Component {

    public $students = [];

    public $selectAll = false;

    public $first_name, $last_name, $email, $phone_number, $date_of_birth;

    public $editId = null;

    public $selected = [];

    public $search = '';


    public function rules()
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone_number' => 'required|numeric',
            'date_of_birth' => 'required|date',
        ];

    }


    public function mount()
    {
        $this->loadStudents();
    }


    #[On('search')]
    public function search()
    {
        $this->loadStudents();
    }


    public function loadStudents()
    {
        $this->students = Student::with('user')
            ->when($this->search, fn($q) => $q->where(function ($query) {
                $query->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->get();
    }


    public function addStudent()
    {
        $this->validate();

        try {

            DB::beginTransaction();

            $user = User::create([
                'name' => $this->first_name . ' ' . $this->last_name,
                'email' => $this->email,
                'password' => Hash::make('password'),
            ]);

            Student::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone_number,
                'dob' => $this->date_of_birth,
                'user_id' => $user->id,
            ]);

            DB::commit();

            $this->resetForm();
            $this->loadStudents();
            $this->dispatch('hide-student-modal');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding student: ' . $e->getMessage());
            session()->flash('error', 'Failed to add student. Please try again.');
        }
    }


    public function editStudent($id)
    {
        $student = Student::with('user')->findOrFail($id);

        $this->editId = $student->id;
        $this->first_name = $student->first_name;
        $this->last_name = $student->last_name;
        $this->email = $student->email;
        $this->phone_number = $student->phone;
        $this->date_of_birth = $student->dob;

        $this->dispatch('show-student-modal');
    }


    public function updateStudent()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $student = Student::with('user')->findOrFail($this->editId);

            $student->user->update([
                'name' => $this->first_name . ' ' . $this->last_name,
                'email' => $this->email,
            ]);

            $student->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone_number,
                'dob' => $this->date_of_birth,
            ]);

            DB::commit();

            $this->resetForm();
            $this->loadStudents();
            $this->dispatch('hide-student-modal');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update student: ' . $e->getMessage());
            session()->flash('error', 'Update failed. Please try again.');
        }
    }


    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id);
        $student->user()->delete();
        $this->loadStudents();
    }


    public function deleteSelected()
    {
        $students = Student::whereIn('id', $this->selected)->get();
        foreach ($students as $student) {
            $student->user()->delete();
        }

        $this->selected = [];
        $this->selectAll = false;
        $this->loadStudents();
    }


    private function resetForm()
    {
        $this->first_name = $this->last_name = $this->email =
        $this->phone_number = $this->date_of_birth = null;
        $this->editId = null;
    }


    #[On('select-all')]
    public function selectAll()
    {
        if ($this->selectAll) {
            $this->selected = $this->students->pluck('id')->map(fn($id) => (string)$id)->toArray();
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
                                   class="form-control product-search ps-5" placeholder="Search Students..."
                                   wire:model="search"/>
                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div
                        class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">

                        @if (count($selected) > 0)
                            <div class="action-btn">
                                <a href="javascript:void(0)" wire:click.prevent="deleteSelected"
                                   class="delete-multiple bg-danger-subtle btn me-2 text-danger">
                                    <i class="ti ti-trash me-1 fs-5"></i> Delete Selected
                                </a>
                            </div>
                        @endif

                        <a href="javascript:void(0)" wire:click="$dispatch('show-student-modal')"
                           class="btn btn-primary d-flex align-items-center">
                            <i class="ti ti-users text-white me-1 fs-5"></i> Add Student
                        </a>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog"
                 aria-labelledby="addStudentModalTitle" aria-hidden="true" wire:ignore.self>
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header d-flex align-items-center">
                            <h5 class="modal-title">Contact</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form wire:submit.prevent="{{ $editId ? 'updateStudent' : 'addStudent' }}">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <input type="text" wire:model.live="first_name" class="form-control"
                                               placeholder="First Name"/> @error('first_name') <small
                                            class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="text" wire:model.live="last_name" class="form-control"
                                               placeholder="Last Name"/> @error('last_name') <small
                                            class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="email" wire:model.live="email" class="form-control"
                                               placeholder="Email"/> @error('email') <small
                                            class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="text" wire:model.live="phone_number" class="form-control"
                                               placeholder="Phone Number"/>
                                        @error('phone_number') <small
                                            class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <input type="date" wire:model.live="date_of_birth" class="form-control"
                                               placeholder="Date of Birth"/>
                                        @error('date_of_birth') <small
                                            class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="d-flex gap-6 m-0">
                                    <button type="submit" class="btn btn-success">
                                        {{ $editId ? 'Save' : 'Add' }}
                                    </button>
                                    <button type="button" class="btn bg-danger-subtle text-danger"
                                            data-bs-dismiss="modal">Discard
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
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Date of Birth</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>

                        @forelse ($students as $student)
                            <tr class="search-items">
                                <td>
                                    <div class="form-check text-center">
                                        <input type="checkbox" class="form-check-input"
                                               wire:model="selected" value="{{ (string) $student->id }}"/>
                                    </div>
                                </td>
                                <td>{{ $student->first_name }}</td>
                                <td>{{ $student->last_name }}</td>
                                <td>{{ $student->email }}</td>
                                <td>{{ $student->phone }}</td>
                                <td>{{ \Carbon\Carbon::parse($student->date_of_birth)->format('jS M Y') }}</td>
                                <td>
                                    <div class="action-btn">
                                        <a href="{{ route('students.view',$student->id) }}"
                                           class="text-primary">
                                            <i class="ti ti-eye fs-5"></i>
                                        </a>
                                        <a href="javascript:void(0)" wire:click="editStudent({{ $student->id }})"
                                           class="text-primary">
                                            <i class="ti ti-pencil fs-5"></i>
                                        </a>
                                        <a href="javascript:void(0)" wire:click="deleteStudent({{ $student->id }})"
                                           class="text-dark ms-2">
                                            <i class="ti ti-trash fs-5"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No students found.</td>
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
        window.addEventListener('show-student-modal', () => {
            new bootstrap.Modal(document.getElementById('addStudentModal')).show();
        });

        window.addEventListener('hide-student-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('addStudentModal'))?.hide();
        });
    </script>
@endpush






