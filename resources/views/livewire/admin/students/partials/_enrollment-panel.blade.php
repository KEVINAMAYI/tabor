{{-- Enrollment detail panel — shown when an enrollment is selected --}}
@php
    $panelStatusCls = match ($selectedEnrollment->status) {
        'active' => 'bg-primary-subtle text-primary',
        'course_completed' => 'bg-success-subtle text-success',
        'pending_graduation' => 'bg-warning-subtle text-warning',
        'graduated' => 'bg-dark-subtle text-dark',
        'deferred' => 'bg-warning-subtle text-warning',
        'withdrawn' => 'bg-secondary-subtle text-secondary',
        'cancelled' => 'bg-danger-subtle text-danger',
        default => 'bg-light text-muted',
    };

    $totalTrimesters = max($selectedEnrollment->course->number_of_trimesters ?? 3, $discountProgressions->count());

    // Find an active progression for the statement link
    $activeProgForStatement = $discountProgressions->firstWhere('status', 'active');
@endphp

<div class="sv-detail-card">

    {{-- ── Course header bar ── --}}
    <div class="sv-detail-top d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h5 class="fw-bold mb-1">
                {{ $selectedEnrollment->course->title }}
                <span class="text-muted fw-normal"> - {{ $selectedEnrollment->course->level }}</span>
            </h5>
            <div class="text-muted d-flex flex-wrap gap-3" style="font-size:.8rem;">
                <span>
                    <i class="ti ti-calendar me-1"></i>
                    Admitted {{ optional($selectedEnrollment->admission_date)->format('d M Y') ?? '-' }}
                </span>
                <span>
                    <i class="ti ti-layers me-1"></i>
                    {{ $selectedEnrollment->course->number_of_trimesters ?? '?' }} trimesters
                </span>
                @if ($selectedEnrollment->intakeTrimester)
                    <span>
                        <i class="ti ti-calendar-event me-1"></i>
                        Intake: {{ $selectedEnrollment->intakeTrimester->name }}
                    </span>
                @endif
            </div>
        </div>
        <span class="badge {{ $panelStatusCls }} px-3 py-2" style="font-size:.8rem;">
            {{ str_replace('_', ' ', ucfirst($selectedEnrollment->status)) }}
        </span>
    </div>

    {{-- ── Progression timeline ── --}}
    <div class="sv-timeline-section">
        <div class="sv-section-label">Course Journey</div>
        <div class="sv-timeline-wrap">
            <div class="sv-timeline">
                @php $progCollection = $discountProgressions; @endphp

                @if ($progCollection->isNotEmpty())
                    @foreach ($progCollection as $idx => $prog)
                        @php
                            $tlStatus = $prog->status;
                            $tlClass = match ($tlStatus) {
                                'completed', 'course_completed' => 'completed',
                                'active' => 'active',
                                'deferred' => 'deferred',
                                'repeated' => 'repeated',
                                'cancelled' => 'cancelled',
                                'pending_graduation' => 'pending-grad',
                                'graduated' => 'graduated',
                                default => 'upcoming',
                            };
                            $tlIcon = match ($tlStatus) {
                                'completed', 'course_completed' => 'ti-check',
                                'active' => 'ti-point-filled',
                                'deferred' => 'ti-player-pause',
                                'repeated' => 'ti-refresh',
                                'cancelled' => 'ti-x',
                                'pending_graduation' => 'ti-certificate',
                                'graduated' => 'ti-school',
                                default => '',
                            };

                            $isLast = $idx === $progCollection->count() - 1;
                            $connClass = match ($tlStatus) {
                                'completed', 'course_completed', 'graduated' => 'done',
                                'active' => 'partial',
                                default => '',
                            };
                        @endphp

                        <div class="sv-tl-item">
                            <div class="sv-tl-seq">T{{ $prog->trimester_sequence }}</div>
                            <div class="sv-tl-circle {{ $tlClass }}"
                                title="{{ optional($prog->started_at ?? $prog->trimester?->start_date)->format('d M Y') ?? '?' }} - {{ optional($prog->completed_at ?? $prog->trimester?->end_date)->format('d M Y') ?? '?' }}">
                                @if ($tlIcon)
                                    <i class="{{ $tlIcon }}" style="font-size:.9rem;"></i>
                                @else
                                    {{ $prog->trimester_sequence }}
                                @endif
                            </div>
                            <div class="sv-tl-label">
                                <div class="fw-semibold" style="font-size:.7rem;">
                                    {{ $prog->trimester?->name ?? 'T' . $prog->trimester_sequence }}
                                </div>
                                <div class="text-muted" style="font-size:.65rem;">
                                    {{ ucfirst(str_replace('_', ' ', $tlStatus)) }}
                                </div>
                                <button type="button" class="btn btn-link btn-sm p-0 mt-1 text-decoration-none"
                                    style="font-size:.62rem; line-height:1;"
                                    wire:click="openEditProgressionModal({{ $prog->id }})"
                                    title="Edit trimester / dates">
                                    <i class="ti ti-pencil" style="font-size:.68rem;"></i> Edit
                                </button>
                            </div>
                        </div>

                        @if (!$isLast)
                            <div class="sv-tl-connector {{ $connClass }}"></div>
                        @endif
                    @endforeach

                    {{-- Ghost nodes for trimesters not yet started --}}
                    @php
                        $maxSeqDone = $progCollection->max('trimester_sequence') ?? 0;
                    @endphp
                    @for ($ghost = $maxSeqDone + 1; $ghost <= $totalTrimesters; $ghost++)
                        <div class="sv-tl-connector"></div>
                        <div class="sv-tl-item">
                            <div class="sv-tl-seq">T{{ $ghost }}</div>
                            <div class="sv-tl-circle upcoming">{{ $ghost }}</div>
                            <div class="sv-tl-label text-muted" style="font-size:.67rem;">Upcoming</div>
                        </div>
                    @endfor
                @else
                    <div class="text-muted small py-2">No progressions recorded yet.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Financial summary cards ── --}}
    <div class="row g-3 mb-0 sv-fin-row">
        <div class="col-4">
            <div class="sv-fin-card sv-fin-primary h-100">
                <div class="sv-fin-label"><i class="ti ti-receipt me-1"></i>Total Charges</div>
                <div class="sv-fin-value">KES {{ number_format($totalCharges, 0) }}</div>
            </div>
        </div>
        <div class="col-4">
            <div class="sv-fin-card sv-fin-success h-100">
                <div class="sv-fin-label"><i class="ti ti-cash me-1"></i>Paid</div>
                <div class="sv-fin-value">KES {{ number_format($totalPaid, 0) }}</div>
            </div>
        </div>
        <div class="col-4">
            <div class="sv-fin-card {{ $balance > 0 ? 'sv-fin-danger' : 'sv-fin-success' }} h-100">
                <div class="sv-fin-label"><i class="ti ti-scale me-1"></i>Balance</div>
                <div class="sv-fin-value">KES {{ number_format($balance, 0) }}</div>
            </div>
        </div>
    </div>

    {{-- ── Contextual action bar ── --}}
    <div class="sv-action-bar d-flex flex-wrap gap-2">

        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3"
            wire:click="openPaymentModal({{ $selectedEnrollment->id }})">
            <i class="ti ti-cash me-1"></i> Post Payment
        </button>

        <button type="button" class="btn btn-outline-warning btn-sm rounded-3 px-3"
            wire:click="openDiscountModal({{ $selectedEnrollment->id }})">
            <i class="ti ti-discount-2 me-1"></i> Discount
        </button>

        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 px-3"
            wire:click="confirmGenerateCharges({{ $selectedEnrollment->id }})">
            <i class="ti ti-receipt me-1"></i> Generate Charges
        </button>

        @if (in_array($selectedEnrollment->status, ['active', 'approved']))
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 px-3"
                wire:click="openDeferralModal({{ $selectedEnrollment->id }})">
                <i class="ti ti-clock-pause me-1"></i> Defer
            </button>
        @endif

        @if ($selectedEnrollment->status === 'active')
            <button type="button" class="btn btn-outline-info btn-sm rounded-3 px-3"
                wire:click="markCourseCompleted({{ $selectedEnrollment->id }})"
                wire:confirm="Mark this enrollment as course completed?">
                <i class="ti ti-circle-check me-1"></i> Course Completed
            </button>
        @endif

        @if ($selectedEnrollment->status === 'course_completed')
            <button type="button" class="btn btn-outline-warning btn-sm rounded-3 px-3"
                wire:click="moveToPendingGraduation({{ $selectedEnrollment->id }})"
                wire:confirm="Move this student to pending graduation?">
                <i class="ti ti-certificate me-1"></i> Pending Graduation
            </button>
        @endif

        @if ($selectedEnrollment->status === 'pending_graduation')
            <button type="button" class="btn btn-outline-success btn-sm rounded-3 px-3"
                wire:click="markGraduated({{ $selectedEnrollment->id }})"
                wire:confirm="Mark this student as graduated?">
                <i class="ti ti-school me-1"></i> Graduate
            </button>
        @endif

        <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 px-3"
                wire:click="openEditEnrollmentModal({{ $selectedEnrollment->id }})">
                <i class="ti ti-pencil me-1"></i> Edit
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm rounded-3 px-3"
                wire:click="deleteEnrollment({{ $selectedEnrollment->id }})"
                wire:confirm="Delete this enrollment? This cannot be undone.">
                <i class="ti ti-trash"></i>
            </button>
        </div>
    </div>

    {{-- ── Tabs ── --}}
    <div class="sv-tabs-wrap">
        <ul class="nav sv-tabs gap-1 px-3 pt-2" id="enrollDetailTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-fees" type="button">
                    <i class="ti ti-receipt me-1"></i> Fee Schedule
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-payments" type="button">
                    <i class="ti ti-credit-card me-1"></i> Payments
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-statements" type="button">
                    <i class="ti ti-file-invoice me-1"></i> Statements
                </button>
            </li>
        </ul>

        <div class="tab-content sv-tab-content">

            {{-- FEE SCHEDULE TAB --}}
            <div class="tab-pane fade show active" id="tab-fees" role="tabpanel">

                @if ($discountProgressions->isNotEmpty())
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-3 pt-3 pb-2">
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label mb-0 small text-muted">Trimester</label>
                            <select class="form-select form-select-sm rounded-3" style="width:auto; min-width:220px;"
                                wire:model.live="feeScheduleTrimesterFilter">
                                <option value="">All Trimesters</option>
                                @foreach ($discountProgressions as $prog)
                                    <option value="{{ $prog->id }}">{{ $prog->display_label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($feeScheduleTrimesterFilter)
                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0"
                                wire:click="$set('feeScheduleTrimesterFilter', '')">
                                <i class="ti ti-x me-1"></i>Clear filter
                            </button>
                        @endif
                    </div>
                @endif

                @if ($selectedFeeItems->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="ti ti-receipt-off"
                            style="font-size:2.5rem; display:block; margin-bottom:.5rem;"></i>
                        @if ($feeScheduleTrimesterFilter)
                            No fee items for the selected trimester.
                        @else
                            No fee items yet. Use "Generate Charges" after enrollment.
                        @endif
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Description</th>
                                    <th>Trimester</th>
                                    <th>Charge Date</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($selectedFeeItems as $fi)
                                    @php
                                        $fiBadge = match ($fi->status) {
                                            'paid' => 'badge-paid',
                                            'partial' => 'badge-partial',
                                            'waived' => 'badge-waived',
                                            default => 'badge-pending',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-medium">{{ $fi->description }}</span>
                                            @if ($fi->credit_type)
                                                <span class="badge bg-info-subtle text-info ms-1"
                                                    style="font-size:.65rem;">
                                                    {{ strtoupper($fi->credit_type) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-muted" style="font-size:.8rem;">
                                            {{ $fi->progression?->display_label ?? '-' }}
                                        </td>
                                        <td class="text-muted" style="font-size:.8rem;">
                                            {{ optional($fi->charge_date)->format('d M Y') ?? '-' }}
                                        </td>
                                        <td class="text-end fw-semibold">
                                            {{ number_format($fi->amount, 0) }}
                                        </td>
                                        <td class="text-end text-success">
                                            {{ number_format($fi->amount_paid, 0) }}
                                        </td>
                                        <td
                                            class="text-end {{ $fi->balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                            {{ number_format($fi->balance, 0) }}
                                        </td>
                                        <td>
                                            <span class="badge {{ $fiBadge }}" style="font-size:.68rem;">
                                                {{ ucfirst($fi->status ?? 'pending') }}
                                            </span>
                                        </td>
                                        <td class="text-nowrap">
                                            @if ($fi->audits_count > 0)
                                                <button type="button"
                                                    class="btn btn-link btn-sm p-0 me-1 text-decoration-none text-muted"
                                                    wire:click="openFeeItemHistoryModal({{ $fi->id }})"
                                                    title="View change history ({{ $fi->audits_count }})">
                                                    <i class="ti ti-history" style="font-size:.9rem;"></i>
                                                </button>
                                            @endif
                                            <button type="button"
                                                class="btn btn-outline-secondary btn-sm rounded-3 px-2"
                                                wire:click="openEditFeeItemModal({{ $fi->id }})"
                                                title="Edit this fee item">
                                                <i class="ti ti-pencil" style="font-size:.85rem;"></i>
                                            </button>
                                            @if ($fi->status !== 'waived' && $fi->balance > 0)
                                                <button type="button"
                                                    class="btn btn-outline-danger btn-sm rounded-3 px-2"
                                                    wire:click="openWaiverModal({{ $fi->id }})"
                                                    title="Waive this fee">
                                                    <i class="ti ti-circle-x" style="font-size:.85rem;"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @php
                                $ftCharges = $selectedFeeItems->sum('amount');
                                $ftPaid = $selectedFeeItems->sum('amount_paid');
                                $ftBalance = $selectedFeeItems->sum('balance');
                            @endphp
                            <tfoot class="table-light">
                                <tr class="fw-semibold">
                                    <td colspan="3">
                                        Totals
                                        @if ($feeScheduleTrimesterFilter)
                                            <span class="text-muted fw-normal small">(filtered)</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($ftCharges, 0) }}</td>
                                    <td class="text-end text-success">{{ number_format($ftPaid, 0) }}</td>
                                    <td class="text-end {{ $ftBalance > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($ftBalance, 0) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

            </div>

            {{-- PAYMENTS TAB --}}
            <div class="tab-pane fade" id="tab-payments" role="tabpanel">

                @if ($discountProgressions->isNotEmpty())
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-3 pt-3 pb-2">
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label mb-0 small text-muted">Trimester</label>
                            <select class="form-select form-select-sm rounded-3" style="width:auto; min-width:220px;"
                                wire:model.live="paymentsTrimesterFilter">
                                <option value="">All Trimesters</option>
                                @foreach ($discountProgressions as $prog)
                                    <option value="{{ $prog->id }}">{{ $prog->display_label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($paymentsTrimesterFilter)
                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0"
                                wire:click="$set('paymentsTrimesterFilter', '')">
                                <i class="ti ti-x me-1"></i>Clear filter
                            </button>
                        @endif
                    </div>
                @endif

                @if ($payments->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="ti ti-credit-card"
                            style="font-size:2.5rem; display:block; margin-bottom:.5rem;"></i>
                        @if ($paymentsTrimesterFilter)
                            No payments allocated to the selected trimester.
                        @else
                            No payments posted yet.
                        @endif
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Reference</th>
                                    <th>Method</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Allocated</th>
                                    <th class="text-end">Unallocated</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    @php
                                        $allocated = $payment->allocations->sum('amount_allocated');
                                        $unallocated = $payment->unallocated_balance;
                                        $touchedTrimesters = $payment->allocations
                                            ->map(fn($a) => $a->studentFeeItem?->progression?->display_label)
                                            ->filter()
                                            ->unique()
                                            ->values();
                                    @endphp
                                    <tr>
                                        <td style="font-size:.82rem;">
                                            {{ optional($payment->payment_date)->format('d M Y') ?? '-' }}
                                        </td>
                                        <td style="font-size:.82rem;">
                                            <div>{{ $payment->reference ?? '-' }}</div>
                                            @if ($payment->receipt_no)
                                                <div class="text-muted" style="font-size:.72rem;">
                                                    {{ $payment->receipt_no }}</div>
                                            @endif
                                            @if ($touchedTrimesters->isNotEmpty())
                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                    @foreach ($touchedTrimesters as $label)
                                                        <span class="badge bg-primary-subtle text-primary"
                                                            style="font-size:.62rem;">{{ $label }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border" style="font-size:.7rem;">
                                                {{ strtoupper($payment->method ?? '-') }}
                                            </span>
                                            @if ($payment->is_sponsored && $payment->sponsored_by)
                                                <div class="text-muted" style="font-size:.68rem;">
                                                    {{ $payment->sponsored_by }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold">
                                            {{ number_format($payment->amount, 0) }}
                                        </td>
                                        <td class="text-end text-success">
                                            {{ number_format($allocated, 0) }}
                                        </td>
                                        <td
                                            class="text-end {{ $unallocated > 0 ? 'text-warning fw-semibold' : 'text-muted' }}">
                                            {{ number_format($unallocated, 0) }}
                                        </td>
                                        <td class="text-end">
                                            @if ($payment->method !== 'credit')
                                                <button type="button"
                                                    class="btn btn-outline-danger btn-sm rounded-3 px-2"
                                                    wire:click="openRefundModal({{ $payment->id }})"
                                                    title="Process refund">
                                                    <i class="ti ti-arrow-back-up" style="font-size:.85rem;"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>

                                    {{-- Allocation breakdown --}}
                                    @if ($payment->allocations->count())
                                        <tr class="bg-light">
                                            <td colspan="7" class="py-2 px-4">
                                                <div class="text-muted small mb-1">
                                                    Allocation breakdown
                                                    <span
                                                        class="text-muted">({{ $payment->allocations->count() }})</span>
                                                </div>
                                                <table class="table table-sm table-borderless mb-0"
                                                    style="font-size:.76rem;">
                                                    <tbody>
                                                        @foreach ($payment->allocations as $alloc)
                                                            <tr>
                                                                <td class="py-1 ps-0" style="width:45%;">
                                                                    {{ $alloc->studentFeeItem->description ?? 'Fee' }}
                                                                </td>
                                                                <td class="py-1 text-muted" style="width:30%;">
                                                                    {{ $alloc->studentFeeItem?->progression?->display_label ?? '-' }}
                                                                </td>
                                                                <td class="py-1 text-end fw-semibold"
                                                                    style="width:25%;">
                                                                    KES
                                                                    {{ number_format($alloc->amount_allocated, 0) }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- STATEMENTS TAB --}}
            <div class="tab-pane fade" id="tab-statements" role="tabpanel">
                @php
                    $stmtProgs = $discountProgressions->whereIn('status', ['active', 'completed'])->values();
                @endphp

                @if ($stmtProgs->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="ti ti-file-invoice"
                            style="font-size:2.5rem; display:block; margin-bottom:.5rem;"></i>
                        No statements available yet.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Trimester</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th class="text-end">Statement</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stmtProgs as $sp)
                                    @php
                                        $spBadge = match ($sp->status) {
                                            'active' => 'bg-primary-subtle text-primary',
                                            'completed' => 'bg-success-subtle text-success',
                                            default => 'bg-light text-muted',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="fw-medium">
                                            {{ $sp->display_label }}
                                            @if ($sp->trimester)
                                                <div class="text-muted fw-normal" style="font-size:.72rem;">
                                                    {{ $sp->trimester->name }}
                                                    {{ $sp->trimester->academicYear?->name }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-muted" style="font-size:.78rem;">
                                            {{ optional($sp->started_at ?? $sp->trimester?->start_date)->format('d M Y') ?? '-' }}
                                            -
                                            {{ optional($sp->completed_at ?? $sp->trimester?->end_date)->format('d M Y') ?? '-' }}
                                        </td>
                                        <td><span class="badge {{ $spBadge }}"
                                                style="font-size:.7rem;">{{ ucfirst($sp->status) }}</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('students.statement', ['student' => $student->id, 'progression' => $sp->id]) }}"
                                                target="_blank"
                                                class="btn btn-sm btn-outline-secondary rounded-3 px-3">
                                                <i class="ti ti-printer me-1"></i> Open
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Also show repeat trimester option for each active/completed progression --}}
                @if ($discountProgressions->whereIn('status', ['active', 'completed'])->count())
                    <div class="px-3 py-2 border-top">
                        <div class="text-muted small mb-1">Need to repeat a trimester?</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($discountProgressions->whereIn('status', ['active', 'completed']) as $rp)
                                <button type="button" class="btn btn-sm btn-outline-orange rounded-3"
                                    style="border-color:#fd7e14; color:#fd7e14; font-size:.75rem;"
                                    wire:click="openRepeatTrimesterModal({{ $rp->id }})">
                                    <i class="ti ti-refresh me-1"></i>
                                    Repeat T{{ $rp->trimester_sequence }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

        </div>{{-- .tab-content --}}
    </div>{{-- .sv-tabs-wrap --}}

</div>{{-- .sv-detail-card --}}
