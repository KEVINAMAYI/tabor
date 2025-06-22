<?php

use App\Models\Module;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

new class extends Component {

    public $modules = [];
    public $title, $description, $course_id;
    public $editId = null;
    public $selected = [];
    public $selectAll = false;
    public $search = '';

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'course_id' => 'required|exists:courses,id',
    ];

    public function mount($course_id)
    {
        $this->course_id = $course_id;
        $this->loadModules();
    }

    #[On('search')]
    public function search()
    {
        $this->loadModules();

    }

    public function loadModules()
    {
        $this->modules = Module::query()
            ->when($this->course_id, function ($query) {
                $query->where('course_id', $this->course_id);
            })
            ->when($this->search, function ($query) {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            })
            ->latest()
            ->get();
    }


    public function addModule()
    {
        $this->validate();
        try {
            Module::create([
                'title' => $this->title,
                'description' => $this->description,
                'course_id' => $this->course_id,
            ]);

            $this->resetForm();
            $this->loadModules();
            $this->dispatch('hide-module-modal');

            session()->flash('message', 'Module added successfully');

        } catch (\Exception $e) {
            dd('Error adding module: ' . $e->getMessage());
        }
    }

    public function editModule($id)
    {
        $module = Module::findOrFail($id);
        $this->editId = $module->id;
        $this->title = $module->title;
        $this->description = $module->description;
        $this->course_id = $module->course_id;

        $this->dispatch('show-module-modal');
    }

    public function updateModule()
    {
        $this->validate();

        try {
            $module = Module::findOrFail($this->editId);

            $module->update([
                'title' => $this->title,
                'description' => $this->description,
                'course_id' => $this->course_id,
            ]);

            $this->resetForm();
            $this->loadModules();
            $this->dispatch('hide-module-modal');

            session()->flash('message', 'Module updated successfully');

        } catch (\Exception $e) {
            Log::error('Error updating module: ' . $e->getMessage());
            session()->flash('error', 'Failed to update module');
        }
    }

    public function deleteModule($id)
    {
        try {
            $module = Module::findOrFail($id);
            $module->delete();
            $this->loadModules();
            session()->flash('message', 'Module deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting module: ' . $e->getMessage());
            session()->flash('error', 'Failed to delete module');
        }
    }

    public function deleteSelected()
    {
        Module::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
        $this->loadModules();
        session()->flash('message', 'Selected modules deleted successfully');
    }

    private function resetForm()
    {
        $this->title = $this->description = null;
        $this->editId = null;
    }

    #[On('select-all')]
    public function selectAll()
    {
        if ($this->selectAll) {
            $this->selected = $this->modules->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selected = [];
        }
    }

}; ?>

