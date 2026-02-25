<?php

use App\Exports\PendingEnrollmentExport;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\EnrollmentStatus;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\EnrollmentService;

new class extends Component {
    use WithFileUploads, WithPagination;

    public $selectAll = false;

    public $status, $remarks;

    public $editId = null;
    public $perPage = 10;

    public $selected = [];

    public $search = '';

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
        $pending_enrollments = Enrollment::whereIn('status', ['pending', 'rejected'])
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->select('enrollments.*')
            ->with(['student.user', 'course', 'intake'])
            ->when(
                !empty($this->search),
                fn($q) => $q->whereHas('student', function ($query) {
                    $query
                        ->where('students.first_name', 'like', "%{$this->search}%")
                        ->orWhere('students.last_name', 'like', "%{$this->search}%")
                        ->orWhere('students.email', 'like', "%{$this->search}%")
                        ->orWhere('students.phone', 'like', "%{$this->search}%")
                        ->orWhere('students.admission_number', 'like', "%{$this->search}%");
                }),
            )
            ->orderBy('students.first_name', 'asc')
            ->paginate($this->perPage ?? 10);

        return [
            'enrollments' => $pending_enrollments, // Pass the Paginator instance
        ];
    }

    public function editStatus($id)
    {
        $enrollment = Enrollment::findOrFail($id);

        $this->editId = $enrollment->id;
        $this->status = $enrollment->status;
        $this->remarks = $enrollment->remarks;

        $this->dispatch('show-enrollment-modal');
    }

    public function updateStatus()
    {
        try {
            DB::beginTransaction();

            $enrollment = Enrollment::findOrFail($this->editId);
            $enrollment->status = $this->status;
            $enrollment->remarks = $this->remarks;
            $enrollment->save();

            $student = Student::findOrFail($enrollment->student_id);
                $user = User::findOrFail($student->user_id);

            // Handle approval logic
            if ($this->status === 'approved') {
                // Update user's active status
                $user->active = true;
                $user->save();

                // Update remarks AFTER successful user activation
                $enrollment->remarks = 'Approved by ' . auth()->user()->first_name;
                $enrollment->save();

                //create enrollment trimesters
                EnrollmentService::createEnrollmentTrimesters($enrollment);
            }
            DB::commit();

            // ✅ Send email notification to student upon approval or rejection
            if ($user) {
                $user->notify(new EnrollmentStatus($this->status, $enrollment->program->name ?? 'Unknown Program'));
            }

            $this->resetForm();
            $this->dispatch('hide-enrollment-modal');

            LivewireAlert::text('Enrollment status updated successfully.!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update enrollment status: ' . $e->getMessage());

            LivewireAlert::text('Failed to update enrollment status!')->error()->toast()->position('top-end')->show();
        }
    }

    public function deleteEnrollment($id)
    {
        DB::beginTransaction();
        try {
            $enrollment = Enrollment::findOrFail($id);
            $student = Student::findOrFail($enrollment->student_id);
            $user = User::findOrFail($student->user_id);
            $student->delete();
            $user->delete();
            $enrollment->delete();

            DB::commit();

            LivewireAlert::text('Enrollment deleted successfully!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete enrollment: ' . $e->getMessage());

            LivewireAlert::text('Failed to delete enrollment!')->error()->toast()->position('top-end')->show();
        }
    }

    private function resetForm()
    {
        $this->status = null;
        $this->remarks = null;
    }

    public function exportExcel()
    {
        return Excel::download(app(PendingEnrollmentExport::class), 'pending-enrollments.xlsx');
    }

    public function exportPdf()
    {
        $url = route('pending-enrollments.export.pdf');
        return redirect()->to($url);
    }
}; ?>

@push('styles')
    <style>
        .pagination {
            margin-left: 10px;
        }

        .text-orange {
            color: #f69121 !important;
        }

        .text-blue {
            color: #446076 !important;
        }

        .badge-pending {
            background-color: #f69121;
            color: white;
        }

        .badge-rejected {
            background-color: #dc3545;
            /* Bootstrap red (or customize) */
        }

        .dropdown-menu a.dropdown-item:hover {
            background-color: #446076;
            color: white;
        }

        .table td {
            vertical-align: middle;
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
                                <option value="all">500</option>
                            </select>
                            <span class="ms-2">entries</span>
                        </div>
                        <!-- Title -->
                        <h6 class="mb-0 fw-semibold text-primary d-flex align-items-center">
                            <iconify-icon class="me-2" icon="mdi:progress-clock"
                                style="font-size: 20px; color: orange;"></iconify-icon>
                            Pending Enrollment List
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
                                <th>#</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Intake</th>
                                <th>Phone Number</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($enrollments as $enrollment)
                                <tr class="search-items">
                                    <td class="text-blue fw-bold">{{ $loop->iteration }}</td>
                                    <td class="text-blue">
                                        {{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}
                                    </td>
                                    <td class="text-orange">{{ $enrollment->course->title ?? 'N/A' }}</td>
                                    <td class="text-blue">{{ $enrollment->intake->name }}</td>
                                    <td>{{ $enrollment->student->phone }}</td>
                                    <td>
                                        <span
                                            class="badge
                @if ($enrollment->status == 'pending') badge-pending
                @elseif($enrollment->status == 'rejected') badge-rejected
                @else bg-success @endif">
                                            {{ ucfirst($enrollment->status) }}
                                        </span>
                                    </td>
                                    <td class="text-muted">{{ $enrollment->remarks }}</td>
                                    <td>
                                        <div class="action-btn dropdown">
                                            <a href="#" class="text-blue" id="studentActions"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-5"></i>
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="studentActions">
                                                @can('edit-students')
                                                    <li>
                                                        <a href="javascript:void(0)"
                                                            wire:click="editStatus({{ $enrollment->id }})"
                                                            class="dropdown-item">
                                                            <i class="ti ti-pencil fs-5 me-2"></i> Edit
                                                        </a>
                                                    </li>
                                                @endcan
                                                @can('delete-students')
                                                    <li>
                                                        <a href="javascript:void(0)"
                                                            wire:click="deleteEnrollment({{ $enrollment->id }})"
                                                            wire:confirm="Are you sure you want to delete this enrollment?"
                                                            class="dropdown-item text-danger">
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
                                    <td colspan="9" class="text-center text-muted">No Enrollments found.</td>
                                </tr>
                            @endforelse

                        </tbody>
                    </table>
                </div>

                {{-- Add the pagination links here --}}
                <div class="d-flex justify-content-center mt-4">
                    {{ $enrollments->links() }}
                </div>

            </div>

            <!-- Modal -->
            <div class="modal fade" id="enrollmentModal" tabindex="-1" role="dialog"
                aria-labelledby="enrollmentModalTitle" aria-hidden="true" wire:ignore.self>
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header d-flex align-items-center">
                            <h5 class="modal-title">Update Status</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form wire:submit.prevent="{{ 'updateStatus' }}">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select wire:model="status" id="status" class="form-control" required>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="withdrawn">Withdrawn</option>
                                            <option value="completed">Completed</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="remarks" class="form-label">Remarks</label>
                                        <textarea wire:model="remarks" id="remarks" class="form-control" rows="3"></textarea>
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
        </div>
    </div>
</div>

@push('scripts')
    <script>
        window.addEventListener('show-enrollment-modal', () => {
            new bootstrap.Modal(document.getElementById('enrollmentModal')).show();
        });

        window.addEventListener('hide-enrollment-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('enrollmentModal'))?.hide();
        });
    </script>
@endpush
