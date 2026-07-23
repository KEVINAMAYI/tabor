{{-- ═══════════════════════════════════════════
     ENROLL MODAL
═══════════════════════════════════════════ --}}
<div wire:ignore.self class="modal fade" id="enrollModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title">Add Enrollment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Course</label>
                    <select class="form-select" wire:model.live="course_id">
                        <option value="">Select course</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->title }} - {{ $course->level }}</option>
                        @endforeach
                    </select>
                    @error('course_id') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                @if ($course_id)
                    <div class="alert alert-light border rounded-3 mt-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-semibold">Estimated Fees</div>
                                <span class="text-muted small">edit amounts, remove items, or add a custom charge before saving</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addCustomEnrollmentFeeItem">
                                <i class="ti ti-plus"></i> Add Fee Item
                            </button>
                        </div>

                        @if (count($enrollmentChargePreview))
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
                                        @foreach ($enrollmentChargePreview as $index => $item)
                                            <tr>
                                                <td>
                                                    @if (empty($item['fee_definition_id']))
                                                        <input type="text" placeholder="Fee description"
                                                            wire:model.live.debounce.300ms="enrollmentChargePreview.{{ $index }}.description"
                                                            class="form-control form-control-sm">
                                                    @else
                                                        {{ $item['description'] }}
                                                    @endif
                                                </td>
                                                <td class="text-muted small">{{ $item['timing'] }}</td>
                                                <td>
                                                    <input type="number" step="0.01" min="0"
                                                        wire:model.live.debounce.300ms="enrollmentChargePreview.{{ $index }}.amount"
                                                        class="form-control form-control-sm text-end">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-danger px-2"
                                                        wire:click="removeEnrollmentFeeItem({{ $index }})" title="Remove this fee item">
                                                        <i class="ti ti-x"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td colspan="2">Total</td>
                                            <td class="text-end">KES {{ number_format($this->enrollmentFeeTotal, 2) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Admission Date</label>
                    <input type="date" class="form-control" wire:model="admission_date">
                    @error('admission_date') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model="enrollment_status">
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="deferred">Deferred</option>
                        <option value="withdrawn">Withdrawn</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="course_completed">Course Completed</option>
                        <option value="pending_graduation">Pending Graduation</option>
                        <option value="graduated">Graduated</option>
                    </select>
                    @error('enrollment_status') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" wire:click="saveEnrollment">Save Enrollment</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     EDIT ENROLLMENT MODAL
═══════════════════════════════════════════ --}}
<div wire:ignore.self class="modal fade" id="editEnrollmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title">Edit Enrollment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Course</label>
                    <select class="form-select" wire:model="edit_course_id">
                        <option value="">Select course</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->title }} - {{ $course->level }}</option>
                        @endforeach
                    </select>
                    @error('edit_course_id') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Admission Date</label>
                    <input type="date" class="form-control" wire:model="edit_admission_date">
                    @error('edit_admission_date') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model="edit_enrollment_status">
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="deferred">Deferred</option>
                        <option value="withdrawn">Withdrawn</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="course_completed">Course Completed</option>
                        <option value="pending_graduation">Pending Graduation</option>
                        <option value="graduated">Graduated</option>
                    </select>
                    @error('edit_enrollment_status') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" wire:click="updateEnrollment">Update Enrollment</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     GENERATE CHARGES MODAL
