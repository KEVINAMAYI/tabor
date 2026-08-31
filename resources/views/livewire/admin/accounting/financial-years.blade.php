<?php

use App\Models\AccountingPeriod;
use App\Models\FinancialYear;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;

new class extends Component {
    public $selectedYearId = null;

    // Financial year form
    public $yearId = null;
    public $year_name = '';
    public $year_starts_at = '';
    public $year_ends_at = '';
    public $year_active = true;

    // Accounting period form
    public $periodId = null;
    public $period_financial_year_id = null;
    public $period_name = '';
    public $period_number = '';
    public $period_starts_at = '';
    public $period_ends_at = '';
    public $period_status = 'open';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-chart-of-accounts') || auth()->user()?->can('manage-accounting-periods'), 403);
    }

    public function with(): array
    {
        $years = FinancialYear::orderByDesc('start_date')->get();

        if (!$this->selectedYearId && $years->count()) {
            $this->selectedYearId = $years->first()->id;
        }

        $periods = $this->selectedYearId
            ? AccountingPeriod::where('financial_year_id', $this->selectedYearId)->orderBy('period_number')->get()
            : collect();

        return [
            'years' => $years,
            'periods' => $periods,
        ];
    }

    public function selectYear($id): void
    {
        $this->selectedYearId = $id;
    }

    public function openYearModal(): void
    {
        $this->resetYearForm();
        $this->dispatch('show-year-modal');
    }

    public function editYear($id): void
    {
        $year = FinancialYear::findOrFail($id);

        $this->yearId = $year->id;
        $this->year_name = $year->name;
        $this->year_starts_at = $year->start_date?->format('Y-m-d');
        $this->year_ends_at = $year->end_date?->format('Y-m-d');
        $this->year_active = $year->active;

        $this->dispatch('show-year-modal');
    }

    public function saveYear(): void
    {
        abort_unless(auth()->user()?->can('manage-accounting-periods'), 403);

        $this->validate([
            'year_name' => 'required|string|max:255',
            'year_starts_at' => 'required|date',
            'year_ends_at' => 'required|date|after_or_equal:year_starts_at',
        ]);

        try {
            $year = FinancialYear::updateOrCreate(
                ['id' => $this->yearId],
                [
                    'name' => $this->year_name,
                    'start_date' => $this->year_starts_at,
                    'end_date' => $this->year_ends_at,
                    'active' => (bool) $this->year_active,
                ],
            );

            $this->selectedYearId = $year->id;
            $this->resetYearForm();
            $this->dispatch('hide-year-modal');

            LivewireAlert::text('Financial year saved successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Financial year save failed: ' . $e->getMessage());
            LivewireAlert::text('Failed to save financial year.')->error()->toast()->position('top-end')->show();
        }
    }

    public function openPeriodModal(): void
    {
        $this->resetPeriodForm();
        $this->dispatch('show-period-modal');
    }

    public function editPeriod($id): void
    {
        $period = AccountingPeriod::findOrFail($id);

        $this->periodId = $period->id;
        $this->period_financial_year_id = $period->financial_year_id;
        $this->period_name = $period->name;
        $this->period_number = $period->period_number;
        $this->period_starts_at = $period->start_date?->format('Y-m-d');
        $this->period_ends_at = $period->end_date?->format('Y-m-d');
        $this->period_status = $period->status;

        $this->dispatch('show-period-modal');
    }

    public function savePeriod(): void
    {
        abort_unless(auth()->user()?->can('manage-accounting-periods'), 403);

        $this->validate([
            'period_name' => 'required|string|max:255',
            'period_number' => 'required|integer|min:1|max:12',
            'period_starts_at' => 'required|date',
            'period_ends_at' => 'required|date|after_or_equal:period_starts_at',
            'period_status' => 'required|in:open,closed,locked',
        ]);

        try {
            AccountingPeriod::updateOrCreate(
                ['id' => $this->periodId],
                [
                    'financial_year_id' => $this->selectedYearId,
                    'name' => $this->period_name,
                    'period_number' => $this->period_number,
                    'start_date' => $this->period_starts_at,
                    'end_date' => $this->period_ends_at,
                    'status' => $this->period_status,
                    'closed_at' => in_array($this->period_status, ['closed', 'locked']) ? now() : null,
                    'closed_by' => in_array($this->period_status, ['closed', 'locked']) ? auth()->id() : null,
                ],
            );

            $this->resetPeriodForm();
            $this->dispatch('hide-period-modal');

            LivewireAlert::text('Accounting period saved successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Accounting period save failed: ' . $e->getMessage());
            LivewireAlert::text('Failed to save accounting period. Check that the period number is unique for this financial year.')->error()->toast()->position('top-end')->show();
        }
    }

    public function setPeriodStatus($id, string $status): void
    {
        abort_unless(auth()->user()?->can('manage-accounting-periods'), 403);

        $period = AccountingPeriod::findOrFail($id);
        $period->update([
            'status' => $status,
            'closed_at' => $status !== 'open' ? now() : null,
            'closed_by' => $status !== 'open' ? auth()->id() : null,
        ]);

        LivewireAlert::text("Period marked as {$status}.")->success()->toast()->position('top-end')->show();
    }

    protected function resetYearForm(): void
    {
        $this->yearId = null;
        $this->year_name = '';
        $this->year_starts_at = '';
        $this->year_ends_at = '';
        $this->year_active = true;
    }

    protected function resetPeriodForm(): void
    {
        $this->periodId = null;
        $this->period_financial_year_id = $this->selectedYearId;
        $this->period_name = '';
        $this->period_number = '';
        $this->period_starts_at = '';
        $this->period_ends_at = '';
        $this->period_status = 'open';
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Financial Years &amp; Accounting Periods</h4>
            <p class="text-muted mb-0">A financial year must have at least one open period before the GL can post to it.</p>
        </div>
        @can('manage-accounting-periods')
            <button class="btn btn-primary btn-sm" wire:click="openYearModal">
                <i class="ti ti-plus me-1"></i> Add Financial Year
            </button>
        @endcan
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card card-body">
                <h6 class="fw-semibold mb-3">Financial Years</h6>
                <ul class="list-unstyled mb-0">
                    @forelse ($years as $year)
                        <li class="d-flex align-items-center justify-content-between px-2 py-2 rounded mb-1 {{ (int) $selectedYearId === (int) $year->id ? 'bg-light' : '' }}"
                            style="cursor:pointer" wire:click="selectYear({{ $year->id }})">
                            <div>
                                <div class="fw-semibold">{{ $year->name }}</div>
                                <div class="text-muted small">
                                    {{ optional($year->start_date)->format('d M Y') }} – {{ optional($year->end_date)->format('d M Y') }}
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if ($year->active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @endif
                                @can('manage-accounting-periods')
                                    <button class="btn btn-sm btn-outline-secondary" wire:click.stop="editYear({{ $year->id }})">
                                        <i class="ti ti-pencil"></i>
                                    </button>
                                @endcan
                            </div>
                        </li>
                    @empty
                        <li class="text-center text-muted py-3">No financial years yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">Accounting Periods</h6>
                    @can('manage-accounting-periods')
                        <button class="btn btn-sm btn-primary" wire:click="openPeriodModal" @disabled(!$selectedYearId)>
                            <i class="ti ti-plus me-1"></i> Add Period
                        </button>
                    @endcan
                </div>
                <div class="table-responsive">
                    <table class="table align-middle text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Dates</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($periods as $period)
                                <tr>
                                    <td>{{ $period->period_number }}</td>
                                    <td>{{ $period->name }}</td>
                                    <td>{{ optional($period->start_date)->format('d M Y') }} – {{ optional($period->end_date)->format('d M Y') }}</td>
                                    <td>
                                        @php
                                            $statusClasses = match ($period->status) {
                                                'open' => 'bg-success-subtle text-success',
                                                'closed' => 'bg-warning-subtle text-warning',
                                                default => 'bg-danger-subtle text-danger',
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClasses }}">{{ ucfirst($period->status) }}</span>
                                    </td>
                                    <td>
                                        @can('manage-accounting-periods')
                                            <button class="btn btn-sm btn-outline-primary" wire:click="editPeriod({{ $period->id }})">Edit</button>
                                            @if ($period->status === 'open')
                                                <button class="btn btn-sm btn-outline-warning" wire:click="setPeriodStatus({{ $period->id }}, 'closed')">Close</button>
                                            @endif
                                            @if ($period->status !== 'locked')
                                                <button class="btn btn-sm btn-outline-danger" wire:click="setPeriodStatus({{ $period->id }}, 'locked')">Lock</button>
                                            @else
                                                <button class="btn btn-sm btn-outline-secondary" wire:click="setPeriodStatus({{ $period->id }}, 'open')">Reopen</button>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No accounting periods for this financial year.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Financial Year Modal --}}
    <div class="modal fade" id="yearModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $yearId ? 'Edit Financial Year' : 'Add Financial Year' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="saveYear">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" wire:model="year_name" placeholder="e.g. FY2026">
                            @error('year_name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" wire:model="year_starts_at">
                                @error('year_starts_at') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" wire:model="year_ends_at">
                                @error('year_ends_at') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="year_active" id="year_active">
                            <label class="form-check-label" for="year_active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">{{ $yearId ? 'Update' : 'Save' }}</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Accounting Period Modal --}}
    <div class="modal fade" id="periodModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $periodId ? 'Edit Period' : 'Add Period' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit.prevent="savePeriod">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Period #</label>
                                <input type="number" min="1" max="12" class="form-control" wire:model="period_number">
                                @error('period_number') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" wire:model="period_name" placeholder="e.g. August 2026">
                                @error('period_name') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" wire:model="period_starts_at">
                                @error('period_starts_at') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" wire:model="period_ends_at">
                                @error('period_ends_at') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" wire:model="period_status">
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                                <option value="locked">Locked</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">{{ $periodId ? 'Update' : 'Save' }}</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@script
    <script>
        window.addEventListener('show-year-modal', () => new bootstrap.Modal(document.getElementById('yearModal')).show());
        window.addEventListener('hide-year-modal', () => bootstrap.Modal.getInstance(document.getElementById('yearModal'))?.hide());
        window.addEventListener('show-period-modal', () => new bootstrap.Modal(document.getElementById('periodModal')).show());
        window.addEventListener('hide-period-modal', () => bootstrap.Modal.getInstance(document.getElementById('periodModal'))?.hide());
    </script>
@endscript
