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

@push('scripts')
    <script src="assets/js/apps/contact.js"></script>
@endpush