═══════════════════════════════════════════ --}}
<div class="modal fade" id="generateChargesModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-semibold mb-1">Generate Initial Charges</h5>
                    <small class="text-muted">This will post starting charges for the selected enrollment.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if ($selectedEnrollment)
                    <div class="p-3 rounded-3 bg-light mb-3">
                        <div class="small text-muted mb-1">Enrollment</div>
                        <div class="fw-semibold">{{ $selectedEnrollment->course->title }} - {{ $selectedEnrollment->course->level }}</div>
                        <div class="small text-muted mt-1">
                            {{ optional($selectedEnrollment->admission_date)->format('d M Y') ?? '-' }}
                            &bull;
                            {{ $selectedEnrollment->assignedStartTrimester->name ?? '-' }}
                        </div>
                    </div>
                @endif
                <div class="alert alert-warning border-0 rounded-3 mb-0">
                    <div class="fw-semibold mb-2">Charges to be generated</div>
                    @if (count($chargePreview))
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr class="text-muted small"><th>Scope</th><th>Fee</th><th class="text-end">Amount</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($chargePreview as $item)
                                    <tr>
                                        <td class="small text-muted">{{ $item['type'] }}</td>
                                        <td class="fw-medium">{{ $item['name'] }}</td>
                                        <td class="text-end fw-semibold">KES {{ number_format($item['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2" class="text-end">Total</th>
                                    <th class="text-end">KES {{ number_format(collect($chargePreview)->sum('amount'), 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    @else
                        <div class="text-muted small">No new charges to generate.</div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary rounded-3"
                        wire:click="generateInitialCharges" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="generateInitialCharges">
                        <i class="ti ti-receipt me-1"></i> Generate Charges
                    </span>
                    <span wire:loading wire:target="generateInitialCharges">
                        <i class="ti ti-loader me-1"></i> Processing...
                    </span>
                </button>
                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     POST PAYMENT MODAL
═══════════════════════════════════════════ --}}
<div class="modal fade" id="paymentModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-semibold mb-1">Post Payment</h5>
                    <small class="text-muted">Record a payment and allocate it to outstanding charges.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form wire:submit.prevent="savePayment">
                <div class="modal-body">
                    @if ($selectedEnrollment)
                        <div class="p-3 rounded-3 bg-light mb-4">
                            <div class="fw-semibold">{{ $selectedEnrollment->course->title }} - {{ $selectedEnrollment->course->level }}</div>
                            <div class="small text-muted mt-1">
                                {{ $student->first_name }} {{ $student->last_name }}
                                &bull;
                                TTI/{{ $student->admission_number }}/{{ $selectedEnrollment->course->code }}/{{ $student->created_at->format('Y') }}
                            </div>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Payment Date</label>
                            <input type="date" class="form-control rounded-3" wire:model="payment_date">
                            @error('payment_date') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control rounded-3" wire:model="payment_amount" placeholder="0.00">
                            @error('payment_amount') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Method</label>
                            <select class="form-select rounded-3" wire:model="payment_method">
                                <option value="">Select method</option>
                                <option value="cash">Cash</option>
                                <option value="mpesa">M-PESA</option>
                                <option value="bank">Bank</option>
                                <option value="card">Card</option>
                                <option value="other">Other</option>
                            </select>
                            @error('payment_method') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Reference No</label>
                            <input type="text" class="form-control rounded-3" wire:model="payment_reference" placeholder="Transaction reference">
                            @error('payment_reference') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Receipt No</label>
                            <input type="text" class="form-control rounded-3" wire:model="payment_receipt_no" placeholder="Receipt number">
                            @error('payment_receipt_no') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Notes</label>
                        <textarea class="form-control rounded-3" rows="2" wire:model="payment_notes" placeholder="Optional notes"></textarea>
                    </div>
                    <div class="alert alert-info border-0 rounded-3 mb-0 small">
                        Payment will be allocated to the oldest outstanding fee items first.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary rounded-3"><i class="ti ti-cash me-1"></i> Save Payment</button>
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     APPLY DISCOUNT MODAL
═══════════════════════════════════════════ --}}
<div class="modal fade" id="discountModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-semibold mb-1">Apply Student Discount</h5>
                    <small class="text-muted">Post a discount against a specific trimester progression.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form wire:submit.prevent="saveDiscount">
                <div class="modal-body">
                    @if ($selectedEnrollment)
                        <div class="p-3 rounded-3 bg-light mb-4">
                            <div class="fw-semibold">{{ $selectedEnrollment->course?->title }} - {{ $selectedEnrollment->course?->level }}</div>
                            <div class="small text-muted mt-1">{{ $student->first_name }} {{ $student->last_name }}</div>
                        </div>
                    @endif
                    <div class="alert alert-warning border-0 rounded-3 small">
                        This creates a credit entry against the selected progression. It does not alter payments or allocations.
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-medium">Trimester Progression</label>
                            <select class="form-select rounded-3" wire:model="discount_progression_id">
                                <option value="">Select progression</option>
                                @foreach ($discountProgressions as $prog)
                                    <option value="{{ $prog->id }}">
                                        T{{ $prog->trimester_sequence }} -{{ $prog->trimester?->name ?? 'Trimester' }}
                                        {{ $prog->trimester?->academicYear?->name }} -{{ ucfirst($prog->status) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('discount_progression_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Discount Date</label>
                            <input type="date" class="form-control rounded-3" wire:model="discount_date">
                            @error('discount_date') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Discount Amount</label>
                            <input type="number" step="0.01" min="1" class="form-control rounded-3" wire:model="discount_amount" placeholder="0.00">
                            @error('discount_amount') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-medium">Reason / Description</label>
                            <input type="text" class="form-control rounded-3" wire:model="discount_description"
                                   placeholder="e.g. Scholarship discount, management waiver, bursary...">
                            @error('discount_description') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning rounded-3"
                            wire:loading.attr="disabled" wire:target="saveDiscount">
                        <span wire:loading.remove wire:target="saveDiscount">
                            <i class="ti ti-discount-2 me-1"></i> Apply Discount
                        </span>
                        <span wire:loading wire:target="saveDiscount">Processing...</span>
                    </button>
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     DEFERRAL MODAL
═══════════════════════════════════════════ --}}
<div class="modal fade" id="deferralModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-semibold mb-1">Defer Student</h5>
                    <small class="text-muted">Select the trimester the student will resume in.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form wire:submit.prevent="saveDeferral">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Resume In Trimester <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3" wire:model="deferral_resume_trimester_id">
                            <option value="">— Select trimester —</option>
                            @foreach ($trimesters->where('status', 'upcoming') as $trimester)
                                <option value="{{ $trimester->id }}">
                                    {{ $trimester->name }} ({{ optional($trimester->start_date)->format('d M Y') }})
                                </option>
                            @endforeach
                        </select>
                        @error('deferral_resume_trimester_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Reason for Deferral <span class="text-danger">*</span></label>
                        <textarea class="form-control rounded-3" rows="3" wire:model="deferral_reason"
                                  placeholder="e.g. Medical reasons, financial difficulties..."></textarea>
                        @error('deferral_reason')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-secondary rounded-3"
                            wire:loading.attr="disabled" wire:target="saveDeferral">
                        <span wire:loading.remove wire:target="saveDeferral">
                            <i class="ti ti-clock-pause me-1"></i> Approve Deferral
                        </span>
                        <span wire:loading wire:target="saveDeferral">Processing...</span>
                    </button>
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     WAIVER MODAL
═══════════════════════════════════════════ --}}
<div class="modal fade" id="waiverModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-semibold mb-1">Waive Fee</h5>
                    <small class="text-muted">Waive all or part of a fee item balance.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form wire:submit.prevent="saveWaiver">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Waive Amount (KES) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control rounded-3" wire:model="waiver_amount" min="0.01" step="0.01" />
                        @error('waiver_amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control rounded-3" rows="3" wire:model="waiver_reason"
                                  placeholder="e.g. Scholarship, financial hardship..."></textarea>
                        @error('waiver_reason') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger rounded-3"
                            wire:loading.attr="disabled" wire:target="saveWaiver">
                        <span wire:loading.remove wire:target="saveWaiver">
                            <i class="ti ti-circle-x me-1"></i> Waive Fee
                        </span>
                        <span wire:loading wire:target="saveWaiver">Processing...</span>
                    </button>
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     EDIT FEE ITEM MODAL
═══════════════════════════════════════════ --}}
<div class="modal fade" id="editFeeItemModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-semibold mb-1">Edit Fee Item</h5>
                    <small class="text-muted">Correct a charge. Amount changes automatically re-flow payment allocations.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form wire:submit.prevent="updateFeeItem">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Description <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" wire:model="edit_fee_description">
                        @error('edit_fee_description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Amount (KES) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control rounded-3" wire:model="edit_fee_amount">
                            @error('edit_fee_amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Charge Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control rounded-3" wire:model="edit_fee_charge_date">
                            @error('edit_fee_charge_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Due Date</label>
                        <input type="date" class="form-control rounded-3" wire:model="edit_fee_due_date">
                        @error('edit_fee_due_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-medium">Reason for change</label>
                        <textarea class="form-control rounded-3" rows="2" wire:model="edit_fee_reason"
                                  placeholder="Required when changing the amount, e.g. Board-approved fee reduction"></textarea>
                        @error('edit_fee_reason') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="alert alert-info border-0 rounded-3 mt-3 mb-0 small">
                        <i class="ti ti-info-circle me-1"></i>
                        Reducing the amount below what's already paid pulls the excess back onto the payment's
                        unallocated balance and re-applies it to other outstanding fees. Waived or cancelled
                        items cannot have their amount changed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary rounded-3"
                            wire:loading.attr="disabled" wire:target="updateFeeItem">
                        <span wire:loading.remove wire:target="updateFeeItem">
                            <i class="ti ti-device-floppy me-1"></i> Save Changes
                        </span>
                        <span wire:loading wire:target="updateFeeItem">Processing...</span>
                    </button>
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     REFUND MODAL
═══════════════════════════════════════════ --}}
<div class="modal fade" id="refundModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-semibold mb-1">Process Refund</h5>
                    <small class="text-muted">Reverse a payment and restore fee item balances.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form wire:submit.prevent="saveRefund">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Refund Amount (KES) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control rounded-3" wire:model="refund_amount" min="0.01" step="0.01" />
                        @error('refund_amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Refund Method <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3" wire:model="refund_method">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="mpesa">M-Pesa</option>
                        </select>
                        @error('refund_method') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control rounded-3" rows="3" wire:model="refund_reason"
                                  placeholder="e.g. Overpayment, withdrawal refund..."></textarea>
                        @error('refund_reason') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger rounded-3"
                            wire:loading.attr="disabled" wire:target="saveRefund">
                        <span wire:loading.remove wire:target="saveRefund">
                            <i class="ti ti-arrow-back-up me-1"></i> Process Refund
                        </span>
                        <span wire:loading wire:target="saveRefund">Processing...</span>
                    </button>
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     REPEAT TRIMESTER MODAL
═══════════════════════════════════════════ --}}
<div class="modal fade" id="repeatTrimesterModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-semibold mb-1">Repeat Trimester</h5>
                    <small class="text-muted">Mark this progression as repeated and create a new one.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form wire:submit.prevent="saveRepeatTrimester">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Repeat In Trimester <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3" wire:model="repeat_trimester_id">
                            <option value="">— Select trimester —</option>
                            @foreach ($trimesters->whereIn('status', ['upcoming', 'active']) as $trimester)
                                <option value="{{ $trimester->id }}">
                                    {{ $trimester->name }} ({{ optional($trimester->start_date)->format('d M Y') }})
                                </option>
                            @endforeach
                        </select>
                        @error('repeat_trimester_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control rounded-3" rows="3" wire:model="repeat_reason"
                                  placeholder="e.g. Failed assessments, insufficient attendance..."></textarea>
                        @error('repeat_reason') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning rounded-3"
                            wire:loading.attr="disabled" wire:target="saveRepeatTrimester">
                        <span wire:loading.remove wire:target="saveRepeatTrimester">
                            <i class="ti ti-refresh me-1"></i> Repeat Trimester
                        </span>
                        <span wire:loading wire:target="saveRepeatTrimester">Processing...</span>
                    </button>
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     EDIT PROGRESSION MODAL
═══════════════════════════════════════════ --}}
<div class="modal fade" id="editProgressionModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-semibold mb-1">Edit Progression</h5>
                    <small class="text-muted">Correct the linked trimester and start/end dates.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form wire:submit.prevent="updateProgressionDates">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Trimester <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3" wire:model="edit_progression_trimester_id">
                            <option value="">— Select trimester —</option>
                            @foreach ($trimesters as $trimester)
                                <option value="{{ $trimester->id }}">
                                    {{ $trimester->name }} {{ $trimester->academicYear?->name }}
                                    ({{ optional($trimester->start_date)->format('d M Y') }} - {{ optional($trimester->end_date)->format('d M Y') }})
                                </option>
                            @endforeach
                        </select>
                        @error('edit_progression_trimester_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Started On</label>
                            <input type="date" class="form-control rounded-3" wire:model="edit_progression_started_at">
                            @error('edit_progression_started_at') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Completed On</label>
                            <input type="date" class="form-control rounded-3" wire:model="edit_progression_completed_at">
                            @error('edit_progression_completed_at') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-medium">Notes</label>
                        <textarea class="form-control rounded-3" rows="2" wire:model="edit_progression_notes"
                                  placeholder="Reason for correction (optional)"></textarea>
                        @error('edit_progression_notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary rounded-3"
                            wire:loading.attr="disabled" wire:target="updateProgressionDates">
                        <span wire:loading.remove wire:target="updateProgressionDates">
                            <i class="ti ti-calendar-time me-1"></i> Save Changes
                        </span>
                        <span wire:loading wire:target="updateProgressionDates">Processing...</span>
                    </button>
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     FEE ITEM HISTORY MODAL
═══════════════════════════════════════════ --}}
<div class="modal fade" id="feeItemHistoryModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-semibold mb-1">Fee Item History</h5>
                    <small class="text-muted">
                        @if ($historyFeeItem)
                            {{ $historyFeeItem->description }} &bull; current amount KES {{ number_format($historyFeeItem->amount, 0) }}
                        @else
                            Change log for this charge.
                        @endif
                    </small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if ($historyFeeItem && $historyFeeItem->audits->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>When</th>
                                    <th>By</th>
                                    <th>Action</th>
                                    <th>Change</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($historyFeeItem->audits as $audit)
                                    <tr>
                                        <td class="text-muted text-nowrap" style="font-size:.78rem;">
                                            {{ $audit->created_at->format('d M Y H:i') }}
                                        </td>
                                        <td style="font-size:.8rem;">{{ $audit->user?->name ?? 'System' }}</td>
                                        <td>
                                            <span class="badge {{ $audit->action === 'waived' ? 'badge-waived' : 'bg-primary-subtle text-primary' }}" style="font-size:.68rem;">
                                                {{ ucfirst($audit->action) }}
                                            </span>
                                        </td>
                                        <td style="font-size:.8rem;">{{ $audit->change_summary }}</td>
                                        <td class="text-muted" style="font-size:.78rem;">{{ $audit->reason ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="ti ti-history" style="font-size:2rem; display:block; margin-bottom:.5rem;"></i>
                        No changes recorded for this fee item.
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
