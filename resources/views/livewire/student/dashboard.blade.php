<?php

use Livewire\Volt\Component;

new class extends Component {
}; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4 pb-0" data-simplebar>
                <div class="row flex-nowrap">

                    {{-- Courses --}}
                    <div class="col">
                        <div class="card warning-gradient">
                            <div class="card-body text-center px-9 pb-4">
                                <div class="d-flex align-items-center justify-content-center round-48 rounded text-bg-warning flex-shrink-0 mb-3 mx-auto">
                                    <iconify-icon icon="mdi:book-open-page-variant" class="fs-7 text-white"></iconify-icon>
                                </div>
                                <h6 class="fw-normal fs-3 mb-1">Courses</h6>
                                <h4 class="mb-3 d-flex align-items-center justify-content-center gap-1">
                                    3
                                </h4>
                                <a href="javascript:void(0)" class="btn btn-white fs-2 fw-semibold text-nowrap">View Details</a>
                            </div>
                        </div>
                    </div>

                    {{-- Modules --}}
                    <div class="col">
                        <div class="card danger-gradient">
                            <div class="card-body text-center px-9 pb-4">
                                <div class="d-flex align-items-center justify-content-center round-48 rounded text-bg-danger flex-shrink-0 mb-3 mx-auto">
                                    <iconify-icon icon="mdi:file-document-multiple" class="fs-7 text-white"></iconify-icon>
                                </div>
                                <h6 class="fw-normal fs-3 mb-1">Modules</h6>
                                <h4 class="mb-3 d-flex align-items-center justify-content-center gap-1">
                                    64
                                </h4>
                                <a href="javascript:void(0)" class="btn btn-white fs-2 fw-semibold text-nowrap">View Details</a>
                            </div>
                        </div>
                    </div>


                </div> <!-- end row -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="assets/js/apps/contact.js"></script>
@endpush




