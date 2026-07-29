<?php

use App\Exports\EnrollmentRetentionExport;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Services\Reports\EnrollmentRetentionReportService;
use Livewire\Volt\Component;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component {
    public $courseFilter = '';
    public $academicYearFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-reports'), 403);
    }

    public function updated($property): void
    {
        if (in_array($property, ['courseFilter', 'academicYearFilter'], true)) {
            $this->dispatch('retention-trend-updated', $this->trimesterChart);
        }
    }

    public function getCoursesProperty()
    {
        return Course::orderBy('title')->get(['id', 'title']);
    }

    public function getAcademicYearsProperty()
    {
        return AcademicYear::orderByDesc('start_date')->get(['id', 'name']);
    }

    protected function filters(): array
    {
        return [
            $this->courseFilter !== '' ? (int) $this->courseFilter : null,
            $this->academicYearFilter !== '' ? (int) $this->academicYearFilter : null,
        ];
    }

    public function getSummaryProperty(): array
    {
        return app(EnrollmentRetentionReportService::class)->summary(...$this->filters());
    }

    public function getTrimesterBreakdownProperty()
    {
        return app(EnrollmentRetentionReportService::class)->trimesterBreakdown(...$this->filters());
    }

    public function getCourseBreakdownProperty()
    {
        [, $academicYearId] = $this->filters();

        return app(EnrollmentRetentionReportService::class)->courseBreakdown($academicYearId);
    }

    public function getTrimesterChartProperty(): array
    {
        $rows = $this->trimesterBreakdown;

        return [
            'labels' => $rows->map(fn ($row) => 'T' . $row->trimester_sequence)->values()->toArray(),
            'data' => $rows->map(fn ($row) => $row->retention_rate)->values()->toArray(),
        ];
    }

    public function exportExcel()
    {
        return Excel::download(
            new EnrollmentRetentionExport(...$this->filters()),
            'enrollment-retention-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        [$courseId, $academicYearId] = $this->filters();

        return redirect()->to(route('reports.retention.export.pdf', [
            'course' => $courseId,
            'academic_year' => $academicYearId,
        ]));
    }
}; ?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Enrollment &amp; Retention</h4>
            <p class="text-muted mb-0">Trimester-by-trimester progression and drop-off.</p>
        </div>
        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
            Back to Reports
        </a>
    </div>

    <div class="card card-body mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted">Course</label>
                <select class="form-select" wire:model.live="courseFilter">
                    <option value="">All courses</option>
                    @foreach ($this->courses as $course)
                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted">Academic Year</label>
                <select class="form-select" wire:model.live="academicYearFilter">
                    <option value="">All years</option>
                    @foreach ($this->academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 text-end">
                <button wire:click="exportExcel" class="btn btn-outline-success btn-sm">Excel</button>
                <button wire:click="exportPdf" class="btn btn-outline-danger btn-sm">PDF</button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-2 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Progressions</h6>
                    <h4 class="fw-bold mb-0">{{ $this->summary['total_progressions'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Retained</h6>
                    <h4 class="fw-bold text-success mb-0">{{ $this->summary['retained'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Retention Rate</h6>
                    <h4 class="fw-bold mb-0">{{ $this->summary['retention_rate'] }}%</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Deferred</h6>
                    <h4 class="fw-bold text-warning mb-0">{{ $this->summary['deferred'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Repeated</h6>
                    <h4 class="fw-bold text-info mb-0">{{ $this->summary['repeated'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Cancelled</h6>
                    <h4 class="fw-bold text-danger mb-0">{{ $this->summary['cancelled'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="card-title fw-semibold">Retention Rate by Trimester</h6>
            <div id="retention-trend-chart" wire:ignore></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header fw-semibold">By Trimester</div>
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Trimester</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Retained</th>
                                <th class="text-end">Deferred</th>
                                <th class="text-end">Repeated</th>
                                <th class="text-end">Cancelled</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->trimesterBreakdown as $row)
                                <tr>
                                    <td>T{{ $row->trimester_sequence }}</td>
                                    <td class="text-end">{{ $row->total }}</td>
                                    <td class="text-end">{{ $row->retained }}</td>
                                    <td class="text-end">{{ $row->deferred }}</td>
                                    <td class="text-end">{{ $row->repeated }}</td>
                                    <td class="text-end">{{ $row->cancelled }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">No progressions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header fw-semibold">By Course</div>
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Course</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Retained</th>
                                <th class="text-end">Retention Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->courseBreakdown as $row)
                                <tr>
                                    <td>{{ $row->course_title }}</td>
                                    <td class="text-end">{{ $row->total }}</td>
                                    <td class="text-end">{{ $row->retained }}</td>
                                    <td class="text-end">{{ $row->retention_rate }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No progressions found.</td>
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
        document.addEventListener('DOMContentLoaded', function () {
            const initialChart = @json($this->trimesterChart);

            const options = {
                series: [{
                    name: 'Retention Rate %',
                    data: initialChart.data,
                }],
                chart: {
                    type: 'line',
                    height: 300,
                    fontFamily: 'inherit',
                    toolbar: {
                        show: false,
                    },
                },
                colors: ['#39b69a'],
                stroke: {
                    curve: 'straight',
                },
                dataLabels: {
                    enabled: true,
                    formatter: (val) => val + '%',
                },
                xaxis: {
                    categories: initialChart.labels,
                },
                yaxis: {
                    max: 100,
                    min: 0,
                },
            };

            const retentionChart = new ApexCharts(document.querySelector('#retention-trend-chart'), options);
            retentionChart.render();

            Livewire.on('retention-trend-updated', (chartData) => {
                retentionChart.updateOptions({
                    series: [{
                        name: 'Retention Rate %',
                        data: chartData.data,
                    }],
                    xaxis: {
                        categories: chartData.labels,
                    },
                });
            });
        });
    </script>
@endpush
