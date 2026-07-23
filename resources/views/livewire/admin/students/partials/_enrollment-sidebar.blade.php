{{-- Enrollment list sidebar --}}
<div class="sv-sidebar flex-shrink-0">

    <div class="sv-sidebar-header">
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-semibold" style="font-size:.85rem; color:#344054;">Enrollments</span>
            <span class="badge rounded-pill bg-primary-subtle text-primary">{{ $enrollments->count() }}</span>
        </div>
    </div>

    <div class="sv-sidebar-body">
        @forelse ($enrollments as $enrollment)
            @php
                $enrBalance  = $enrollment->feeItems->sum('balance');
                $enrProgs    = $enrollment->progressions->sortBy('trimester_sequence');
                $totalT      = max($enrollment->course->number_of_trimesters ?? 3, $enrProgs->count());
                $doneCount   = $enrProgs->whereIn('status', ['completed', 'repeated', 'course_completed'])->count();

                $enrStatusCls = match ($enrollment->status) {
                    'active'             => 'bg-primary-subtle text-primary',
                    'course_completed'   => 'bg-success-subtle text-success',
                    'pending_graduation' => 'bg-warning-subtle text-warning',
                    'graduated'          => 'bg-dark-subtle text-dark',
                    'deferred'           => 'bg-warning-subtle text-warning',
                    'withdrawn'          => 'bg-secondary-subtle text-secondary',
                    'cancelled'          => 'bg-danger-subtle text-danger',
                    default              => 'bg-light text-muted',
                };
            @endphp

            <div wire:click="selectEnrollment({{ $enrollment->id }})"
                 class="sv-enroll-card {{ (int) $selectedEnrollmentId === (int) $enrollment->id ? 'is-selected' : '' }}">

                {{-- Course + status --}}
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                    <div class="sv-enroll-card-title flex-grow-1 pe-1">
                        {{ $enrollment->course->title }}
                        <span class="text-muted fw-normal"> - {{ $enrollment->course->level }}</span>
                    </div>
                    <span class="badge {{ $enrStatusCls }} flex-shrink-0" style="font-size:.67rem; white-space:nowrap;">
                        {{ str_replace('_', ' ', ucfirst($enrollment->status)) }}
                    </span>
                </div>

                {{-- Progress dots --}}
                <div class="sv-dots mb-2">
                    @for ($t = 1; $t <= $totalT; $t++)
                        @php
                            $tp = $enrProgs->where('trimester_sequence', $t)->sortByDesc('id')->first();
                            $dotCls = $tp ? $tp->status : 'upcoming';
                            if ($dotCls === 'course_completed') $dotCls = 'completed';
                        @endphp
                        <div class="sv-dot {{ $dotCls }}" title="T{{ $t }} - {{ ucfirst($dotCls) }}"></div>
                    @endfor
                </div>

                {{-- Footer row --}}
                <div class="d-flex justify-content-between align-items-center sv-enroll-card-sub">
                    <span>{{ $doneCount }}/{{ $totalT }} trimesters</span>
                    <span class="{{ $enrBalance > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                        KES {{ number_format($enrBalance, 0) }}
                    </span>
                </div>
            </div>
        @empty
            <div class="text-center py-5 px-3">
                <i class="ti ti-school text-muted" style="font-size:2.2rem;"></i>
                <p class="text-muted small mt-2 mb-0">No enrollments yet.</p>
            </div>
        @endforelse
    </div>

</div>