<div class="col-12">
    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-space-between">
                        <h4 class="mb-4 mb-sm-0 card-title">Computer Science</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="../main/index.html">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="breadcrumb-item" aria-current="page">
                        <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                          Computer Science
                        </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-body p-0">
                <img src="../assets/images/backgrounds/profilebg.jpg" alt="matdash-img" class="img-fluid">
                <div class="row align-items-center">
                    <div class="col-lg-4 order-lg-1 order-2">
                        <div class="d-flex align-items-center justify-content-around m-4">
                            <div class="text-center">
                                <i class="ti ti-file-description fs-6 d-block mb-2"></i>
                                <h4 class="mb-0 fw-semibold lh-1">6</h4>
                                <p class="mb-0 ">Modules</p>
                            </div>
                            <div class="text-center">
                                <i class="ti ti-user-circle fs-6 d-block mb-2"></i>
                                <h4 class="mb-0 fw-semibold lh-1">100</h4>
                                <p class="mb-0 ">Students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mt-n3 order-lg-2 order-1">
                        <div class="mt-n5">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <div class="d-flex align-items-center justify-content-center round-110">
                                    <div
                                        class="border border-4 border-white d-flex align-items-center justify-content-center rounded-circle overflow-hidden"
                                        style="background-color: #f0f0f0; width: 100px; height: 100px;">
                                        <i class="ti ti-book text-primary"
                                           style="font-size: 3rem !important; line-height: 1;"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center">
                                <h5 class="mb-0">Computer Science</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 order-last">
                    </div>
                </div>
                <ul class="nav nav-pills user-profile-tab justify-content-end mt-2 bg-primary-subtle rounded-2 rounded-top-0"
                    id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active hstack gap-2 rounded-0 fs-12 py-6" id="pills-modules-tab"
                                data-bs-toggle="pill" data-bs-target="#pills-modules" type="button" role="tab"
                                aria-controls="pills-modules" aria-selected="true">
                            <i class="ti ti-book fs-5"></i> <!-- Book icon for Courses -->
                            <span class="d-none d-md-block">Modules</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link hstack gap-2 rounded-0 fs-12 py-6" id="pills-attendance-tab"
                                data-bs-toggle="pill" data-bs-target="#pills-attendance" type="button" role="tab"
                                aria-controls="pills-attendance" aria-selected="false">
                            <i class="ti ti-calendar-check fs-5"></i>
                            <span class="d-none d-md-block">Attendance</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link hstack gap-2 rounded-0 fs-12 py-6" id="pills-payments-tab"
                                data-bs-toggle="pill" data-bs-target="#pills-payments" type="button" role="tab"
                                aria-controls="pills-payments" aria-selected="false">
                            <i class="ti ti-credit-card fs-5"></i> <!-- Credit card icon for Payments -->
                            <span class="d-none d-md-block">Payments</span>
                        </button>
                    </li>
                </ul>

            </div>
        </div>
        <div class="tab-content" id="pills-tabContent">
            <div class="tab-pane fade show active" id="pills-modules" role="tabpanel"
                 aria-labelledby="pills-modules-tab" tabindex="0">
                <div class="row">
                    <div class="col-12">
                        <div class="widget-content searchable-container list">
                            <div class="card card-body">
                                <div class="row">
                                    <div class="col-md-4 col-xl-3">
                                        <form class="position-relative">
                                            <input wire:keyup.debounce.100ms="$dispatch('search')" type="text"
                                                   class="form-control product-search ps-5"
                                                   placeholder="Search Modules..."
                                                   wire:model="search"/>
                                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                                        </form>
                                    </div>
                                    <div
                                        class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                                        @if (count($selected) > 0)
                                            <div class="action-btn">
                                                <a href="javascript:void(0)" wire:click.prevent="deleteSelected"
                                                   class="delete-multiple bg-danger-subtle btn me-2 text-danger">
                                                    <i class="ti ti-trash me-1 fs-5"></i> Delete Selected
                                                </a>
                                            </div>
                                        @endif
                                        <a href="javascript:void(0)" wire:click="$dispatch('show-module-modal')"
                                           class="btn btn-primary d-flex align-items-center">
                                            <i class="ti ti-book text-white me-1 fs-5"></i> Add Module
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-body">
                                <div class="table-responsive">
                                    <table class="table search-table align-middle text-nowrap">
                                        <thead class="header-item">
                                        <tr>
                                            <th>
                                                <div class="form-check text-center">
                                                    <input wire:click="$dispatch('select-all')" type="checkbox"
                                                           class="form-check-input"
                                                           wire:model="selectAll"/>
                                                </div>
                                            </th>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($modules as $module)
                                            <tr>
                                                <td>
                                                    <div class="form-check text-center">
                                                        <input type="checkbox" class="form-check-input"
                                                               wire:model="selected" value="{{ $module->id }}"/>
                                                    </div>
                                                </td>
                                                <td>{{ $module->title }}</td>
                                                <td>{{ Str::limit($module->description, 60) }}</td>
                                                <td>
                                                    <a href="javascript:void(0)"
                                                       wire:click="editModule({{ $module->id }})"
                                                       class="text-primary">
                                                        <i class="ti ti-pencil  fs-5">
                                                        </i>
                                                    </a>
                                                    <a href="javascript:void(0)"
                                                       wire:click="deleteModule({{ $module->id }})"
                                                       class="text-danger ms-2">
                                                        <i class="ti ti-trash fs-5"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="pills-attendance" role="tabpanel" aria-labelledby="pills-attendance-tab"
                 tabindex="0">
                <div class="row">
                    <div class="col-12">
                        <div class="widget-content searchable-container list">
                            <div class="card card-body">
                                <div class="row">
                                    <div class="col-md-4 col-xl-3">
                                        <form class="position-relative">
                                            <input type="text" class="form-control product-search ps-5" id="input-search" placeholder="Search Contacts..." />
                                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                                        </form>
                                    </div>
                                    <div class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                                        <div class="action-btn show-btn">
                                            <a href="javascript:void(0)" class="delete-multiple bg-danger-subtle btn me-2 text-danger d-flex align-items-center ">
                                                <i class="ti ti-trash me-1 fs-5"></i> Delete All Row
                                            </a>
                                        </div>
                                        <div  class="example">
                                            <div style="border-width:2px;" class="input-daterange input-group" id="date-range">
                                                <input type="text" class="form-control" name="start" />

                                                <span class="input-group-text bg-primary b-0 text-white">TO</span>

                                                <input type="text" class="form-control" name="end" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Modal -->
                            <div class="modal fade" id="addContactModal" tabindex="-1" role="dialog" aria-labelledby="addContactModalTitle" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header d-flex align-items-center">
                                            <h5 class="modal-title">Contact</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="add-contact-box">
                                                <div class="add-contact-content">
                                                    <form id="addContactModalTitle">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3 contact-name">
                                                                    <input type="text" id="c-name" class="form-control" placeholder="Name" />
                                                                    <span class="validation-text text-danger"></span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3 contact-email">
                                                                    <input type="text" id="c-email" class="form-control" placeholder="Email" />
                                                                    <span class="validation-text text-danger"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3 contact-occupation">
                                                                    <input type="text" id="c-occupation" class="form-control" placeholder="Occupation" />
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3 contact-phone">
                                                                    <input type="text" id="c-phone" class="form-control" placeholder="Phone" />
                                                                    <span class="validation-text text-danger"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="mb-3 contact-location">
                                                                    <input type="text" id="c-location" class="form-control" placeholder="Location" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <div class="d-flex gap-6 m-0">
                                                <button id="btn-add" class="btn btn-success">Add</button>
                                                <button id="btn-edit" class="btn btn-success">Save</button>
                                                <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal"> Discard
                                                </button>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card card-body">
                                <div class="table-responsive">
                                    <table class="table search-table align-middle text-nowrap">
                                        <thead class="header-item">
                                        <th>
                                            <div class="n-chk align-self-center text-center">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input primary" id="contact-check-all" />
                                                    <label class="form-check-label" for="contact-check-all"></label>
                                                    <span class="new-control-indicator"></span>
                                                </div>
                                            </div>
                                        </th>
                                        <th>Module</th>
                                        <th>Total</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                        <th>late</th>
                                        <th>Excused</th>
                                        <th>Action</th>
                                        </thead>
                                        <tbody>
                                        <!-- start row -->
                                        <tr class="search-items">
                                            <td>
                                                <div class="n-chk align-self-center text-center">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input contact-chkbox primary" id="checkbox1" />
                                                        <label class="form-check-label" for="checkbox1"></label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="ms-3">
                                                        <div class="user-meta-info">
                                                            <h6 class="user-name mb-0" data-name="Emma Adams">Computer Science</h6>
                                                            <span class="user-work fs-3" data-occupation="Web Developer">Web Development</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0)" class="text-bg-secondary text-white fs-6 round-40 rounded-circle me-n2 card-hover border border-2 border-white d-flex align-items-center justify-content-center">
                                                        <span class="fs-2">100</span>
                                                    </a>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-success-subtle text-success">10</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning-subtle text-warning">20</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-success">30</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-success">10</span>
                                            </td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="javascript:void(0)" class="text-primary edit">
                                                        <i class="ti ti-pencil fs-5"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- end row -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="pills-payments" role="tabpanel" aria-labelledby="pills-payments-tab"
                 tabindex="0">
                <div class="row">
                    <div class="col-12">
                        <div class="widget-content searchable-container list">
                            <div class="card card-body">
                                <div class="row">
                                    <div class="col-md-4 col-xl-3">
                                        <form class="position-relative">
                                            <input type="text" class="form-control product-search ps-5" id="input-search"
                                                   placeholder="Search Payments..." />
                                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                                        </form>
                                    </div>
                                    <div class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                                        @if (count($selected) > 0)
                                            <div class="action-btn">
                                                <a href="javascript:void(0)" wire:click.prevent="deleteSelected"
                                                   class="delete-multiple bg-danger-subtle btn me-2 text-danger">
                                                    <i class="ti ti-trash me-1 fs-5"></i> Delete Selected
                                                </a>
                                            </div>
                                        @endif
                                        <a href="javascript:void(0)" wire:click="$dispatch('show-module-modal')"
                                           class="btn btn-primary d-flex align-items-center">
                                            <i class="ti ti-currency-dollar text-white me-1 fs-5"></i> Add Payment
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Table -->
                            <div class="card card-body">
                                <div class="table-responsive">
                                    <table class="table search-table align-middle text-nowrap">
                                        <thead class="header-item">
                                        <tr>
                                            <th>
                                                <div class="form-check text-center">
                                                    <input type="checkbox" class="form-check-input primary" />
                                                </div>
                                            </th>
                                            <th>Payer</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th>Paid On</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <!-- Row 1 -->
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input" />
                                            </td>
                                            <td>
                                                <div class="user-meta-info">
                                                    <h6 class="user-name mb-0">John Doe</h6>
                                                    <span class="user-work fs-3">TX12345</span>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-secondary">KES 5,000</span></td>
                                            <td><span class="badge bg-warning text-dark">M-Pesa</span></td>
                                            <td><span class="badge bg-success-subtle text-success">Completed</span></td>
                                            <td>2024-05-01</td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="javascript:void(0)" class="text-primary edit">
                                                        <i class="ti ti-eye fs-5"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Row 2 -->
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input" />
                                            </td>
                                            <td>
                                                <div class="user-meta-info">
                                                    <h6 class="user-name mb-0">Jane Smith</h6>
                                                    <span class="user-work fs-3">TX12346</span>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-secondary">KES 3,200</span></td>
                                            <td><span class="badge bg-primary-subtle text-primary">Bank</span></td>
                                            <td><span class="badge bg-warning-subtle text-warning">Pending</span></td>
                                            <td>2024-05-03</td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="javascript:void(0)" class="text-primary edit">
                                                        <i class="ti ti-eye fs-5"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Row 3 -->
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input" />
                                            </td>
                                            <td>
                                                <div class="user-meta-info">
                                                    <h6 class="user-name mb-0">Ali Mwana</h6>
                                                    <span class="user-work fs-3">TX12347</span>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-secondary">KES 1,800</span></td>
                                            <td><span class="badge bg-info-subtle text-info">Cash</span></td>
                                            <td><span class="badge bg-success-subtle text-success">Completed</span></td>
                                            <td>2024-05-05</td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="javascript:void(0)" class="text-primary edit">
                                                        <i class="ti ti-eye fs-5"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Row 4 -->
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input" />
                                            </td>
                                            <td>
                                                <div class="user-meta-info">
                                                    <h6 class="user-name mb-0">Mary Njoki</h6>
                                                    <span class="user-work fs-3">TX12348</span>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-secondary">KES 4,000</span></td>
                                            <td><span class="badge bg-danger-subtle text-danger">Card</span></td>
                                            <td><span class="badge bg-danger-subtle text-danger">Failed</span></td>
                                            <td>2024-05-07</td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="javascript:void(0)" class="text-primary edit">
                                                        <i class="ti ti-eye fs-5"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Module Modal -->
    <div class="modal fade" id="addModuleModal" tabindex="-1" role="dialog"
         aria-labelledby="addModuleModalTitle" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header d-flex align-items-center">
                    <h5 class="modal-title">Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="{{ $editId ? 'updateModule' : 'addModule' }}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <input type="text" wire:model.live="title" class="form-control"
                                       placeholder="Module Title"/>
                            </div>
                            <div class="col-md-12 mb-3">
                                        <textarea wire:model.live="description" class="form-control"
                                                  placeholder="Module Description" rows="4"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="d-flex gap-6 m-0">
                            <button type="submit" class="btn btn-success">
                                {{ $editId ? 'Save' : 'Add' }}
                            </button>
                            <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">
                                Discard
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@push('scripts')
    <script>
        window.addEventListener('show-module-modal', () => {
            new bootstrap.Modal(document.getElementById('addModuleModal')).show();
        });

        window.addEventListener('hide-module-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('addModuleModal'))?.hide();
        });
    </script>
@endpush



