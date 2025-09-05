<?php

use App\Models\Student;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {

    use WithFileUploads, WithPagination;

    public $selectAll = false;

    public $first_name, $last_name, $admission_number, $email, $phone_number, $date_of_birth;

    public $editId = null;

    public $selected = [];

    public $search = '';

    public $address, $country, $highest_level_of_education;
    public $id_url, $kcse_certificate, $passport_size_url;

    public function rules()
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'admission_number' => 'nullable|string|max:255|unique:students,admission_number,' . $this->editId,
            'email' => 'required|email',
            'phone_number' => 'required|numeric',
            'date_of_birth' => 'required|date',
            'address' => 'nullable|string|max:500',
            'country' => 'nullable|string|max:100',
            'highest_level_of_education' => 'nullable|string|max:255',
            'id_url' => 'nullable|file|mimes:pdf,jpeg,png,jpg,gif|max:2048',
            'kcse_certificate' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:2048',
            'passport_size_url' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function mount()
    {
        if (!auth()->user()->hasPermissionTo('view-students')) {
            abort(403, 'Unauthorized action.');
        }
    }

    #[On('search')]
    public function search()
    {
        $this->resetPage();
    }

    public function with()
    {
        $students = Student::with(['user'])
            ->whereHas('user', function ($query) {
                $query->where('active', true);
            })
            ->when(
                !empty($this->search),
                fn($q) => $q->where(function ($query) {
                    $query
                        ->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('admission_number', 'like', "%{$this->search}%");
                }),
            )
            ->orderBy('admission_number', 'asc')
            ->paginate(10);

        return [
            'students' => $students, // Pass the Paginator instance
        ];
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

            // Create the student
            $admissionnumber = Student::generateAdmissionNumber();
            // Generate the next admission number

            $this->admission_number = $admissionnumber;

            // Create the student with the next admission number
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

            $user->assignRole('student');

            DB::commit();

            $this->resetForm();
            $this->resetPage();
            $this->dispatch('hide-student-modal');

            LivewireAlert::text('Student added successfully.!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding student: ' . $e->getMessage());

            LivewireAlert::text('Failed to add student.!')->error()->toast()->position('top-end')->show();
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
        $this->address = $student->address;
        $this->country = $student->country;
        $this->highest_level_of_education = $student->highest_level_of_education;

        $this->id_url = $student->id_url;
        $this->kcse_certificate = $student->kcse_certificate;
        $this->passport_size_url = $student->passport_size_url;

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
                'address' => $this->address,
                'country' => $this->country,
                'highest_level_of_education' => $this->highest_level_of_education,
                'id_url' => $this->id_url ? $this->id_url->store('students/ids', 'public') : $student->id_url,
                'kcse_certificate' => $this->kcse_certificate ? $this->kcse_certificate->store('students/certificates', 'public') : $student->kcse_certificate,
                'passport_size_url' => $this->passport_size_url ? $this->passport_size_url->store('students/passport_size', 'public') : $student->passport_size_url,
            ]);

            DB::commit();

            $this->resetForm();
            $this->dispatch('hide-student-modal');

            LivewireAlert::text('Student updated successfully.!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update student: ' . $e->getMessage());

            LivewireAlert::text('Failed to update student.!')->error()->toast()->position('top-end')->show();
        }
    }

    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id);
        $student->user()->delete();
        $this->resetPage();

        LivewireAlert::text('Student deleted successfully.!')->success()->toast()->position('top-end')->show();
    }

    public function deleteSelected()
    {
        $students = Student::whereIn('id', $this->selected)->get();
        foreach ($students as $student) {
            $student->user()->delete();
        }

        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();


        LivewireAlert::text('Students deleted successfully.!')->success()->toast()->position('top-end')->show();
    }

    private function resetForm()
    {
        $this->first_name = $this->last_name = $this->admission_number = $this->email = $this->phone_number = $this->date_of_birth = null;
        $this->editId = null;
    }

    #[On('select-all')]
    public function selectAll()
    {
        if ($this->selectAll) {

            $currentPageStudentIds = Student::with(['user'])
                ->when(
                    !empty($this->search),
                    fn($q) => $q->where(function ($query) {
                        $query
                            ->where('first_name', 'like', "%{$this->search}%")
                            ->orWhere('last_name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%")
                            ->orWhere('phone', 'like', "%{$this->search}%");
                    }),
                )
                ->latest()
                ->paginate(10)
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();

            $this->selected = $currentPageStudentIds;
        } else {
            $this->selected = [];
        }
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
                        <form class="position-relative">
                            <input wire:keyup.debounce.100ms="$dispatch('search')" type="text"
                                class="form-control product-search ps-5" placeholder="Search Students..."
                                wire:model="search" />
                            <i
                                class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                    </div>
                    <div
                        class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">

                        @if (count($selected) > 0)
                            @can('delete-students')
                                <div class="action-btn">
                                    <a href="javascript:void(0)" wire:click.prevent="deleteSelected"
                                       class="delete-multiple bg-danger-subtle btn me-2 text-danger">
                                        <i class="ti ti-trash me-1 fs-5"></i> Delete Selected
                                    </a>
                                </div>
                            @endcan
                        @endif
                        @can('add-students')
                            <a href="javascript:void(0)" wire:click="$dispatch('show-student-modal')"
                                class="btn btn-primary d-flex align-items-center">
                                <i class="ti ti-users text-white me-1 fs-5"></i> Add Student
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog"
                 aria-labelledby="addStudentModalTitle" aria-hidden="true" wire:ignore.self>
                <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header d-flex align-items-center">
                            <h5 class="modal-title">Add Student</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form wire:submit.prevent="{{ $editId ? 'updateStudent' : 'addStudent' }}">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" wire:model.live="first_name" id="first_name"
                                               class="form-control" placeholder="Enter your first name"/>
                                        @error('first_name')<small
                                            class="text-error text-danger">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" wire:model.live="last_name" id="last_name"
                                               class="form-control" placeholder="Enter your last name"/>
                                        @error('last_name')<small
                                            class="text-error text-danger">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" wire:model.live="email" id="email" class="form-control"
                                               placeholder="Enter your email address"/>
                                        @error('email')<small
                                            class="text-error text-danger">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="phone_number" class="form-label">Phone Number</label>
                                        <input type="text" wire:model.live="phone_number" id="phone_number"
                                               class="form-control" placeholder="Enter your phone number"/>
                                        @error('phone_number')<small
                                            class="text-error text-danger">{{ $message }}</small>@enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="country" class="form-label">Country</label>
                                        <input type="text" wire:model.live="country" id="country" class="form-control"
                                               placeholder="Enter your country"/>
                                        @error('country')<small
                                            class="text-error text-danger">{{ $message }}</small>@enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                        <input type="date" wire:model.live="date_of_birth" id="date_of_birth"
                                               class="form-control" placeholder="Select your date of birth"/>
                                        @error('date_of_birth')<small
                                            class="text-error text-danger">{{ $message }}</small>@enderror
                                    </div>

                                    <!-- New Fields -->
                                    <div class="col-md-4 mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <input type="text" wire:model.live="address" id="address" class="form-control"
                                               placeholder="Enter your address"/>
                                        @error('address')<small
                                            class="text-error text-danger">{{ $message }}</small>@enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="highest_level_of_education" class="form-label">Highest Level of
                                            Education</label>
                                        <input type="text" wire:model.live="highest_level_of_education"
                                            id="highest_level_of_education" class="form-control"
                                            placeholder="Enter your highest level of education" />
                                        @error('highest_level_of_education')
                                            <small class="text-error text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <!-- File Inputs -->
                                    <div class="col-md-4 mb-3">
                                        <label for="id_url" class="form-label">Upload ID</label>
                                        <input type="file" wire:model.live="id_url" id="id_url" class="form-control"
                                               placeholder="Upload your ID"/>
                                        @error('id_url')<small
                                            class="text-error text-danger">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="kcse_certificate" class="form-label">Upload KCSE Certificate</label>
                                        <input type="file" wire:model.live="kcse_certificate" id="kcse_certificate"
                                               class="form-control" placeholder="Upload your KCSE certificate"/>
                                        @error('kcse_certificate')<small
                                            class="text-error text-danger">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="passport_size_url" class="form-label">Upload Passport Size
                                            Photo</label>
                                        <input type="file" wire:model.live="passport_size_url"
                                            id="passport_size_url" class="form-control"
                                            placeholder="Upload your passport size photo" />
                                        @error('passport_size_url')
                                            <small class="text-error text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <div class="d-flex gap-1 m-0">
                                    <button type="button" class="btn btn-danger bg-error-subtle"
                                        data-bs-dismiss="modal">Discard
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        {{ $editId ? 'Save' : 'Add' }}
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
                                        <input wire:click="$dispatch('select-all')" type="checkbox"
                                            class="form-check-input" wire:model="selectAll" />
                                    </div>
                                </th>
                                <th>#</th>
                                <th>Admission Number</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone Number</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                            @forelse ($students as $student)
                                <tr class="search-items">
                                    <td>
                                        <div class="form-check text-center">
                                            <input type="checkbox" class="form-check-input" wire:model="selected"
                                                value="{{ (string) $student->id }}" />
                                        </div>
                                    </td>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ 'TTI/' . $student->admission_number . '/' . $student->created_at->format('Y') }}</td>
                                    <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                                    <td>{{ $student->email }}</td>
                                    <td>{{ $student->phone }}</td>
                                    <td>
                                        <div class="action-btn dropdown">
                                            <a href="#" class="text-primary dropdown-toggle" id="studentActions"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-5"></i>
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="studentActions">
                                                <li>
                                                    <a href="{{ route('students.view', $student->id) }}"
                                                        class="dropdown-item">
                                                        <i class="ti ti-eye fs-5 me-2"></i> View
                                                    </a>
                                                </li>
                                                @can('edit-students')
                                                    <li>
                                                        <a href="javascript:void(0)"
                                                            wire:click="editStudent({{ $student->id }})"
                                                            class="dropdown-item">
                                                            <i class="ti ti-pencil fs-5 me-2"></i> Edit
                                                        </a>
                                                    </li>
                                                @endcan
                                                @can('delete-students')
                                                    <li>
                                                        <a href="javascript:void(0)"
                                                            wire:click="deleteStudent({{ $student->id }})"
                                                            class="dropdown-item">
                                                            <i class="ti ti-trash fs-5 me-2"></i> Delete
                                                        </a>
                                                    </li>
                                                @endcan
                                            </ul>
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

                {{-- Add the pagination links here --}}
                <div class="d-flex justify-content-center mt-4">
                    {{ $students->links() }}
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
