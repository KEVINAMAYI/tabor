<?php

use App\Exports\ReconciliationHealthExport;
use App\Services\Reports\ReconciliationReportService;
use Livewire\Volt\Component;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component {
    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-reports'), 403);
    }

    public function getSummaryProperty(): array
    {
        return app(ReconciliationReportService::class)->summary();
    }

    public function getStudentBreakdownProperty()
    {
        return app(ReconciliationReportService::class)->studentBreakdown();
    }

    public function exportExcel()
    {
        return Excel::download(
            new ReconciliationHealthExport(),
            'reconciliation-health-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        return redirect()->to(route('reports.reconciliation.export.pdf'));
    }
}; ?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Reconciliation Health</h4>
            <p class="text-muted mb-0">Payment allocation drift and balance integrity across all students.</p>
        </div>
        <div>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">Back to Reports</a>
            <button wire:click="exportExcel" class="btn btn-outline-success btn-sm">Excel</button>
            <button wire:click="exportPdf" class="btn btn-outline-danger btn-sm">PDF</button>
        </div>
    </div>

    <div class="alert alert-info small">
        This mirrors the <code>finance:reconcile --dry-run</code> command — it detects drift, it does not fix it.
        Use the console command with <code>--fix</code> to repair mismatches for a specific student.
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Students Checked</h6>
                    <h4 class="fw-bold mb-0">{{ $this->summary['students_checked'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Students With Mismatches</h6>
                    <h4 class="fw-bold text-danger mb-0">{{ $this->summary['students_with_mismatches'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Mismatches</h6>
                    <h4 class="fw-bold mb-0">{{ $this->summary['total_mismatches'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header fw-semibold">By Mismatch Type</div>
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Type</th>
                        <th class="text-end">Count</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->summary['by_type'] as $type => $count)
                        <tr>
                            <td>{{ $type }}</td>
                            <td class="text-end">{{ $count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted py-3">No mismatches found. Everything reconciles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Admission No.</th>
                        <th>Student</th>
                        <th>Mismatch Count</th>
                        <th>Types</th>
                        <th class="text-end">Total Drift</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->studentBreakdown as $row)
                        <tr>
                            <td>{{ $row->admission_number }}</td>
                            <td>{{ $row->student_name }}</td>
                            <td>{{ $row->mismatch_count }}</td>
                            <td class="small text-muted">{{ $row->types }}</td>
                            <td class="text-end fw-semibold {{ $row->total_drift < 0 ? 'text-danger' : 'text-success' }}">
                                KES {{ number_format($row->total_drift, 2) }}
                            </td>
                            <td>
                                <a href="{{ route('payments.index') }}" class="btn btn-sm btn-outline-primary">
                                    Investigate
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No affected students found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
