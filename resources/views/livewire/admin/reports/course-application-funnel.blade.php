<?php

use App\Exports\CourseApplicationFunnelExport;
use App\Models\Course;
use App\Models\User;
use App\Services\Reports\CourseApplicationFunnelReportService;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component {
    use WithPagination;

    public $from = '';
    public $to = '';
    public $courseFilter = '';
    public $statusFilter = '';
    public $reviewerFilter = '';
    public $perPage = 15;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-reports'), 403);
    }

    public function updated($property): void
    {
        if (in_array($property, ['from', 'to', 'courseFilter', 'statusFilter', 'reviewerFilter'], true)) {
            $this->resetPage();
            $this->dispatch('funnel-trend-updated', $this->trend);
        }
    }

    public function getCoursesProperty()
    {
        return Course::orderBy('title')->get(['id', 'title']);
    }

    public function getReviewersProperty()
    {
        return User::whereIn('id', function ($q) {
            $q->select('reviewed_by')
                ->from('course_applications')
                ->whereNotNull('reviewed_by')
                ->distinct();
        })->orderBy('name')->get(['id', 'name']);
    }

    protected function filters(): array
    {
        return [
            $this->courseFilter !== '' ? (int) $this->courseFilter : null,
            $this->from !== '' ? $this->from : null,
            $this->to !== '' ? $this->to : null,
            $this->statusFilter !== '' ? $this->statusFilter : null,
            $this->reviewerFilter !== '' ? (int) $this->reviewerFilter : null,
        ];
    }

    public function getSummaryProperty(): array
    {
        [$courseId, $from, $to, , $reviewerId] = $this->filters();

        return app(CourseApplicationFunnelReportService::class)->summary($courseId, $from, $to, $reviewerId);
    }

    public function getTrendProperty(): array
    {
        return app(CourseApplicationFunnelReportService::class)->monthlyTrend(...$this->filters());
    }

    public function getCourseBreakdownProperty()
    {
        [, $from, $to, $status] = $this->filters();

        return app(CourseApplicationFunnelReportService::class)->courseBreakdown($from, $to, $status);
    }

    public function getReviewerBreakdownProperty()
    {
        [$courseId, $from, $to] = $this->filters();

        return app(CourseApplicationFunnelReportService::class)->reviewerBreakdown($courseId, $from, $to);
    }

    public function getRowsProperty()
    {
        return app(CourseApplicationFunnelReportService::class)->query(...$this->filters())
            ->with(['course', 'reviewer'])
            ->paginate($this->perPage);
    }

    public function exportExcel()
    {
        return Excel::download(
            new CourseApplicationFunnelExport(...$this->filters()),
            'course-application-funnel-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        [$courseId, $from, $to, $status, $reviewerId] = $this->filters();

        return redirect()->to(route('reports.applications-funnel.export.pdf', [
            'course' => $courseId,
            'from' => $from,
            'to' => $to,
            'status' => $status,
            'reviewer' => $reviewerId,
        ]));
    }
}; ?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Course-Application Funnel</h4>
            <p class="text-muted mb-0">Submissions, conversion rate and reviewer turnaround.</p>
        </div>
        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
            Back to Reports
        </a>
    </div>

    <div class="card card-body mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label small text-muted">From</label>
                <input type="date" class="form-control" wire:model.live="from" />
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">To</label>
                <input type="date" class="form-control" wire:model.live="to" />
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Course</label>
                <select class="form-select" wire:model.live="courseFilter">
                    <option value="">All courses</option>
                    @foreach ($this->courses as $course)
                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Status</label>
                <select class="form-select" wire:model.live="statusFilter">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Reviewer</label>
                <select class="form-select" wire:model.live="reviewerFilter">
                    <option value="">All reviewers</option>
                    @foreach ($this->reviewers as $reviewer)
                        <option value="{{ $reviewer->id }}">{{ $reviewer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 text-end">
                <button wire:click="exportExcel" class="btn btn-outline-success btn-sm">Excel</button>
                <button wire:click="exportPdf" class="btn btn-outline-danger btn-sm">PDF</button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-2 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Submitted</h6>
                    <h4 class="fw-bold mb-0">{{ $this->summary['submitted'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Approved</h6>
                    <h4 class="fw-bold text-success mb-0">{{ $this->summary['approved'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Rejected</h6>
                    <h4 class="fw-bold text-danger mb-0">{{ $this->summary['rejected'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Pending</h6>
                    <h4 class="fw-bold text-warning mb-0">{{ $this->summary['pending'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Conversion Rate</h6>
                    <h4 class="fw-bold mb-0">{{ $this->summary['conversion_rate'] }}%</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Avg. Turnaround</h6>
                    <h4 class="fw-bold mb-0">{{ $this->summary['avg_turnaround_hours'] }}h</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="card-title fw-semibold">Monthly Submissions</h6>
            <div id="funnel-trend-chart" wire:ignore></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header fw-semibold">By Course</div>
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Course</th>
                                <th class="text-end">Submitted</th>
                                <th class="text-end">Approved</th>
                                <th class="text-end">Rejected</th>
                                <th class="text-end">Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->courseBreakdown as $row)
                                <tr>
                                    <td>{{ $row->course_title }}</td>
                                    <td class="text-end">{{ $row->submitted }}</td>
                                    <td class="text-end">{{ $row->approved }}</td>
                                    <td class="text-end">{{ $row->rejected }}</td>
                                    <td class="text-end">{{ $row->pending }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No applications found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header fw-semibold">By Reviewer</div>
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Reviewer</th>
                                <th class="text-end">Reviewed</th>
                                <th class="text-end">Approved</th>
                                <th class="text-end">Rejected</th>
                                <th class="text-end">Avg. Hrs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->reviewerBreakdown as $row)
                                <tr>
                                    <td>{{ $row->reviewer_name }}</td>
                                    <td class="text-end">{{ $row->reviewed_count }}</td>
                                    <td class="text-end">{{ $row->approved_count }}</td>
                                    <td class="text-end">{{ $row->rejected_count }}</td>
                                    <td class="text-end">{{ $row->avg_hours ? number_format($row->avg_hours, 1) : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No reviewed applications found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Applicant</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Reviewed</th>
                        <th>Reviewer</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->rows as $application)
                        <tr>
                            <td>{{ trim($application->first_name . ' ' . $application->last_name) }}</td>
                            <td>{{ $application->course->title ?? 'N/A' }}</td>
                            <td>
                                @if ($application->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif ($application->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td>{{ $application->created_at->format('d M Y') }}</td>
                            <td>{{ $application->reviewed_at?->format('d M Y') ?? 'N/A' }}</td>
                            <td>{{ $application->reviewer->name ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No applications found.</td>
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

@script
    <script>
        {
            const initialTrend = @json($this->trend);

            const options = {
                series: [{
                    name: 'Submissions',
                    data: initialTrend.data,
                }],
                chart: {
                    type: 'bar',
                    height: 300,
                    fontFamily: 'inherit',
                    toolbar: {
                        show: false,
                    },
                },
                colors: ['#7367f0'],
                dataLabels: {
                    enabled: false,
                },
                xaxis: {
                    categories: initialTrend.labels,
                },
            };

            const funnelTrendChart = new ApexCharts(document.querySelector('#funnel-trend-chart'), options);
            funnelTrendChart.render();

            Livewire.on('funnel-trend-updated', (trend) => {
                funnelTrendChart.updateOptions({
                    series: [{
                        name: 'Submissions',
                        data: trend.data,
                    }],
                    xaxis: {
                        categories: trend.labels,
                    },
                });
            });
        }
    </script>
@endscript
