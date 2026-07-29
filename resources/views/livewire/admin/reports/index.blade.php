<?php

use Livewire\Volt\Component;

new class extends Component {
    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-reports'), 403);
    }
}; ?>

<div class="row g-3">
    <div class="col-12">
        <h4 class="fw-bold mb-1">Reports</h4>
        <p class="text-muted">Analytics and exportable reports across the institute.</p>
    </div>

    <div class="col-12">
        <h6 class="text-uppercase text-muted small fw-semibold mb-2">Financial</h6>
        <div class="row g-3">
            <div class="col-lg-4 col-md-6">
                <a href="{{ route('reports.arrears') }}" class="text-decoration-none">
                    <div class="card shadow-sm h-100 report-card">
                        <div class="card-body d-flex align-items-start">
                            <div class="stat-icon icon-red me-3"><span class="iconify" data-icon="mdi:cash-remove"></span></div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Outstanding Balances</h6>
                                <div class="text-muted small">Arrears by student, course and enrollment</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="{{ route('reports.revenue') }}" class="text-decoration-none">
                    <div class="card shadow-sm h-100 report-card">
                        <div class="card-body d-flex align-items-start">
                            <div class="stat-icon icon-green me-3"><span class="iconify" data-icon="mdi:cash-multiple"></span></div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Revenue &amp; Collections</h6>
                                <div class="text-muted small">Monthly trend, by course and payment method</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="{{ route('reports.reconciliation') }}" class="text-decoration-none">
                    <div class="card shadow-sm h-100 report-card">
                        <div class="card-body d-flex align-items-start">
                            <div class="stat-icon icon-blue me-3"><span class="iconify" data-icon="mdi:sync-alert"></span></div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Reconciliation Health</h6>
                                <div class="text-muted small">Payment allocation drift and balance integrity</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 mt-4">
        <h6 class="text-uppercase text-muted small fw-semibold mb-2">Admissions &amp; Enrollment</h6>
        <div class="row g-3">
            <div class="col-lg-4 col-md-6">
                <a href="{{ route('reports.applications-funnel') }}" class="text-decoration-none">
                    <div class="card shadow-sm h-100 report-card">
                        <div class="card-body d-flex align-items-start">
                            <div class="stat-icon icon-blue me-3"><span class="iconify" data-icon="mdi:filter-variant"></span></div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Course-Application Funnel</h6>
                                <div class="text-muted small">Submission to enrollment conversion and turnaround</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="{{ route('reports.retention') }}" class="text-decoration-none">
                    <div class="card shadow-sm h-100 report-card">
                        <div class="card-body d-flex align-items-start">
                            <div class="stat-icon icon-green me-3"><span class="iconify" data-icon="mdi:account-arrow-right-outline"></span></div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Enrollment &amp; Retention</h6>
                                <div class="text-muted small">Trimester-by-trimester progression and drop-off</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 mt-4">
        <h6 class="text-uppercase text-muted small fw-semibold mb-2">Coming soon</h6>
        <div class="row g-3">
            @foreach ([
                ['icon' => 'mdi:school-outline', 'title' => 'Academic Performance', 'desc' => 'Grades, attendance and grading turnaround'],
            ] as $card)
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 border-dashed opacity-75">
                        <div class="card-body d-flex align-items-start">
                            <div class="stat-icon icon-blue me-3"><span class="iconify" data-icon="{{ $card['icon'] }}"></span></div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">{{ $card['title'] }}</h6>
                                <div class="text-muted small">{{ $card['desc'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('styles')
    <style>
        .report-card {
            transition: all .15s ease-in-out;
        }

        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, .08) !important;
        }

        .stat-icon {
            font-size: 26px;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .icon-blue {
            background: rgba(59, 130, 246, .1);
            color: #3b82f6;
        }

        .icon-green {
            background: rgba(34, 197, 94, .1);
            color: #22c55e;
        }

        .icon-red {
            background: rgba(239, 68, 68, .1);
            color: #ef4444;
        }

        .border-dashed {
            border: 2px dashed #dee2e6;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
@endpush
