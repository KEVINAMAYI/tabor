<?php

use App\Models\AccountingPeriod;
use App\Services\Accounting\TrialBalanceReportService;
use Livewire\Volt\Component;

new class extends Component {
    public $accountingPeriodId = '';
    public $asOfDate = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-trial-balance'), 403);

        $this->asOfDate = now()->toDateString();
    }

    public function with(): array
    {
        $result = app(TrialBalanceReportService::class)->generate(
            $this->accountingPeriodId ?: null,
            $this->accountingPeriodId ? null : $this->asOfDate
        );

        return [
            'rows' => $result['rows'],
            'totals' => $result['totals'],
            'periods' => AccountingPeriod::orderByDesc('start_date')->get(),
        ];
    }

    public function exportPdf()
    {
        return redirect()->to(route('accounting.trial-balance.export.pdf', [
            'accounting_period_id' => $this->accountingPeriodId,
            'as_of' => $this->asOfDate,
        ]));
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Trial Balance</h4>
            <p class="text-muted mb-0">Every posted journal entry, grouped by account.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.journal-entries.index') }}" class="btn btn-outline-secondary btn-sm">Journal Entries</a>
            <button class="btn btn-outline-danger btn-sm" wire:click="exportPdf">PDF</button>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <label class="form-label small text-muted">Accounting Period</label>
            <select class="form-select" wire:model.live="accountingPeriodId">
                <option value="">Cumulative as of a date</option>
                @foreach ($periods as $period)
                    <option value="{{ $period->id }}">{{ $period->name }}</option>
                @endforeach
            </select>
        </div>
        @if (!$accountingPeriodId)
            <div class="col-md-4">
                <label class="form-label small text-muted">As Of Date</label>
                <input type="date" class="form-control" wire:model.live="asOfDate">
            </div>
        @endif
    </div>

    @unless ($totals->balanced)
        <div class="alert alert-danger">
            <strong>Warning:</strong> Total debits ({{ number_format($totals->total_debit, 2) }}) do not equal
            total credits ({{ number_format($totals->total_credit, 2) }}). This indicates a bug in the posting
            engine, not a data-entry problem — investigate before relying on this report.
        </div>
    @endunless

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Account</th>
                        <th>Type</th>
                        <th class="text-end">Total Debit</th>
                        <th class="text-end">Total Credit</th>
                        <th class="text-end">Closing Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row->account_code }}</td>
                            <td>{{ $row->name }}</td>
                            <td class="text-capitalize small text-muted">{{ $row->account_type }}</td>
                            <td class="text-end">{{ number_format($row->total_debit, 2) }}</td>
                            <td class="text-end">{{ number_format($row->total_credit, 2) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($row->closing_balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No posted journal entries in this range yet.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="3">Totals</td>
                        <td class="text-end">{{ number_format($totals->total_debit, 2) }}</td>
                        <td class="text-end">{{ number_format($totals->total_credit, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
