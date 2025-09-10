<?php

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\Lecturer;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new class extends Component {
    public $students;
    public $courses;
    public $lecturers;
    public $statusData;
    public $courseData;
    public $monthlyEnrollments;
    public $months;
    public $total_revenue;
    public $recentEnrollments = [];
    public $monthlyPayments = [];
    public $recentPayments = [];

    public function mount()
    {
        $this->students = Enrollment::with('student')->where('enrollments.status', 'approved')->count();

        $this->courses = Course::count();
        $this->lecturers = Lecturer::count();
        $this->total_revenue = Payment::where('payment_method', '!=', 'discount')->sum('amount');

        // Group by status
        $this->statusData = Enrollment::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->toArray();

        // Group by course
        $this->courseData = Enrollment::join('courses', 'enrollments.course_id', '=', 'courses.id')->selectRaw('courses.title as course_name, COUNT(*) as total')->groupBy('courses.title')->pluck('total', 'course_name')->toArray();

        // Monthly enrollments (e.g., Jan–Dec)
        $monthlyEnrollments = Student::select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month');

        // Fill missing months with 0
        $this->monthlyEnrollments = collect(range(1, 12))
            ->map(function ($month) use ($monthlyEnrollments) {
                return $monthlyEnrollments->get($month, 0);
            })
            ->toArray();

        // Generate month labels (Jan, Feb, etc.)
        $this->months = collect(range(1, 12))
            ->map(function ($m) {
                return \Carbon\Carbon::create()->month($m)->format('M');
            })
            ->toArray();

        $this->recentEnrollments = Enrollment::with(['student', 'course'])
            ->latest()
            ->take(5) // Or any number
            ->get();

        $months = collect(range(0, 11))
            ->map(function ($i) {
                return now()->subMonths($i)->startOfMonth();
            })
            ->reverse();

        $this->monthlyPayments = $months->map(function ($month) {
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $payments = Payment::whereBetween('paid_at', [$start, $end])
                ->selectRaw(
                    "
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END) as failed
            ",
                )
                ->first();

            $total = $payments->completed + $payments->pending + $payments->failed;

            return [
                'month' => $month->format('F'),
                'completed' => $payments->completed,
                'pending' => $payments->pending,
                'failed' => $payments->failed,
                'total' => $total ?: 1, // prevent division by zero
            ];
        });

        $this->recentPayments = Payment::with(['enrollment.student'])
            ->latest()
            ->take(5)
            ->get();
    }
}; ?>

