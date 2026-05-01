<?php

use App\Models\AcademicYear;
use App\Models\Trimester;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public $search = '';

    // Selected items
    public $selectedAcademicYearId = null;
    public $selectedTrimesterId = null;

    // Academic year form
    public $academicYearId = null;
    public $academic_year_name = '';
    public $academic_year_starts_at = '';
    public $academic_year_ends_at = '';
    public $academic_year_is_active = false;

    // Trimester form
    public $trimesterId = null;
    public $trimester_academic_year_id = null;
    public $trimester_name = '';
    public $trimester_number = '';
    public $trimester_starts_at = '';
    public $trimester_ends_at = '';
    public $midpoint_date = '';
    public $intake_cutoff_date = '';
    public $status = 'upcoming';

    public function rules()
    {
        return [
            'academic_year_name' => 'nullable|string|max:255',
            'academic_year_starts_at' => 'nullable|date',
            'academic_year_ends_at' => 'nullable|date|after_or_equal:academic_year_starts_at',

            'trimester_academic_year_id' => 'nullable|exists:academic_years,id',
            'trimester_name' => 'nullable|string|max:255',
            'trimester_number' => 'nullable|integer|min:1|max:3',
            'trimester_starts_at' => 'nullable|date',
            'trimester_ends_at' => 'nullable|date|after_or_equal:trimester_starts_at',
            'midpoint_date' => 'nullable|date',
            'intake_cutoff_date' => 'nullable|date',
            'status' => 'nullable|in:upcoming,active,closed',
        ];
    }

    #[On('search-academic-calendar')]
    public function searchAcademicCalendar()
    {
        $this->resetPage();
    }

    public function with()
    {
        $academicYears = AcademicYear::query()
            ->when(filled($this->search), fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderByDesc('name')
            ->get();

        if (!$this->selectedAcademicYearId && $academicYears->count()) {
            $this->selectedAcademicYearId = $academicYears->where('name', now()->year)->first()->id;
        }

        $trimesters = Trimester::query()
            ->with('academicYear')
            ->when($this->selectedAcademicYearId, fn($q) => $q->where('academic_year_id', $this->selectedAcademicYearId))
            ->when(filled($this->search), fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('trimester_number')
            ->get();

        if (!$this->selectedTrimesterId && $trimesters->count()) {
            $this->selectedTrimesterId = $trimesters->first()->id;
        }

        $selectedTrimester = $this->selectedTrimesterId ? Trimester::with('academicYear')->find($this->selectedTrimesterId) : null;

        return [
            'academicYears' => $academicYears,
            'trimesters' => $trimesters,
            'selectedTrimester' => $selectedTrimester,
        ];
    }

    public function selectAcademicYear($id)
    {
        $this->selectedAcademicYearId = $id;
        $this->selectedTrimesterId = null;
    }

    public function selectTrimester($id)
    {
        $this->selectedTrimesterId = $id;
    }

    public function openAcademicYearModal()
    {
        $this->resetAcademicYearForm();
        $this->dispatch('show-academic-year-modal');
    }

    public function openTrimesterModal()
    {
        $this->resetTrimesterForm();
        $this->trimester_academic_year_id = $this->selectedAcademicYearId;
        $this->dispatch('show-trimester-modal');
    }

    public function editAcademicYear($id)
    {
        $year = AcademicYear::findOrFail($id);

        $this->academicYearId = $year->id;
        $this->academic_year_name = $year->name;
        $this->academic_year_starts_at = $year->start_date?->format('Y-m-d');
        $this->academic_year_ends_at = $year->end_date?->format('Y-m-d');
        $this->academic_year_is_active = $year->active;

        $this->dispatch('show-academic-year-modal');
    }

    public function editTrimester($id)
    {
        $trimester = Trimester::findOrFail($id);

        $this->trimesterId = $trimester->id;
        $this->trimester_academic_year_id = $trimester->academic_year_id;
        $this->trimester_name = $trimester->name;
        $this->trimester_number = $trimester->trimester_number;
        $this->trimester_starts_at = $trimester->start_date?->format('Y-m-d');
        $this->trimester_ends_at = $trimester->end_date?->format('Y-m-d');
        $this->midpoint_date = $trimester->midpoint_date?->format('Y-m-d');
        $this->intake_cutoff_date = $trimester->intake_cutoff_date?->format('Y-m-d');
        $this->status = $trimester->status;

        $this->dispatch('show-trimester-modal');
    }

    public function saveAcademicYear()
    {
        $this->validate([
            'academic_year_name' => 'required|string|max:255',
            'academic_year_starts_at' => 'required|date',
            'academic_year_ends_at' => 'required|date|after_or_equal:academic_year_starts_at',
        ]);

        try {
            AcademicYear::updateOrCreate(
                ['id' => $this->academicYearId],
                [
                    'name' => $this->academic_year_name,
                    'start_date' => $this->academic_year_starts_at,
                    'end_date' => $this->academic_year_ends_at,
                    'active' => (bool) $this->academic_year_is_active,
                ],
            );

            $this->resetAcademicYearForm();
            $this->dispatch('hide-academic-year-modal');

            LivewireAlert::text('Academic year saved successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Academic year save failed: ' . $e->getMessage());

            LivewireAlert::text('Failed to save academic year.')->error()->toast()->position('top-end')->show();
        }
    }

    public function saveTrimester()
    {
        $this->autoFillTrimesterFields();

        $this->validate([
            'trimester_academic_year_id' => 'required|exists:academic_years,id',
            'trimester_name' => 'required|string|max:255',
            'trimester_number' => 'required|integer|min:1|max:3',
            'trimester_starts_at' => 'required|date',
            'trimester_ends_at' => 'required|date|after_or_equal:trimester_starts_at',
            'midpoint_date' => 'required|date',
            'intake_cutoff_date' => 'required|date',
            'status' => 'required|in:upcoming,active,closed',
        ]);

        try {
            $trimester = Trimester::updateOrCreate(
                ['id' => $this->trimesterId],
                [
                    'academic_year_id' => $this->trimester_academic_year_id,
                    'name' => $this->trimester_name,
                    'trimester_number' => $this->trimester_number,
                    'start_date' => $this->trimester_starts_at,
                    'end_date' => $this->trimester_ends_at,
                    'midpoint_date' => $this->midpoint_date,
                    'intake_cutoff_date' => $this->intake_cutoff_date,
                    'status' => $this->status,
                ],
            );

            // $latest = Trimester::query()->where('academic_year_id', $this->trimester_academic_year_id)->where('trimester_number', $this->trimester_number)->first();

            $this->selectedAcademicYearId = $this->trimester_academic_year_id;
            $this->selectedTrimesterId = $trimester->id;

            $this->resetTrimesterForm();
            $this->dispatch('hide-trimester-modal');

            LivewireAlert::text('Trimester saved successfully.')->success()->toast()->position('top-end')->show();
        } catch (\Throwable $e) {
            Log::error('Trimester save failed: ' . $e->getMessage());

            LivewireAlert::text('Failed to save trimester.')->error()->toast()->position('top-end')->show();
        }
    }

    public function deleteAcademicYear($id)
    {
        AcademicYear::findOrFail($id)->delete();

        if ((int) $this->selectedAcademicYearId === (int) $id) {
            $this->selectedAcademicYearId = null;
            $this->selectedTrimesterId = null;
        }

        LivewireAlert::text('Academic year deleted successfully.')->success()->toast()->position('top-end')->show();
    }

    public function deleteTrimester($id)
    {
        Trimester::findOrFail($id)->delete();

        if ((int) $this->selectedTrimesterId === (int) $id) {
            $this->selectedTrimesterId = null;
        }

        LivewireAlert::text('Trimester deleted successfully.')->success()->toast()->position('top-end')->show();
    }

    public function updatedTrimesterStartsAt()
    {
        $this->autoFillTrimesterFields();
    }

    public function updatedTrimesterEndsAt()
    {
        $this->autoFillTrimesterFields();
    }

    public function updatedTrimesterNumber()
    {
        if ($this->trimester_number) {
            $this->trimester_name = 'Trimester ' . $this->trimester_number;
        }
    }

    protected function autoFillTrimesterFields()
    {
        if (!$this->trimester_starts_at || !$this->trimester_ends_at) {
            return;
        }

        $start = Carbon::parse($this->trimester_starts_at);
        $end = Carbon::parse($this->trimester_ends_at);

        $days = $start->diffInDays($end);
        $midpoint = $start->copy()->addDays((int) floor($days / 2));

        $this->midpoint_date = $midpoint->format('Y-m-d');
        $this->intake_cutoff_date = $midpoint->format('Y-m-d');
    }

    protected function resetAcademicYearForm()
    {
        $this->academicYearId = null;
        $this->academic_year_name = '';
        $this->academic_year_starts_at = '';
        $this->academic_year_ends_at = '';
        $this->academic_year_is_active = false;
    }

    protected function resetTrimesterForm()
    {
        $this->trimesterId = null;
        $this->trimester_academic_year_id = $this->selectedAcademicYearId;
        $this->trimester_name = '';
        $this->trimester_number = '';
        $this->trimester_starts_at = '';
        $this->trimester_ends_at = '';
        $this->midpoint_date = '';
        $this->intake_cutoff_date = '';
        $this->status = 'upcoming';
    }
};
?>

@push('styles')
    <style>
        /* === General Container === */
        .widget-content {
            border-radius: 18px;
        }

        .widget-content .card {
            border: none;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        }

        /* === Left Panel (Academic Years) === */
        .academic-item {
            transition: all 0.2s ease;
            border-radius: 12px;
        }

        .academic-item:hover {
            background-color: #f8f9ff;
        }

        .academic-item.active {
            background-color: #eef2ff;
        }

        /* === Middle Panel (Trimesters) === */
        .trimester-item {
            transition: all 0.2s ease;
        }

        .trimester-item:hover {
            background-color: #f9fafb;
        }

        .trimester-item.active {
            background-color: #eef2ff;
        }

        /* Circle number */
        .trimester-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #eef2ff;
            color: #4f46e5;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* === Right Panel (Details) === */
        .details-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #eef2ff;
            color: #4f46e5;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Labels */
        .label-title {
            font-size: 0.75rem;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .label-value {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
        }

        /* === Buttons === */
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
        }

        /* === Search input === */
        .product-search {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .product-search:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
        }

        /* === Badges === */
        .badge {
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 6px;
        }

        /* === Dropdown fix === */
        .dropdown-menu {
            border-radius: 10px;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        /* === Modal polish === */
        .modal-content {
            border-radius: 16px;
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid #f1f1f1;
        }

        .modal-footer {
            border-top: 1px solid #f1f1f1;
        }

        /* === Scroll fix for panels === */
        .col-md-3,
        .col-md-4,
        .col-md-5 {
            max-height: 80vh;
            overflow-y: auto;
        }

        /* smooth scroll */
        .col-md-3::-webkit-scrollbar,
        .col-md-4::-webkit-scrollbar,
        .col-md-5::-webkit-scrollbar {
            width: 6px;
        }

        .col-md-3::-webkit-scrollbar-thumb,
        .col-md-4::-webkit-scrollbar-thumb,
        .col-md-5::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 10px;
        }
    </style>
@endpush

<div class="row">
    <div class="col-12">
        <div class="widget-content searchable-container list">
            <div class="card overflow-hidden" style="border-radius: 18px;">
                <div class="row g-0">

                    {{-- LEFT PANEL: Academic Years --}}
                    <div class="col-md-3 border-end bg-white">
                        <div class="p-4">
                            <button class="btn btn-primary w-100 py-3 fw-semibold rounded-3 mb-4"
                                wire:click="openAcademicYearModal">
                                Add New Academic Year
                            </button>

                            <ul class="list-unstyled mb-0">
                                @forelse($academicYears as $year)
                                    <li class="d-flex align-items-center justify-content-between px-3 py-3 rounded-3 mb-2 {{ (int) $selectedAcademicYearId === (int) $year->id ? 'bg-light' : '' }}"
                                        style="cursor:pointer;" wire:click="selectAcademicYear({{ $year->id }})">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="pt-1">
                                                <i class="ti ti-calendar-event text-primary fs-5"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark">
                                                    {{ $year->name }}
                                                </div>
                                                <div class="text-muted small">
                                                    {{ optional($year->start_date)->format('d M Y') }} -
                                                    {{ optional($year->end_date)->format('d M Y') }}
                                                </div>
                                                <div class="mt-1">
                                                    @if ($year->active)
                                                        <span class="badge bg-success-subtle text-success">Active</span>
                                                    @else
                                                        <span class="badge bg-light text-muted">Inactive</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="dropdown" wire:click.stop>
                                            <a href="javascript:void(0)" class="text-muted" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0)"
                                                        wire:click="editAcademicYear({{ $year->id }})">
                                                        <i class="ti ti-pencil me-2"></i> Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="javascript:void(0)"
                                                        wire:click="deleteAcademicYear({{ $year->id }})"
                                                        onclick="return confirm('Are you sure you want to delete this academic year?')">
                                                        <i class="ti ti-trash me-2"></i> Delete
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>
                                @empty
                                    <li class="text-center text-muted py-5">
                                        No academic years found.
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    {{-- MIDDLE PANEL: Search + Trimesters --}}
                    <div class="col-md-4 border-end bg-white">
                        <div class="p-4 border-bottom">
                            <div class="position-relative">
                                <input type="text" class="form-control ps-5 py-3 rounded-3" placeholder="Search"
                                    wire:model.live="search"
                                    wire:keyup.debounce.300ms="$dispatch('search-academic-calendar')">
                                <i
                                    class="ti ti-search position-absolute top-50 start-0 translate-middle-y ms-3 fs-5 text-muted"></i>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <h6 class="mb-0 fw-semibold text-dark">Trimesters</h6>

                                <button class="btn btn-sm btn-primary rounded-3" wire:click="openTrimesterModal"
                                    @disabled(!$selectedAcademicYearId)>
                                    <i class="ti ti-plus me-1"></i> Add
                                </button>
                            </div>
                        </div>

                        <div class="p-0">
                            @forelse($trimesters as $trimester)
                                <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom {{ (int) $selectedTrimesterId === (int) $trimester->id ? 'bg-light' : '' }}"
                                    style="cursor:pointer;" wire:click="selectTrimester({{ $trimester->id }})">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="pt-1">
                                            <span
                                                class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary fw-bold"
                                                style="width:42px; height:42px;">
                                                {{ $trimester->trimester_number }}
                                            </span>
                                        </div>

                                        <div>
                                            <div class="fw-semibold text-dark">
                                                {{ $trimester->name }}
                                            </div>
                                            <div class="text-muted small">
                                                {{ optional($trimester->start_date)->format('d M Y') }}
                                                -
                                                {{ optional($trimester->end_date)->format('d M Y') }}
                                            </div>
                                            <div class="mt-1">
                                                @php
                                                    $statusClasses = match ($trimester->status) {
                                                        'active' => 'bg-success-subtle text-success',
                                                        'closed' => 'bg-danger-subtle text-danger',
                                                        default => 'bg-warning-subtle text-warning',
                                                    };
                                                @endphp
                                                <span class="badge {{ $statusClasses }}">
                                                    {{ ucfirst($trimester->status) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="dropdown" wire:click.stop>
                                        <a href="javascript:void(0)" class="text-muted" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="javascript:void(0)"
                                                    wire:click="editTrimester({{ $trimester->id }})">
                                                    <i class="ti ti-pencil me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="javascript:void(0)"
                                                    wire:click="deleteTrimester({{ $trimester->id }})"
                                                    onclick="return confirm('Are you sure you want to delete this trimester?')">
                                                    <i class="ti ti-trash me-2"></i> Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-5">
                                    No trimesters found for this academic year.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- RIGHT PANEL: Trimester Details --}}
                    <div class="col-md-5 bg-white">
                        <div class="d-flex align-items-center justify-content-between px-4 py-4 border-bottom">
                            <h3 class="mb-0 fw-semibold text-dark">Trimester Details</h3>

                            @if ($selectedTrimester)
                                <div class="d-flex align-items-center gap-3">
                                    <a href="javascript:void(0)" class="text-dark"
                                        wire:click="editTrimester({{ $selectedTrimester->id }})" title="Edit">
                                        <i class="ti ti-pencil fs-5"></i>
                                    </a>
                                    <a href="javascript:void(0)" class="text-danger"
                                        wire:click="deleteTrimester({{ $selectedTrimester->id }})" title="Delete">
                                        <i class="ti ti-trash fs-5"></i>
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="p-4">
                            @if ($selectedTrimester)
                                <div class="d-flex align-items-start gap-3 mb-4">
                                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold"
                                        style="width:72px; height:72px; font-size: 1.2rem;">
                                        T{{ $selectedTrimester->trimester_number }}
                                    </div>

                                    <div>
                                        <h3 class="mb-1 fw-semibold text-dark">{{ $selectedTrimester->name }}</h3>
                                        <div class="text-muted">
                                            {{ $selectedTrimester->academicYear->name ?? '—' }}
                                        </div>
                                        <div class="mt-2">
                                            @php
                                                $detailStatusClasses = match ($selectedTrimester->status) {
                                                    'active' => 'bg-success-subtle text-success',
                                                    'closed' => 'bg-danger-subtle text-danger',
                                                    default => 'bg-warning-subtle text-warning',
                                                };
                                            @endphp
                                            <span class="badge {{ $detailStatusClasses }}">
                                                {{ ucfirst($selectedTrimester->status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">Trimester Number</div>
                                        <div class="fw-semibold text-dark fs-5">
                                            {{ $selectedTrimester->trimester_number }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">Academic Year</div>
                                        <div class="fw-semibold text-dark fs-5">
                                            {{ $selectedTrimester->academicYear->name ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">Start Date</div>
                                        <div class="fw-semibold text-dark fs-5">
                                            {{ optional($selectedTrimester->start_date)->format('d M Y') ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">End DAte</div>
                                        <div class="fw-semibold text-dark fs-5">
                                            {{ optional($selectedTrimester->end_date)->format('d M Y') ?? '—' }}
                                        </div>
                                    </div>

                                    {{-- <div class="col-md-6">
                                        <div class="text-muted small mb-1">Midpoint Date</div>
                                        <div class="fw-semibold text-dark fs-5">
                                            {{ optional($selectedTrimester->midpoint_date)->format('d M Y') ?? '—' }}
                                        </div>
                                    </div> --}}

                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">Intake Cutoff Date</div>
                                        <div class="fw-semibold text-dark fs-5">
                                            {{ optional($selectedTrimester->intake_cutoff_date)->format('d M Y') ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="text-muted small mb-1">Summary</div>
                                        <div class="text-dark lh-lg">
                                            <strong>{{ $selectedTrimester->name }}</strong>
                                            <strong>{{ $selectedTrimester->academicYear->name ?? 'N/A' }}</strong>,
                                            starts on
                                            <strong>{{ optional($selectedTrimester->start_date)->format('d M Y') ?? '—' }}</strong>,
                                            ends on
                                            <strong>{{ optional($selectedTrimester->end_date)->format('d M Y') ?? '—' }}</strong>,
                                            and intake cutoff on
                                            <strong>{{ optional($selectedTrimester->intake_cutoff_date)->format('d M Y') ?? '—' }}</strong>.
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center text-muted py-5">
                                    Select a trimester to view details.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Academic Year Modal --}}
            <div class="modal fade" id="academicYearModal" tabindex="-1" wire:ignore.self>
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                {{ $academicYearId ? 'Edit Academic Year' : 'Add Academic Year' }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <form wire:submit.prevent="saveAcademicYear">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Academic Year Name</label>
                                    <input type="text" class="form-control" wire:model="academic_year_name"
                                        placeholder="e.g. 2026 Academic Year">
                                    @error('academic_year_name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Starts At</label>
                                        <input type="date" class="form-control"
                                            wire:model="academic_year_starts_at">
                                        @error('academic_year_starts_at')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Ends At</label>
                                        <input type="date" class="form-control"
                                            wire:model="academic_year_ends_at">
                                        @error('academic_year_ends_at')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                        wire:model="academic_year_is_active" id="academic_year_is_active">
                                    <label class="form-check-label" for="academic_year_is_active">Set as active
                                        academic year</label>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button class="btn btn-primary">
                                    {{ $academicYearId ? 'Update' : 'Save' }}
                                </button>
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Trimester Modal --}}
            <div class="modal fade" id="trimesterModal" tabindex="-1" wire:ignore.self>
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                {{ $trimesterId ? 'Edit Trimester' : 'Add Trimester' }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <form wire:submit.prevent="saveTrimester">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Academic Year</label>
                                    <select class="form-select" wire:model="trimester_academic_year_id">
                                        <option value="">Select academic year</option>
                                        @foreach ($academicYears as $year)
                                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('trimester_academic_year_id')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Trimester Number</label>
                                        <select class="form-select" wire:model.live="trimester_number">
                                            <option value="">Select number</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                        </select>
                                        @error('trimester_number')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Trimester Name</label>
                                        <input type="text" class="form-control" wire:model="trimester_name"
                                            placeholder="e.g. Trimester 1">
                                        @error('trimester_name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control"
                                            wire:model.live="trimester_starts_at">
                                        @error('trimester_starts_at')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">End Date</label>
                                        <input type="date" class="form-control"
                                            wire:model.live="trimester_ends_at">
                                        @error('trimester_ends_at')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Intake Cutoff Date</label>
                                        <input type="date" class="form-control" wire:model="intake_cutoff_date">
                                        @error('intake_cutoff_date')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" wire:model="status">
                                            <option value="upcoming">Upcoming</option>
                                            <option value="active">Active</option>
                                            <option value="closed">Closed</option>
                                        </select>
                                        @error('status')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button class="btn btn-primary">
                                    {{ $trimesterId ? 'Update' : 'Save' }}
                                </button>
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // === Academic Year Modal ===
            window.addEventListener('show-academic-year-modal', () => {
                const modal = new bootstrap.Modal(document.getElementById('academicYearModal'));
                modal.show();
            });

            window.addEventListener('hide-academic-year-modal', () => {
                const modalEl = document.getElementById('academicYearModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal?.hide();
            });

            // === Trimester Modal ===
            window.addEventListener('show-trimester-modal', () => {
                const modal = new bootstrap.Modal(document.getElementById('trimesterModal'));
                modal.show();
            });

            window.addEventListener('hide-trimester-modal', () => {
                const modalEl = document.getElementById('trimesterModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal?.hide();
            });

        });
    </script>
@endpush
