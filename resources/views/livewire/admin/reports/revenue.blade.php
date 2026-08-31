<?php

use App\Exports\RevenueExport;
use App\Models\Course;
use App\Services\Reports\RevenueReportService;
use Livewire\Volt\Component;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component {
    public $from = '';
    public $to = '';
    public $courseFilter = '';
    public $methodFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-reports'), 403);
    }

    public function updated($property): void
    {
        if (in_array($property, ['from', 'to', 'courseFilter', 'methodFilter'], true)) {
            $this->dispatch('revenue-trend-updated', $this->trend);
        }
    }

    public function getCoursesProperty()
    {
        return Course::orderBy('title')->get(['id', 'title']);
    }

    public function getMethodOptionsProperty()
    {
        return app(RevenueReportService::class)->methodOptions();
    }

    protected function filters(): array
    {
        return [
            $this->from !== '' ? $this->from : null,
            $this->to !== '' ? $this->to : null,
            $this->courseFilter !== '' ? (int) $this->courseFilter : null,
            $this->methodFilter !== '' ? $this->methodFilter : null,
        ];
    }

    public function getSummaryProperty(): array
    {
        return app(RevenueReportService::class)->summary(...$this->filters());
    }

    public function getTrendProperty(): array
    {
        [$from, $to, $courseId, $method] = $this->filters();

        return app(RevenueReportService::class)->monthlyTrend($from, $to, $courseId, $method);
    }

    public function getCourseBreakdownProperty()
    {
        [$from, $to, , $method] = $this->filters();

        return app(RevenueReportService::class)->courseBreakdown($from, $to, $method);
    }

    public function getMethodBreakdownProperty()
    {
        [$from, $to, $courseId] = $this->filters();

        return app(RevenueReportService::class)->methodBreakdown($from, $to, $courseId);
    }

    public function exportExcel()
    {
        return Excel::download(new RevenueExport(...$this->filters()), 'revenue-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportPdf()
    {
        return redirect()->to(route('reports.revenue.export.pdf', [
            'from' => $this->from !== '' ? $this->from : null,
            'to' => $this->to !== '' ? $this->to : null,
            'course' => $this->courseFilter !== '' ? $this->courseFilter : null,
            'method' => $this->methodFilter !== '' ? $this->methodFilter : null,
        ]));
    }
}; ?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Revenue &amp; Collections</h4>
            <p class="text-muted mb-0">Cash collected over time, by course and payment method.</p>
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
                <label class="form-label small text-muted">Method</label>
                <select class="form-select" wire:model.live="methodFilter">
                    <option value="">All methods</option>
                    @foreach ($this->methodOptions as $method)
                        <option value="{{ $method }}">{{ ucfirst($method) }}</option>
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
        <div class="col-lg-4 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Collected</h6>
                    <h4 class="fw-bold text-success mb-0">KES {{ number_format($this->summary['total_collected'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Transactions</h6>
                    <h4 class="fw-bold mb-0">{{ $this->summary['transaction_count'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Average Payment</h6>
                    <h4 class="fw-bold mb-0">KES {{ number_format($this->summary['average_payment'], 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="card-title fw-semibold">Monthly Trend</h6>
            <div id="revenue-trend-chart" wire:ignore></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header fw-semibold">By Course</div>
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Course</th>
                                <th class="text-end">Transactions</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->courseBreakdown as $row)
                                <tr>
                                    <td>{{ $row->course_title }}</td>
                                    <td class="text-end">{{ $row->txn_count }}</td>
                                    <td class="text-end">KES {{ number_format($row->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">No payments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header fw-semibold">By Payment Method</div>
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Method</th>
                                <th class="text-end">Transactions</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->methodBreakdown as $row)
                                <tr>
                                    <td>{{ ucfirst($row->method_name ?? 'N/A') }}</td>
                                    <td class="text-end">{{ $row->txn_count }}</td>
                                    <td class="text-end">KES {{ number_format($row->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">No payments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@script
    <script>
        {
            const initialTrend = @json($this->trend);

            const options = {
                series: [{
                    name: 'Revenue',
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
                colors: ['#22c55e'],
                dataLabels: {
                    enabled: false,
                },
                xaxis: {
                    categories: initialTrend.labels,
                },
                tooltip: {
                    y: {
                        formatter: (val) => 'KES ' + Number(val).toLocaleString(),
                    },
                },
            };

            const revenueTrendChart = new ApexCharts(document.querySelector('#revenue-trend-chart'), options);
            revenueTrendChart.render();

            Livewire.on('revenue-trend-updated', (trend) => {
                revenueTrendChart.updateOptions({
                    series: [{
                        name: 'Revenue',
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
