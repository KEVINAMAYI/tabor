<?php

use App\Models\JournalEntry;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $statusFilter = '';
    public $sourceFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view-journal-entries'), 403);
    }

    public function with(): array
    {
        return [
            'entries' => JournalEntry::query()
                ->with(['lines.account', 'period', 'createdBy'])
                ->when(filled($this->statusFilter), fn ($q) => $q->where('status', $this->statusFilter))
                ->when(filled($this->sourceFilter), fn ($q) => $q->where('source_type', $this->sourceFilter))
                ->orderByDesc('entry_date')
                ->orderByDesc('id')
                ->paginate(20),
        ];
    }

    public function approveAndPost($id): void
    {
        abort_unless(auth()->user()?->can('approve-journal-entries'), 403);

        try {
            app(JournalPostingService::class)->approveAndPost(JournalEntry::findOrFail($id), auth()->id());

            LivewireAlert::text('Journal entry posted.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Journal entry approve/post failed: ' . $e->getMessage());
            LivewireAlert::text($e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }
};
?>

<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Journal Entries</h4>
            <p class="text-muted mb-0">Automatic postings from Student Finance, plus manual entries.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.trial-balance') }}" class="btn btn-outline-secondary btn-sm">Trial Balance</a>
            @can('create-journal-entries')
                <a href="{{ route('accounting.journal-entries.create') }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i> New Manual Entry
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="posted">Posted</option>
                <option value="reversed">Reversed</option>
            </select>
        </div>
        <div class="col-md-4">
            <select class="form-select" wire:model.live="sourceFilter">
                <option value="">All Sources</option>
                <option value="App\Models\Payment">Payment</option>
                <option value="App\Models\PaymentRefund">Refund</option>
                <option value="App\Models\StudentFeeItem">Fee Charge / Waiver</option>
            </select>
        </div>
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table align-middle text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Source</th>
                        <th>Period</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->entry_date->format('d M Y') }}</td>
                            <td>{{ $entry->description }}</td>
                            <td class="small text-muted">
                                {{ $entry->source_type ? class_basename($entry->source_type) . ' #' . $entry->source_id : 'Manual' }}
                            </td>
                            <td class="small">{{ $entry->period->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format($entry->lines->sum('debit'), 2) }}</td>
                            <td class="text-end">{{ number_format($entry->lines->sum('credit'), 2) }}</td>
                            <td>
                                @php
                                    $statusClasses = match ($entry->status) {
                                        'posted' => 'bg-success-subtle text-success',
                                        'draft' => 'bg-warning-subtle text-warning',
                                        default => 'bg-danger-subtle text-danger',
                                    };
                                @endphp
                                <span class="badge {{ $statusClasses }}">{{ ucfirst($entry->status) }}</span>
                            </td>
                            <td>
                                @can('approve-journal-entries')
                                    @if ($entry->status === 'draft')
                                        <button class="btn btn-sm btn-outline-success" wire:click="approveAndPost({{ $entry->id }})">
                                            Approve &amp; Post
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">No journal entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $entries->links() }}
        </div>
    </div>
</div>
