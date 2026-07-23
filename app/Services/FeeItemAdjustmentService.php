<?php

namespace App\Services;

use App\Models\FeeItemAudit;
use App\Models\Payment;
use App\Models\StudentFeeItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Handles corrections to already-generated fee items (description, amount,
 * dates) and keeps payment allocations consistent with the corrected amount:
 *
 * - Shrinking the amount below what has been paid claws the excess back out
 *   of the fee item's most recent allocations onto their payments'
 *   unallocated balance, then re-sweeps those payments through the normal
 *   allocation engine so the freed money flows to other outstanding fees.
 * - Growing the amount reopens the balance and re-sweeps any payments that
 *   already carry an unallocated balance, so the reopened charge is settled
 *   automatically wherever possible.
 */
class FeeItemAdjustmentService
{
    public function __construct(private PaymentPostingService $paymentPostingService) {}

    public function update(StudentFeeItem $feeItem, array $data, ?User $actor = null): StudentFeeItem
    {
        return DB::transaction(function () use ($feeItem, $data, $actor) {
            $oldAmount = (float) $feeItem->amount;
            $newAmount = array_key_exists('amount', $data) ? round((float) $data['amount'], 2) : $oldAmount;

            if ($newAmount < 0) {
                throw new LogicException('Amount cannot be negative.');
            }

            $original = $feeItem->only(['description', 'amount', 'charge_date', 'due_date']);

            if (filled($data['description'] ?? null)) {
                $feeItem->description = $data['description'];
            }

            if (filled($data['charge_date'] ?? null)) {
                $feeItem->charge_date = $data['charge_date'];
            }

            if (array_key_exists('due_date', $data)) {
                $feeItem->due_date = $data['due_date'] ?: null;
            }

            $affectedPayments = collect();

            if (abs($newAmount - $oldAmount) > 0.001) {
                if (in_array($feeItem->status, ['waived', 'cancelled'], true)) {
                    throw new LogicException("Cannot change the amount of a {$feeItem->status} fee item. Description and dates can still be edited.");
                }

                $feeItem->amount = $newAmount;

                if ($newAmount < (float) $feeItem->amount_paid) {
                    $excess = round((float) $feeItem->amount_paid - $newAmount, 2);
                    $affectedPayments = $this->clawBackAllocations($feeItem, $excess);
                    $feeItem->amount_paid = $newAmount;
                }

                $feeItem->balance = max(0, round($feeItem->amount - (float) $feeItem->amount_paid, 2));
                $feeItem->status = $feeItem->balance <= 0
                    ? 'paid'
                    : ((float) $feeItem->amount_paid > 0 ? 'partial' : 'pending');
            }

            $this->recordAudit($feeItem, $original, $data['reason'] ?? null, $actor);

            $feeItem->save();

            $paymentsToSweep = $affectedPayments
                ->merge(
                    Payment::query()
                        ->where('student_id', $feeItem->student_id)
                        ->where('unallocated_balance', '>', 0.01)
                        ->get()
                )
                ->unique('id');

            foreach ($paymentsToSweep as $payment) {
                $this->paymentPostingService->allocateExistingPayment($payment);
            }

            return $feeItem->fresh(['allocations.payment']);
        });
    }

    /**
     * Log which of the editable fields actually changed, who changed them, and why.
     * No-op when nothing changed (e.g. a save with identical values).
     */
    private function recordAudit(StudentFeeItem $feeItem, array $original, ?string $reason, ?User $actor): void
    {
        $dirty = collect($feeItem->getDirty())
            ->only(['description', 'amount', 'charge_date', 'due_date'])
            ->keys();

        if ($dirty->isEmpty()) {
            return;
        }

        $normalize = fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;

        FeeItemAudit::create([
            'student_fee_item_id' => $feeItem->id,
            'user_id' => $actor?->id,
            'action' => 'edited',
            'old_values' => $dirty->mapWithKeys(fn ($f) => [$f => $normalize($original[$f] ?? null)])->all(),
            'new_values' => $dirty->mapWithKeys(fn ($f) => [$f => $normalize($feeItem->{$f})])->all(),
            'reason' => $reason,
        ]);
    }

    /**
     * Pull back `$excess` from this fee item's own allocations (most recent
     * first) and return the excess to each payment's unallocated balance.
     */
    private function clawBackAllocations(StudentFeeItem $feeItem, float $excess): Collection
    {
        $affected = collect();

        $allocations = $feeItem->allocations()->latest('id')->get();

        foreach ($allocations as $allocation) {
            if ($excess <= 0.001) {
                break;
            }

            $reduceBy = min($excess, (float) $allocation->amount_allocated);
            $remaining = round((float) $allocation->amount_allocated - $reduceBy, 2);

            if ($remaining <= 0) {
                $allocation->delete();
            } else {
                $allocation->update(['amount_allocated' => $remaining]);
            }

            $payment = Payment::find($allocation->payment_id);

            if ($payment) {
                $payment->update([
                    'unallocated_balance' => round((float) $payment->unallocated_balance + $reduceBy, 2),
                ]);

                $affected->push($payment);
            }

            $excess = round($excess - $reduceBy, 2);
        }

        return $affected;
    }
}
