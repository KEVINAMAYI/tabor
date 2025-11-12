<?php

use App\Exports\EnrollmentExport;
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
use Maatwebsite\Excel\Facades\Excel;

new class extends Component {
    use WithFileUploads, WithPagination;

    public $selectAll = false;

    public $status;
    public $perPage = 10;

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
        $active_enrollments = Enrollment::where('enrollments.status', 'approved')
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->leftJoin('payments', 'enrollments.id', '=', 'payments.enrollment_id') // Optional, if you want search by payment reference
            ->with(['student.user', 'course', 'intake', 'payments'])
            ->when(!empty($this->search), function ($query) {
                $query->where(function ($q) {
                    $q->where('students.first_name', 'like', "%{$this->search}%")
                        ->orWhere('students.last_name', 'like', "%{$this->search}%")
                        ->orWhere('students.email', 'like', "%{$this->search}%")
                        ->orWhere('students.phone', 'like', "%{$this->search}%")
                        ->orWhere('students.admission_number', 'like', "%{$this->search}%")
                        ->orWhere('payments.reference', 'like', "%{$this->search}%"); // Payment reference search
                });
            })
            ->orderBy('students.first_name', 'asc')
            ->select('enrollments.*') // Avoid ambiguous column issues
            ->groupBy('enrollments.id')
            ->paginate($this->perPage ?? 10);

       /*  $active_enrollments = Enrollment::where('status', 'approved')
            ->whereHas('student', function ($q) {
                $q->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
                    ->orWhere('admission_number', 'like', "%{$this->search}%");
            })
            ->orWhereHas('payments', function ($q) {
                $q->where('reference', 'like', "%{$this->search}%");
            })
            ->with(['student.user', 'course', 'intake', 'payments'])
            ->orderBy('student.first_name', 'asc')
            ->paginate($this->perPage ?? 10); */

        return [
            'enrollments' => $active_enrollments, // Pass the Paginator instance
        ];
    }

    public function exportExcel()
    {
        return Excel::download(app(EnrollmentExport::class), 'enrollments.xlsx');
    }

    public function exportPdf()
    {
        $url = route('enrollments.export.pdf');
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

        .search-table th {
            background-color: #f8f9fa;
            color: #446076;
            font-weight: 600;
        }

        .search-table td {
            vertical-align: middle;
            color: #444;
        }

        .search-table tbody tr:hover {
            background-color: #fdf3e7;
        }

        .dropdown-toggle::after {
            display: none !important;
            /* removes the caret */
        }

        .dropdown-menu .dropdown-item:hover {
            background-color: #446076;
            color: white;
        }

        .action-btn .dropdown-toggle {
            padding: 6px;
            display: inline-block;
        }

        .text-muted-small {
            font-size: 0.875rem;
            color: #6c757d;
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
                            <iconify-icon icon="mdi:school-outline" class="me-2"
                                style="font-size: 20px;"></iconify-icon>
                            Enrollments List
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
                                <th>Enrollment ID</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Intake</th>
                                <th>Paid Amount</th>
                                <th>Balance</th>
                                <th>Approved On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                            @forelse ($enrollments as $enrollment)
                                <tr class="search-items">
                                    <td class="text-blue fw-semibold">{{ $loop->iteration }}</td>
                                    <td class="text-muted-small">
                                        {{ 'TTI/' . $enrollment->student->admission_number . '/' . $enrollment->course->code . '/' . $enrollment->created_at->format('Y') }}
                                    </td>
                                    <td class="text-blue">
                                        <a href="{{ route('students.view', $enrollment->student->id) }}">
                                            {{ !empty($enrollment) ? $enrollment->student->first_name . ' ' . $enrollment->student->last_name : 'N/A' }}
                                        </a>
                                    </td>
                                    <td class="text-orange">
                                        <a href="{{ route('courses.view', $enrollment->course->id) }}">
                                            {{ !empty($enrollment) ? $enrollment->course->title . '-' . $enrollment->course->level : 'N/A' }}
                                        </a>
                                    </td>
                                    <td>{{ $enrollment->intake->name }}</td>
                                    <td class="text-success fw-bold">
                                        {{ number_format($enrollment->payments->sum('amount'), 2) }}
                                    </td>
                                    <td class="text-danger fw-bold">
                                        {{ number_format($enrollment->course->price - $enrollment->payments->sum('amount'), 2) }}
                                    </td>
                                    <td>{{ $enrollment->created_at->format('d-m-Y') }}</td>
                                    <td>
                                        <div class="action-btn dropdown">
                                            <a href="#" class="text-blue" id="studentActions"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-5"></i>
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="studentActions">
                                                @can('edit-students')
                                                    <li>
                                                        <a href="{{ route('students.view', $enrollment->student->id) }}"
                                                            class="dropdown-item">
                                                            <i class="ti ti-eye fs-5 me-2"></i> View
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
                <div class="modal-dialog modal modal-dialog-centered" role="document">
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
