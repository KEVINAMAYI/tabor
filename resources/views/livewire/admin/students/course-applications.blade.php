<?php

use App\Actions\CourseApplication\ApproveCourseApplicationAction;
use App\Actions\CourseApplication\RejectCourseApplicationAction;
use App\Actions\CourseApplication\RevertCourseApplicationDecisionAction;
use App\Models\Course;
use App\Models\CourseApplication;
use App\Notifications\CourseApplicationApproved;
use App\Notifications\CourseApplicationRejected;
use App\Services\EnrollmentChargePreviewService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $perPage = 10;
    public $search = '';
    public $statusFilter = '';

    public ?int $reviewId = null;
    public ?CourseApplication $reviewApplication = null;
    public $admission_date;
    public $enrollment_status = 'active';
    public $rejection_reason;
    public array $chargePreview = [];

    public function mount(): void
    {
        if (!auth()->user()->hasPermissionTo('view-students')) {
            abort(403, 'Unauthorized action.');
        }
    }

    #[On('search')]
    public function search(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'applications' => CourseApplication::query()
                ->with(['course', 'preferredTrimester'])
                ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
                ->when(
                    !empty($this->search),
                    fn($q) => $q->where(function ($query) {
                        $query
                            ->where('first_name', 'like', "%{$this->search}%")
                            ->orWhere('last_name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%")
                            ->orWhere('phone_number', 'like', "%{$this->search}%");
                    }),
                )
                ->latest()
                ->paginate($this->perPage ?? 10),
        ];
    }

    public function openReview(int $id): void
    {
        $this->reviewApplication = CourseApplication::with('course')->findOrFail($id);

        $this->reviewId = $this->reviewApplication->id;
        $this->admission_date = now()->format('Y-m-d');
        $this->enrollment_status = 'active';
        $this->rejection_reason = null;

        $this->loadChargePreview();

        $this->dispatch('show-application-modal');
    }

    public function loadChargePreview(): void
    {
        $this->chargePreview = [];

        if (!$this->reviewApplication?->course) {
            return;
        }

        $this->chargePreview = app(EnrollmentChargePreviewService::class)
            ->preview(null, $this->reviewApplication->course);
    }

    public function removeFeeItem(int $index): void
    {
        unset($this->chargePreview[$index]);
        $this->chargePreview = array_values($this->chargePreview);
    }

    public function addCustomFeeItem(): void
    {
        $this->chargePreview[] = [
            'type' => 'Custom',
            'description' => '',
            'timing' => 'One-time (specific to this student)',
            'amount' => 0,
            'fee_definition_id' => null,
            'course_fee_plan_id' => null,
        ];
    }

    public function getFeeTotalProperty(): float
    {
        return (float) collect($this->chargePreview)->sum(fn ($item) => (float) ($item['amount'] ?? 0));
    }

    public function approve(ApproveCourseApplicationAction $action): void
    {
        $this->validate([
            'admission_date' => ['required', 'date'],
            'enrollment_status' => ['required', 'in:active,pending'],
        ]);

        try {
            $application = CourseApplication::findOrFail($this->reviewId);

            $action->execute($application, [
                'admission_date' => $this->admission_date,
                'enrollment_status' => $this->enrollment_status,
                'fee_overrides' => $this->chargePreview,
            ]);

            try {
                Notification::route('mail', $application->email)
                    ->notify(new CourseApplicationApproved($application, $this->admission_date));
            } catch (\Throwable $e) {
                Log::warning('Failed to send course application approval email', [
                    'application_id' => $application->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->resetReviewForm();
            $this->dispatch('hide-application-modal');

            LivewireAlert::text('Application approved and enrollment created.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Failed to approve course application', [
                'application_id' => $this->reviewId,
                'error' => $e->getMessage(),
            ]);

            LivewireAlert::text('Failed to approve application: ' . $e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function reject(RejectCourseApplicationAction $action): void
    {
        try {
            $application = CourseApplication::findOrFail($this->reviewId);

            $action->execute($application, $this->rejection_reason);

            try {
                Notification::route('mail', $application->email)
                    ->notify(new CourseApplicationRejected($application->fresh()));
            } catch (\Throwable $e) {
                Log::warning('Failed to send course application rejection email', [
                    'application_id' => $application->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->resetReviewForm();
            $this->dispatch('hide-application-modal');

            LivewireAlert::text('Application rejected.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Failed to reject course application', [
                'application_id' => $this->reviewId,
                'error' => $e->getMessage(),
            ]);

            LivewireAlert::text('Failed to reject application: ' . $e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    public function revert(int $id, RevertCourseApplicationDecisionAction $action): void
    {
        try {
            $application = CourseApplication::findOrFail($id);

            $action->execute($application);

            LivewireAlert::text('Decision reverted — application is pending again.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Failed to revert course application decision', [
                'application_id' => $id,
                'error' => $e->getMessage(),
            ]);

            LivewireAlert::text('Could not revert: ' . $e->getMessage())->error()->toast()->position('top-end')->show();
        }
    }

    private function resetReviewForm(): void
    {
        $this->reviewId = null;
        $this->reviewApplication = null;
        $this->admission_date = null;
        $this->enrollment_status = 'active';
        $this->rejection_reason = null;
        $this->chargePreview = [];
    }
};
?>

<div>
    <div class="card card-body">
        <div class="row">
            <div class="col-md-4 col-xl-3">
                <input wire:keyup.debounce.300ms="$dispatch('search')" type="text"
                    class="form-control" placeholder="Search applicants..." wire:model="search">
            </div>
            <div class="col-md-4 col-xl-3">
                <select wire:model.live="statusFilter" class="form-select">
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="">All</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card card-body">
        <div class="table-responsive">
            <table class="table search-table align-middle text-nowrap">
                <thead class="header-item">
                    <tr>
                        <th>#</th>
                        <th>Applicant</th>
                        <th>Course</th>
                        <th>Preferred Intake</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Applied On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td class="text-blue fw-bold">{{ $loop->iteration }}</td>
                            <td>{{ $application->first_name }} {{ $application->last_name }}</td>
                            <td>{{ $application->course->title ?? 'N/A' }}</td>
                            <td>{{ $application->preferredTrimester?->start_date?->format('M Y') ?? '-' }}</td>
                            <td>{{ $application->phone_number }}</td>
                            <td>
                                <span class="badge bg-{{ $application->status === 'pending' ? 'warning' : ($application->status === 'rejected' ? 'danger' : 'success') }}">
                                    {{ ucfirst($application->status) }}
                                </span>
                            </td>
                            <td class="text-muted">{{ $application->created_at->format('d-m-Y') }}</td>
                            <td>
                                @if ($application->status === 'pending')
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        wire:click="openReview({{ $application->id }})">
                                        Review
                                    </button>
                                @else
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted small">
                                            {{ $application->status === 'approved' ? 'Approved' : 'Rejected' }}
                                        </span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary px-2"
                                            wire:click="revert({{ $application->id }})"
                                            wire:confirm="Revert this {{ $application->status }} decision? This undoes everything it created and puts the application back to Pending."
                                            title="Revert this decision">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No applications found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $applications->links() }}
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="applicationModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                @if ($reviewApplication)
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Name:</strong> {{ $reviewApplication->first_name }} {{ $reviewApplication->last_name }}
                            </div>
                            <div class="col-md-6">
                                <strong>Email:</strong> {{ $reviewApplication->email }}
                            </div>
                            <div class="col-md-6">
                                <strong>Phone:</strong> {{ $reviewApplication->phone_number }}
                            </div>
                            <div class="col-md-6">
                                <strong>Course:</strong> {{ $reviewApplication->course->title ?? 'N/A' }}
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Admission Date</label>
                                <input type="date" class="form-control" wire:model="admission_date">
                                @error('admission_date')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Enrollment Status</label>
                                <select class="form-select" wire:model="enrollment_status">
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                </select>
                                @error('enrollment_status')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <strong>Estimated Fees</strong>
                                    <span class="text-muted small">(edit amounts, remove items, or add a custom charge before approving)</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addCustomFeeItem">
                                    <i class="ti ti-plus"></i> Add Fee Item
                                </button>
                            </div>

                            @if (count($chargePreview))
                                <div class="table-responsive mt-2">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr class="small text-muted">
                                                <th>Description</th>
                                                <th>Frequency</th>
                                                <th class="text-end" style="width: 130px;">Amount</th>
                                                <th style="width: 40px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($chargePreview as $index => $item)
                                                <tr>
                                                    <td>
                                                        @if (empty($item['fee_definition_id']))
                                                            <input type="text" placeholder="Fee description"
                                                                wire:model.live.debounce.300ms="chargePreview.{{ $index }}.description"
                                                                class="form-control form-control-sm">
                                                        @else
                                                            {{ $item['description'] }}
                                                        @endif
                                                    </td>
                                                    <td class="text-muted small">{{ $item['timing'] }}</td>
                                                    <td>
                                                        <input type="number" step="0.01" min="0"
                                                            wire:model.live.debounce.300ms="chargePreview.{{ $index }}.amount"
                                                            class="form-control form-control-sm text-end">
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-danger px-2"
                                                            wire:click="removeFeeItem({{ $index }})" title="Remove this fee item">
                                                            <i class="ti ti-x"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold">
                                                <td colspan="2">Total</td>
                                                <td class="text-end">{{ number_format($this->feeTotal, 2) }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Rejection Reason (only needed if rejecting)</label>
                            <textarea class="form-control" rows="2" wire:model="rejection_reason"></textarea>
                        </div>
                    </div>
                @endif

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-outline-danger" wire:click="reject"
                        wire:confirm="Reject this application?">
                        Reject
                    </button>
                    <button type="button" class="btn btn-success" wire:click="approve">
                        Approve &amp; Enroll
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.addEventListener('show-application-modal', () => {
                new bootstrap.Modal(document.getElementById('applicationModal')).show();
            });

            window.addEventListener('hide-application-modal', () => {
                bootstrap.Modal.getInstance(document.getElementById('applicationModal'))?.hide();
            });
        </script>
    @endpush
</div>
