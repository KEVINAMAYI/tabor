<?php

use App\Models\FinancialYear;
use App\Services\Accounting\BudgetReportService;
use Livewire\Volt\Component;

new class extends Component {
    public $selectedYearId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-budgets'), 403);

        $this->selectedYearId = FinancialYear::orderByDesc('start_date')->value('id');
    }

    public function with(): array
    {
        $years = FinancialYear::orderByDesc('start_date')->get();

        $result = $this->selectedYearId
            ? app(BudgetReportService::class)->generate($this->selectedYearId)
            : null;

        return [
            'years' => $years,
            'rows' => $result['rows'] ?? collect(),
            'totals' => $result['totals'] ?? null,
        ];
    }

    public function exportPdf()
    {
        return redirect()->to(route('accounting.budget.report.export.pdf', [
            'financial_year_id' => $this->selectedYearId,
        ]));
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Budget vs Actual</h4>
            <p class="text-muted mb-0">Actual is computed live from posted journal entries against each vote head's expense account.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.budget.manage') }}" class="btn btn-outline-secondary btn-sm">Manage Budgets</a>
            <button class="btn btn-outline-danger btn-sm" wire:click="exportPdf" @disabled(!$selectedYearId)>PDF</button>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <select class="form-select" wire:model.live="selectedYearId">
                @foreach ($years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($rows->contains('over_budget', true))
        <div class="alert alert-warning">
            <strong>{{ $rows->where('over_budget', true)->count() }}</strong> vote head(s) are over budget for this financial year.
        </div>
    @endif

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Vote Head</th>
                        <th>Sub Vote Head</th>
                        <th class="text-end">Budgeted</th>
                        <th class="text-end">Actual</th>
                        <th class="text-end">Variance</th>
                        <th>% Used</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="{{ $row->over_budget ? 'table-danger' : '' }}">
                            <td>{{ $row->vote_head }}</td>
                            <td>{{ $row->sub_vote_head ?? '— (whole vote head)' }}</td>
                            <td class="text-end">{{ number_format($row->budgeted_amount, 2) }}</td>
                            <td class="text-end">{{ number_format($row->actual_amount, 2) }}</td>
                            <td class="text-end fw-semibold {{ $row->variance < 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($row->variance, 2) }}
                            </td>
                            <td>
                                @if ($row->percent_used !== null)
                                    <span class="badge {{ $row->over_budget ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">
                                        {{ $row->percent_used }}%
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No budget lines for this financial year yet.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($totals)
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="2">Totals</td>
                            <td class="text-end">{{ number_format($totals->total_budgeted, 2) }}</td>
                            <td class="text-end">{{ number_format($totals->total_actual, 2) }}</td>
                            <td class="text-end">{{ number_format($totals->total_budgeted - $totals->total_actual, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
