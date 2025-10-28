<?php

use App\Exports\StudentExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Student;
use App\Models\Course;
use App\Models\User;
use App\Models\Intake;
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
    public $perPage = 10;

    public $first_name, $last_name, $admission_number, $email, $phone_number, $date_of_birth, $active;

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
            'email' => 'required|email|unique:users,email,' . optional(Student::find($this->editId))->user_id,
            'phone_number' => 'required|string',
            'date_of_birth' => 'required|date',
            'address' => 'nullable|string|max:500',
            'country' => 'nullable|string|max:100',
            'highest_level_of_education' => 'nullable|string|max:255',
            'id_url' => 'nullable|file|mimes:pdf,jpeg,png,jpg,gif|max:2048',
            'kcse_certificate' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:2048',
            'passport_size_url' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048'
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
                // $query->where('active', true);
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
            ->orderBy('first_name', 'asc')
            ->paginate($this->perPage ?? 10);

        return [
            'students' => $students
        ];
    }

    public function addStudent()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $this->first_name . ' ' . $this->last_name,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'password' => Hash::make($this->phone_number),
                'active' => true,
            ]);

            // Create the student
            if (!$this->admission_number) {
                $admissionnumber = Student::generateAdmissionNumber();
            } else {
                $admissionnumber = $this->admission_number;
            }
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

            dd($e->getMessage());

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
        $this->active = $student->user ? $student->user->active : false;
        $this->admission_number = $student->admission_number;

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
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'phone' => $this->phone_number,
                'active' => $this->active,
                'email' => $this->email,
            ]);

            $student->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone_number,
                'admission_number' => $this->admission_number,
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
                ->map(fn($id) => (string) $id)
                ->toArray();

            $this->selected = $currentPageStudentIds;
        } else {
            $this->selected = [];
        }
    }

    public function exportExcel()
    {
        return Excel::download(app(StudentExport::class), 'students.xlsx');
    }

    public function exportPdf()
    {
        $url = route('students.export.pdf');
        return redirect()->to($url);
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
                                            class="form-control" placeholder="Enter your first name" />
                                        @error('first_name')
                                            <small class="text-error text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" wire:model.live="last_name" id="last_name"
                                            class="form-control" placeholder="Enter your last name" />
                                        @error('last_name')
                                            <small class="text-error text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" wire:model.live="email" id="email"
                                            class="form-control" placeholder="Enter your email address" />
                                        @error('email')
                                            <small class="text-error text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="admission_number" class="form-label">Admission Number</label>
                                        <input type="text" wire:model.live="admission_number" id="admission_number"
                                            class="form-control" placeholder="leave empty for auto allocation eg 00112" />
                                        @error('admission_number')
                                            <small class="text-error text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="phone_number" class="form-label">Phone Number</label>
                                        <input type="text" wire:model.live="phone_number" id="phone_number"
                                            class="form-control" placeholder="Enter your phone number" />
                                        @error('phone_number')
                                            <small class="text-error text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="country" class="form-label">Country</label>
                                        <input type="text" wire:model.live="country" id="country"
                                            class="form-control" placeholder="Enter your country" />
                                        @error('country')
                                            <small class="text-error text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                        <input type="date" wire:model.live="date_of_birth" id="date_of_birth"
                                            class="form-control" placeholder="Select your date of birth" />
                                        @error('date_of_birth')
                                            <small class="text-error text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <!-- New Fields -->
                                    <div class="col-md-4 mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <input type="text" wire:model.live="address" id="address"
                                            class="form-control" placeholder="Enter your address" />
                                        @error('address')
                                            <small class="text-error text-danger">{{ $message }}</small>
                                        @enderror
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
                                        <input type="file" wire:model.live="id_url" id="id_url"
                                            class="form-control" placeholder="Upload your ID" />
                                        @error('id_url')
                                            <small class="text-error text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="kcse_certificate" class="form-label">Upload KCSE
                                            Certificate</label>
                                        <input type="file" wire:model.live="kcse_certificate"
                                            id="kcse_certificate" class="form-control"
                                            placeholder="Upload your KCSE certificate" />
                                        @error('kcse_certificate')
                                            <small class="text-error text-danger">{{ $message }}</small>
                                        @enderror
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
                                    <div class="col-md-4 mb-3">
                                        <label for="active" class="form-label">Status</label>
                                        <select wire:model.live="active" id="active" class="form-select">
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                        @error('active')
                                            <small class="text-error text-danger">{{ $message }}</small>
                                        @enderror
                                        </select>
                                        @error('intake_id')
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

                <!-- Table Responsive -->
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
                        <!-- Title -->
                        <h6 class="mb-0 fw-semibold text-primary d-flex align-items-center">
                            <iconify-icon icon="mdi:account-group" class="me-2"
                                style="font-size: 20px;"></iconify-icon>
                            Student List
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
                                <th class="text-center align-middle" style="width: 40px;">
                                    <input wire:click="$dispatch('select-all')" type="checkbox"
                                        class="form-check-input m-0" wire:model="selectAll" />
                                </th>
                                <th>#</th>
                                <th>Admission Number</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone Number</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                            @forelse ($students as $student)
                                <tr class="search-items align-middle" style="border-bottom: 1px solid #e5e7eb;">
                                    <!-- Checkbox -->
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input" wire:model="selected"
                                            value="{{ (string) $student->id }}" />
                                    </td>

                                    <!-- Iteration -->
                                    <td class="fw-bold text-primary" style="color: #0e334f;">
                                        {{ $loop->iteration }}
                                    </td>

                                    <!-- Admission Number -->
                                    <td class="text-uppercase fw-semibold" style="color: #f69122;">
                                        {{ 'TTI/' . $student->admission_number . '/' . $student->created_at->format('Y') }}
                                    </td>

                                    <!-- Full Name -->
                                    <td class="fw-semibold" style="color: #0e334f;">
                                        {{ $student->first_name }} {{ $student->last_name }}
                                    </td>

                                    <!-- Email -->
                                    <td>
                                        <span class="badge bg-light text-dark px-2 py-1 rounded-pill">
                                            {{ $student->email }}
                                        </span>
                                    </td>

                                    <!-- Phone -->
                                    <td><span class="text-muted">{{ $student->phone }}</span></td>

                                    <!-- Status -->
                                    <td>
                                        @if ($student->user && $student->user->active)
                                            <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill">
                                                Active
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger px-2 py-1 rounded-pill">
                                                Inactive
                                            </span>
                                        @endif

                                    <!-- Actions Dropdown -->
                                    <td>
                                        <div class="ms-auto">
                                            <div class="dropdown dropstart">
                                                <a href="javascript:void(0)" class="link"
                                                    id="student-actions-{{ $student->id }}"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ti ti-dots-vertical fs-6" style="color: #0e334f;"></i>
                                                </a>
                                                <ul class="dropdown-menu"
                                                    aria-labelledby="student-actions-{{ $student->id }}">
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-2"
                                                            href="{{ route('students.view', $student->id) }}">
                                                            <iconify-icon icon="mdi:eye-outline"
                                                                class="text-primary w-4 h-4"></iconify-icon>
                                                            <span>View</span>
                                                        </a>
                                                    </li>
                                                    @can('edit-students')
                                                        <li>
                                                            <a class="dropdown-item d-flex align-items-center gap-2"
                                                                href="javascript:void(0)"
                                                                wire:click="editStudent({{ $student->id }})">
                                                                <iconify-icon icon="mdi:pencil-outline"
                                                                    class="text-warning w-4 h-4"></iconify-icon>
                                                                <span>Edit</span>
                                                            </a>
                                                        </li>
                                                    @endcan
                                                    @can('delete-students')
                                                        <li>
                                                            <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                                href="javascript:void(0)"
                                                                wire:click="deleteStudent({{ $student->id }})">
                                                                <iconify-icon icon="mdi:delete-outline"
                                                                    class="text-danger w-4 h-4"></iconify-icon>
                                                                <span>Delete</span>
                                                            </a>
                                                        </li>
                                                    @endcan
                                                </ul>
                                            </div>
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

                <!-- Pagination -->
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
