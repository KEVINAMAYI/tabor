<?php

use App\Exports\ArrearsExport;
use App\Models\Course;
use App\Services\Reports\ArrearsReportService;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public $courseFilter = '';
    public $minBalance = '';
    public $perPage = 15;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-reports'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCourseFilter(): void
    {
        $this->resetPage();
    }

    public function updatingMinBalance(): void
    {
        $this->resetPage();
    }

    public function getCoursesProperty()
    {
        return Course::orderBy('title')->get(['id', 'title']);
    }

    protected function filters(): array
    {
        return [
            $this->courseFilter !== '' ? (int) $this->courseFilter : null,
            $this->search !== '' ? $this->search : null,
            (float) ($this->minBalance !== '' ? $this->minBalance : 0),
        ];
    }

    public function getSummaryProperty(): array
    {
        return app(ArrearsReportService::class)->summary(...$this->filters());
    }

    public function getRowsProperty()
    {
        return app(ArrearsReportService::class)->query(...$this->filters())->paginate($this->perPage);
    }

    public function exportExcel()
    {
        return Excel::download(new ArrearsExport(...$this->filters()), 'outstanding-balances-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportPdf()
    {
        return redirect()->to(route('reports.arrears.export.pdf', [
            'course' => $this->courseFilter !== '' ? $this->courseFilter : null,
            'search' => $this->search !== '' ? $this->search : null,
            'min_balance' => $this->minBalance !== '' ? $this->minBalance : null,
        ]));
    }
}; ?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Outstanding Balances</h4>
            <p class="text-muted mb-0">Students and enrollments with unpaid fee balances.</p>
        </div>
        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
            Back to Reports
        </a>
    </div>

    <div class="card card-body mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted">Search</label>
                <input type="text" class="form-control" placeholder="Name or admission no."
                    wire:model.live.debounce.400ms="search" />
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Course</label>
                <select class="form-select" wire:model.live="courseFilter">
                    <option value="">All courses</option>
                    @foreach ($this->courses as $course)
                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Minimum Balance (KES)</label>
                <input type="number" step="0.01" min="0" class="form-control"
                    wire:model.live.debounce.400ms="minBalance" />
            </div>
            <div class="col-md-3 text-end">
                <button wire:click="exportExcel" class="btn btn-outline-success btn-sm">Excel</button>
                <button wire:click="exportPdf" class="btn btn-outline-danger btn-sm">PDF</button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Outstanding</h6>
                    <h4 class="fw-bold text-danger mb-0">KES {{ number_format($this->summary['total_outstanding'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Affected Students</h6>
                    <h4 class="fw-bold mb-0">{{ $this->summary['affected_students'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Affected Enrollments</h6>
                    <h4 class="fw-bold mb-0">{{ $this->summary['affected_enrollments'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Average Outstanding</h6>
                    <h4 class="fw-bold mb-0">KES {{ number_format($this->summary['average_outstanding'], 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Admission No.</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Contact</th>
                        <th>Oldest Due Date</th>
                        <th>Items</th>
                        <th class="text-end">Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->rows as $row)
                        <tr>
                            <td>{{ $row->admission_number ?? 'N/A' }}</td>
                            <td>{{ trim($row->first_name . ' ' . $row->last_name) }}</td>
                            <td>{{ $row->course_title }}</td>
                            <td>
                                <div class="small">{{ $row->phone ?? 'N/A' }}</div>
                                <div class="small text-muted">{{ $row->email ?? 'N/A' }}</div>
                            </td>
                            <td>{{ $row->oldest_due_date ? \Carbon\Carbon::parse($row->oldest_due_date)->format('d M, Y') : 'N/A' }}</td>
                            <td>{{ $row->items_count }}</td>
                            <td class="text-end fw-semibold text-danger">KES {{ number_format($row->total_outstanding, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">No outstanding balances found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $this->rows->links() }}
        </div>
    </div>
</div>
