<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\PaymentMethodAccountMap;
use Illuminate\Database\Seeder;

class PaymentMethodAccountMapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Maps the free-text values already stored in payments.method to a real
     * Chart of Accounts account.
     *
     * Known Phase 1 limitation: 'credit' is used by CreditService for four
     * different credit types (discount, scholarship, credit_transfer,
     * overpayment_credit) which are not all economically "expenses" — this
     * pragmatically maps all of them to the Student Welfare/Waivers expense
     * account. Recommend a Phase 1.1 refinement that keys the mapping off
     * `credit_type` instead of just `method`.
     */
    public function run(): void
    {
        $cashAccount = ChartOfAccount::where('account_code', '1010')->first();
        $waiverExpenseAccount = ChartOfAccount::where('account_code', '5100')->first();

        if (!$cashAccount || !$waiverExpenseAccount) {
            $this->command?->warn('ChartOfAccountsSeeder must run before PaymentMethodAccountMapSeeder — skipping.');

            return;
        }

        $map = [
            'cash' => $cashAccount->id,
            'mpesa' => $cashAccount->id,
            'bank' => $cashAccount->id,
            'credit' => $waiverExpenseAccount->id,
        ];

        foreach ($map as $method => $accountId) {
            PaymentMethodAccountMap::updateOrCreate(
                ['method' => $method],
                ['account_id' => $accountId, 'is_active' => true]
            );
        }
    }
}
