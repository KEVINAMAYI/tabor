<?php

use Livewire\Volt\Component;

new class extends Component {
}; ?>

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
                        <a href="javascript:void(0)" id="btn-add-contact" class="btn btn-primary d-flex align-items-center">
                            <i class="ti ti-users text-white me-1 fs-5"></i> Add Contact
                        </a>
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
                        <tr>
                            <th>
                                <div class="form-check text-center">
                                    <input type="checkbox" class="form-check-input primary" />
                                </div>
                            </th>
                            <th>Course</th> <!-- New Column -->
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
                            <td><span class="badge bg-light text-dark">Web Development</span></td>
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
                            <td><span class="badge bg-light text-dark">Graphic Design</span></td>
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
                            <td><span class="badge bg-light text-dark">Data Science</span></td>
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
                            <td><span class="badge bg-light text-dark">Cybersecurity</span></td>
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

@push('scripts')
    <script src="assets/js/apps/contact.js"></script>
@endpush




