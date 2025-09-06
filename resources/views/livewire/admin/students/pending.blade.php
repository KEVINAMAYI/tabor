<?php

use App\Models\Student;
use App\Models\Enrollment;
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

    public $status, $remarks;

    public $editId = null;

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
            ->with(['student.user', 'course', 'intake'])
            ->when(
                !empty($this->search),
                fn($q) => $q->whereHas('student', function ($query) {
                    $query
                        ->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('admission_number', 'like', "%{$this->search}%");
                }),
            )
            ->paginate(10);

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

            if ($this->status == 'approved') {
                $enrollment->remarks = null;
                $user = User::find($enrollment->student->user_id);
                $user->active = true;
                $user->save();
            }

            //send email notification to student upon approval/rejection

            DB::commit();

            $this->resetForm();
            $this->dispatch('hide-enrollment-modal');

            LivewireAlert::text('Enrollment status updated successfully.!')->success()->toast()->position('top-end')->show();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update enrollment status: ' . $e->getMessage());

            LivewireAlert::text('Failed to update enrollment status.!')->error()->toast()->position('top-end')->show();
        }
    }

    /* public function deleteSelected()
    {
        $students = Student::whereIn('id', $this->selected)->get();
        foreach ($students as $student) {
            $student->user()->delete();
        }

        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();

        LivewireAlert::text('Students deleted successfully.!')->success()->toast()->position('top-end')->show();
    } */

    private function resetForm()
    {
        $this->status = null;
        $this->remarks = null;
    }

    /* #[On('select-all')]
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
    } */
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
                                    <td>
                                        <div class="form-check text-center">
                                            <input type="checkbox" class="form-check-input" wire:model="selected"
                                                value="{{ (string) $enrollment->id }}" />
                                        </div>
                                    </td>
                                    <td>{{ $loop->iteration }}</td>
                                    {{-- <td>{{ 'TTI/' . $enrollment->student->admission_number . '/' . $enrollment->course->code . '/' . $enrollment->created_at->format('Y') }}
                                    </td> --}}
                                    <td>{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}
                                    </td>
                                    <td>{{ $enrollment->course->title }}</td>
                                    <td>{{ $enrollment->intake->name }}</td>
                                    <td>{{ $enrollment->student->phone }}</td>
                                    <td>
                                        <span
                                            class="badge
                                            @if ($enrollment->status == 'pending') bg-warning
                                            @elseif($enrollment->status == 'rejected') bg-danger @endif">
                                            {{ ucfirst($enrollment->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $enrollment->remarks }}</td>
                                    <td>
                                        <div class="action-btn dropdown">
                                            <a href="#" class="text-primary dropdown-toggle" id="studentActions"
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
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No Enrollments found.</td>
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