@push('styles')
    <style>
        .department-overview-title {
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }

        .department-overview-card,
        .recent-activity-card {
            border-radius: 12px;
        }

        .department-overview-title,
        .map-title,
        .quick-actions-title {
            font-weight: bold;
            font-size: 18px;
            color: #000;
            /* black */
        }

        .recent-activity-title,
        .map-title,
        .quick-actions-title {
            font-weight: bold;
            font-size: 14px;
            color: #000;
            /* black */
        }

        .card-action {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            color: #000;
            background: #fff;
            border: 2px dotted #333;
            /* dotted border on small cards */
            border-radius: 8px;
            padding: 15px;
            transition: all 0.2s ease-in-out;
            font-weight: 500;
            height: 100%;
            text-align: center;
        }

        .card-action:hover {
            background: #f5f5f5;
            transform: scale(1.05);
        }

        .recent-activity-card {
            border-radius: 12px;
        }

        .activity-item {
            background: linear-gradient(135deg, #f9fbff, #eef4ff);
            border-radius: 12px;
            padding: 12px;
            transition: 0.2s;
            box-shadow: 0 2px 6px rgba(74, 108, 247, 0.08);
        }

        .activity-item:hover {
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            box-shadow: 0 4px 10px rgba(74, 108, 247, 0.15);
        }

        .icon-wrap {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dbe4ff, #edf2ff);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4a6cf7;
            font-size: 20px;
            box-shadow: 0 2px 5px rgba(74, 108, 247, 0.2);
        }

        .status {
            font-weight: 500;
            text-transform: lowercase;
        }

        .stat-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
        }

        .stat-text {
            text-align: left;
        }

        .stat-icon {
            font-size: 36px;
            padding: 12px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-blue {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .icon-green {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .icon-red {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .icon-orange {
            background: rgba(251, 146, 60, 0.1);
            color: #fb923c;
        }
    </style>
@endpush

<div class="row g-3">

    <!-- Total Students -->
    <div class="col-lg-3 col-6">
        <div class="card shadow-sm h-100">
            <div class="stat-card">
                <div class="stat-text">
                    <h6 class="text-muted mb-1">Total Students</h6>
                    <h3 class="fw-bold text-dark">{{ $students }}</h3>
                    <small class="text-muted">Enrolled & Approved</small>
                </div>
                <div class="stat-icon icon-blue">
                    <span class="iconify" data-icon="mdi:school-outline"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Courses -->
    <div class="col-lg-3 col-6">
        <div class="card shadow-sm h-100">
            <div class="stat-card">
                <div class="stat-text">
                    <h6 class="text-muted mb-1">Total Courses</h6>
                    <h3 class="fw-bold text-success">{{ $courses }}</h3>
                    <small class="text-muted">Active This Semester</small>
                </div>
                <div class="stat-icon icon-green">
                    <span class="iconify" data-icon="mdi:book-open-page-variant-outline"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Lecturers -->
    <div class="col-lg-3 col-6">
        <div class="card shadow-sm h-100">
            <div class="stat-card">
                <div class="stat-text">
                    <h6 class="text-muted mb-1">Total Lecturers</h6>
                    <h3 class="fw-bold text-danger">{{ $lecturers }}</h3>
                    <small class="text-muted">Full-time & Part-time</small>
                </div>
                <div class="stat-icon icon-red">
                    <span class="iconify" data-icon="mdi:account-tie-outline"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Revenue -->
    <div class="col-lg-3 col-6">
        <div class="card shadow-sm h-100">
            <div class="stat-card">
                <div class="stat-text">
                    <h6 class="text-muted mb-1">Total Revenue</h6>
                    <h4 class="text-small text-warning">KES {{ number_format($total_revenue, 2) }}</h4>
                    <small class="text-muted">Fees & Other Payments</small>
                </div>
                <div class="stat-icon icon-orange">
                    <span class="iconify" data-icon="mdi:cash-multiple"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Map -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Monthly Ennrollments</h4>
                <div id="chart-line-basic"></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header quick-actions-title">Quick Actions</div>
            <div class="card-body">
                <div class="row g-3">

                    <!-- Add Student -->
                    <div class="col-6">
                        <a href="{{ route('students.index') }}" class="card-action">
                            <span class="iconify mb-2" data-icon="mdi:account-plus-outline"
                                style="font-size: 28px;"></span>
                            <span>Add Student</span>
                        </a>
                    </div>

                    <!-- Add Course -->
                    <div class="col-6">
                        <a href="{{ route('courses.index') }}" class="card-action">
                            <span class="iconify mb-2" data-icon="mdi:book-plus-outline"
                                style="font-size: 28px;"></span>
                            <span>Add Course</span>
                        </a>
                    </div>

                    <!-- Add Lecturer -->
                    <div class="col-6">
                        <a href="{{ route('lecturers.index') }}" class="card-action">
                            <span class="iconify mb-2" data-icon="mdi:account-tie-outline"
                                style="font-size: 28px;"></span>
                            <span>Add Lecturer</span>
                        </a>
                    </div>

                    <!-- Add Payment -->
                    <div class="col-6">
                        <a href="{{ route('payments.index') }}" class="card-action">
                            <span class="iconify mb-2" data-icon="mdi:cash-plus" style="font-size: 28px;"></span>
                            <span>Add Payment</span>
                        </a>
                    </div>

                    <!-- Add User -->
                    <div class="col-6">
                        <a href="{{ route('roles.users') }}" class="card-action">
                            <span class="iconify mb-2" data-icon="mdi:account-plus-outline"
                                style="font-size: 28px;"></span>
                            <span>Add User</span>
                        </a>
                    </div>

                    <!-- Add Role -->
                    <div class="col-6">
                        <a href="{{ route('roles.index') }}" class="card-action">
                            <span class="iconify mb-2" data-icon="mdi:shield-account-outline"
                                style="font-size: 28px;"></span>
                            <span>Add Role</span>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Start Simple Pie Chart -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Enrollments By Status</h4>
                <div id="chart-pie-simple"></div>
            </div>
        </div>
    </div>
    <!-- Start Donut Pie Chart -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Enrollments By Courses</h4>
                <div id="chart-pie-donut"></div>
            </div>
        </div>
    </div>
    <!-- End Simple Pie Chart -->

    <!-- Department Overview -->
    <div class="col-lg-8 d-flex">
        <div class="card shadow-sm flex-fill">
            <div class="card-header fw-semibold">Monthly Revenue Breakdown</div>
            <div class="card-body">
                @foreach ($monthlyPayments as $payment)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small fw-semibold">
                            <span>{{ $payment['month'] }}</span>
                            <span>
                                <span class="text-success">KES{{ number_format($payment['completed'], 2) }}
                                    Completed</span> /
                                <span class="text-warning">KES{{ number_format($payment['pending'], 2) }}
                                    Pending</span> /
                                <span class="text-danger">KES{{ number_format($payment['failed'], 2) }} Failed</span>
                            </span>
                        </div>

                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success"
                                style="width: {{ round(($payment['completed'] / $payment['total']) * 100, 2) }}%">
                            </div>
                            <div class="progress-bar bg-warning"
                                style="width: {{ round(($payment['pending'] / $payment['total']) * 100, 2) }}%">
                            </div>
                            <div class="progress-bar bg-danger"
                                style="width: {{ round(($payment['failed'] / $payment['total']) * 100, 2) }}%">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>


    <!-- Recent Activity -->
    <div class="col-lg-4 d-flex">
        <div class="card shadow-sm recent-activity-card flex-fill">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold recent-activity-title">Recent Enrollments</span>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    @forelse ($recentEnrollments as $enrollment)
                        <li class="activity-item d-flex align-items-center mb-3">
                            <div class="icon-wrap">
                                <span class="iconify" data-icon="mdi:clock-outline"></span>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-semibold">
                                    {{ $enrollment->student->name }}
                                </div>
                                <div class="text-muted small">
                                    Enrolled in {{ $enrollment->course->title }}
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold">
                                    {{ $enrollment->created_at->format('h:i A') }}
                                </div>
                                <div class="status small text-primary">New Enrollment</div>
                            </div>
                        </li>
                    @empty
                        <li class="text-muted text-center">No recent enrollments</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>


    <!-- Recent Payments -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Recent Payments</span>
                <a href="{{ route('payments.index') }}" class="btn btn-sm btn-outline-primary">
                    View More
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Method</th>
                            <th>Paid On</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentPayments as $payment)
                            <tr>
                                <td>{{ $payment->enrollment?->student->first_name ?? 'N/A' }}</td>

                                <td class="fw-semibold text-primary">
                                    KES {{ number_format($payment->amount, 2) }}
                                </td>

                                <td>
                                    @if ($payment->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($payment->status === 'pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @elseif($payment->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-secondary">Unknown</span>
                                    @endif
                                </td>

                                <td>{{ ucfirst($payment->payment_method) }}</td>

                                <td>{{ \Carbon\Carbon::parse($payment->paid_at)->format('d M, Y') }}</td>
                            </tr>
                        @endforeach

                        @if ($recentPayments->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">No recent payments found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>


</div>



@push('scripts')
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // Data from Livewire
            const statusData = @json(array_values($statusData));
            const statusLabels = @json(array_map(fn($label) => ucfirst($label), array_keys($statusData)));

            const courseData = @json(array_values($courseData));
            const courseLabels = @json(array_map(fn($label) => ucfirst($label), array_keys($courseData)));

            // Match specific colors to statuses
            const statusColorsMap = {
                'Approved': '#39b69a', // green
                'Pending': '#ffae1f', // yellow
                'Rejected': '#fa896b' // red
            };

            const statusColors = statusLabels.map(status => statusColorsMap[status] || '#ccc');

            // Simple Pie Chart (Status)
            var options_simple = {
                series: statusData,
                chart: {
                    fontFamily: "inherit",
                    width: 380,
                    type: "pie",
                },
                colors: statusColors,
                labels: statusLabels,
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 200,
                        },
                        legend: {
                            position: "bottom",
                        },
                    },
                }],
                legend: {
                    labels: {
                        colors: "#a1aab2",
                        useSeriesColors: false,
                        formatter: function(label) {
                            return label.charAt(0).toUpperCase() + label.slice(1);
                        }
                    }
                },
            };

            var chart_pie_simple = new ApexCharts(
                document.querySelector("#chart-pie-simple"),
                options_simple
            );
            chart_pie_simple.render();


            // Donut Pie Chart (Courses)
            var options_donut = {
                series: courseData,
                chart: {
                    fontFamily: "inherit",
                    type: "donut",
                    width: 430, // Match width with pie chart
                },
                colors: [
                    "#7367f0", "#00cfe8", "#ff9f43", "#ea5455", "#28c76f",
                    "#9f44d3", "#736f93", "#1e1e2f", "#ff5b5c", "#39b69a"
                ], // You can expand this list
                labels: courseLabels,
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 200,
                        },
                        legend: {
                            position: "bottom",
                        },
                    },
                }],
                legend: {
                    labels: {
                        colors: "#a1aab2",
                        useSeriesColors: false,
                        formatter: function(label) {
                            return label.charAt(0).toUpperCase() + label.slice(1);
                        }
                    }
                },
            };

            var chart_pie_donut = new ApexCharts(
                document.querySelector("#chart-pie-donut"),
                options_donut
            );
            chart_pie_donut.render();


            const enrollmentSeries = @json($monthlyEnrollments);
            const enrollmentCategories = @json($months);

            var options_line = {
                series: [{
                    name: "Enrollments",
                    data: enrollmentSeries,
                }, ],
                chart: {
                    height: 350,
                    type: "line",
                    fontFamily: "inherit",
                    zoom: {
                        enabled: false,
                    },
                    offsetX: -10,
                    toolbar: {
                        show: false,
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                colors: ["var(--bs-primary)"],
                stroke: {
                    curve: "straight",
                },
                grid: {
                    row: {
                        colors: ["transparent"],
                        opacity: 0.5,
                    },
                    padding: {
                        top: 0,
                        right: 0,
                        bottom: 0,
                        left: 0,
                    },
                    borderColor: "transparent",
                },
                xaxis: {
                    categories: enrollmentCategories,
                    labels: {
                        style: {
                            colors: "#a1aab2",
                        },
                    },
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: "#a1aab2",
                        },
                    },
                },
                tooltip: {
                    theme: "dark",
                },
            };

            var chart_line_basic = new ApexCharts(
                document.querySelector("#chart-line-basic"),
                options_line
            );
            chart_line_basic.render();


        });
    </script>
@endpush
