<?php

use App\Models\ChartOfAccount;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;

new class extends Component {
    public $entry_date = '';
    public $description = '';
    public $reference = '';

    // Each row: ['account_id' => null, 'debit' => 0, 'credit' => 0, 'description' => '']
    public array $lines = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('create-journal-entries'), 403);

        $this->entry_date = now()->toDateString();
        $this->lines = [
            ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => ''],
            ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => ''],
        ];
    }

    public function with(): array
    {
        return [
            'accounts' => ChartOfAccount::active()->orderBy('account_code')->get(),
        ];
    }

    public function addLine(): void
    {
        $this->lines[] = ['account_id' => null, 'debit' => null, 'credit' => null, 'description' => ''];
    }

    public function removeLine($index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function getTotalDebitProperty(): float
    {
        return round(array_sum(array_map(fn ($l) => (float) ($l['debit'] ?? 0), $this->lines)), 2);
    }

    public function getTotalCreditProperty(): float
    {
        return round(array_sum(array_map(fn ($l) => (float) ($l['credit'] ?? 0), $this->lines)), 2);
    }

    public function getIsBalancedProperty(): bool
    {
        return $this->totalDebit > 0 && $this->totalDebit === $this->totalCredit;
    }

    protected function rules(): array
    {
        return [
            'entry_date' => 'required|date',
            'description' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ];
    }

    public function saveDraft(): void
    {
        $this->store(post: false);
    }

    public function saveAndPost(): void
    {
        abort_unless(auth()->user()?->can('approve-journal-entries'), 403);

        $this->store(post: true);
    }

    protected function store(bool $post): void
    {
        $this->validate();

        if (!$this->isBalanced) {
            LivewireAlert::text('Debits and credits must balance before saving.')->error()->toast()->position('top-end')->show();
            return;
        }

        $accountCodes = ChartOfAccount::whereIn('id', array_column($this->lines, 'account_id'))
            ->pluck('account_code', 'id');

        $payload = [
            'entry_date' => $this->entry_date,
            'description' => $this->description,
            'reference' => $this->reference,
            'created_by' => auth()->id(),
            'lines' => collect($this->lines)->map(fn ($line) => [
                'account_code' => $accountCodes->get($line['account_id']),
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
                'description' => $line['description'] ?? null,
            ])->all(),
        ];

        try {
            $service = app(JournalPostingService::class);
            $post ? $service->post($payload) : $service->draft($payload);

            LivewireAlert::text('Journal entry saved.')->success()->toast()->position('top-end')->show();

            $this->redirect(route('accounting.journal-entries.index'), navigate: false);
        } catch (\Throwable $e) {
            Log::error('Manual journal entry save failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">New Manual Journal Entry</h4>
            <p class="text-muted mb-0">Save as a draft for another user to approve, or post directly if you hold approval rights.</p>
        </div>
        <a href="{{ route('accounting.journal-entries.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <div class="card card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Entry Date</label>
                <input type="date" class="form-control" wire:model="entry_date">
                @error('entry_date') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Reference</label>
                <input type="text" class="form-control" wire:model="reference">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Description</label>
                <input type="text" class="form-control" wire:model="description">
                @error('description') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 260px">Account</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Line Description</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $index => $line)
                        <tr>
                            <td>
                                <select class="form-select" wire:model="lines.{{ $index }}.account_id">
                                    <option value="">Select account</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_code }} — {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" step="0.01" min="0" class="form-control" wire:model="lines.{{ $index }}.debit"></td>
                            <td><input type="number" step="0.01" min="0" class="form-control" wire:model="lines.{{ $index }}.credit"></td>
                            <td><input type="text" class="form-control" wire:model="lines.{{ $index }}.description"></td>
                            <td>
                                @if (count($lines) > 2)
                                    <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeLine({{ $index }})">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-semibold">
                        <td>Totals</td>
                        <td class="{{ $this->isBalanced ? 'text-success' : 'text-danger' }}">{{ number_format($this->totalDebit, 2) }}</td>
                        <td class="{{ $this->isBalanced ? 'text-success' : 'text-danger' }}">{{ number_format($this->totalCredit, 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <button type="button" class="btn btn-sm btn-outline-secondary mb-3" wire:click="addLine">
            <i class="ti ti-plus me-1"></i> Add Line
        </button>

        @if (!$this->isBalanced)
            <div class="alert alert-warning py-2">Debits and credits must balance (and total more than zero) before saving.</div>
        @endif

        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" wire:click="saveDraft" @disabled(!$this->isBalanced)>
                Save as Draft
            </button>
            @can('approve-journal-entries')
                <button class="btn btn-primary" wire:click="saveAndPost" @disabled(!$this->isBalanced)>
                    Save &amp; Post
                </button>
            @endcan
        </div>
    </div>
</div>
