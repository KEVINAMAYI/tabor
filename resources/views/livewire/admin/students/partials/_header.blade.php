{{-- Student profile header card --}}
<div class="card border-0 shadow-sm mb-0">
    <div class="card-body px-4 py-3">
        <div class="d-flex align-items-center gap-4 flex-wrap">

            {{-- Avatar --}}
            <div class="sv-avatar flex-shrink-0">
                {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
            </div>

            {{-- Name & identifiers --}}
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h5 class="mb-0 fw-bold text-dark">
                        {{ $student->first_name }} {{ $student->last_name }}
                    </h5>
                    @php
                        $sStatus = $student->status ?? 'active';
                        $sStatusClass = match ($sStatus) {
                            'active'   => 'bg-success-subtle text-success',
                            'deferred' => 'bg-warning-subtle text-warning',
                            'inactive' => 'bg-secondary-subtle text-secondary',
                            default    => 'bg-light text-muted',
                        };
                    @endphp
                    <span class="badge {{ $sStatusClass }}">{{ ucfirst($sStatus) }}</span>
                </div>

                <div class="text-muted d-flex flex-wrap gap-3" style="font-size:.82rem;">
                    <span>
                        <i class="ti ti-id me-1"></i>
                        TTI/{{ $student->admission_number }}/{{ $student->created_at->format('Y') }}
                    </span>
                    @if ($student->phone)
                        <span><i class="ti ti-phone me-1"></i>{{ $student->phone }}</span>
                    @endif
                    @if ($student->email)
                        <span><i class="ti ti-mail me-1"></i>{{ $student->email }}</span>
                    @endif
                </div>
            </div>

            {{-- Quick stats --}}
            <div class="d-flex gap-2 flex-wrap">
                <div class="sv-stat-card text-center">
                    <div class="sv-stat-label">Active</div>
                    <div class="sv-stat-value text-primary">{{ $activeEnrollments }}</div>
                </div>
                <div class="sv-stat-card text-center">
                    <div class="sv-stat-label">Completed</div>
                    <div class="sv-stat-value text-success">{{ $completedEnrollments }}</div>
                </div>
            </div>

            {{-- Enroll CTA --}}
            <button type="button"
                    class="btn btn-primary rounded-3 px-4 flex-shrink-0"
                    wire:click="openEnrollModal">
                <i class="ti ti-plus me-1"></i> Enroll
            </button>

        </div>
    </div>
</div>
