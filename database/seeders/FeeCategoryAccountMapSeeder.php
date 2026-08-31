<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\FeeCategory;
use Illuminate\Database\Seeder;

class FeeCategoryAccountMapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Maps the 5 real fee_categories rows (see FeeCategorySeeder) to a
     * revenue account, so StudentFeeItemObserver knows which revenue account
     * to credit when a charge is booked.
     *
     * FLAGGED FOR FINANCE SIGN-OFF: these mappings are a reasonable best
     * guess (e.g. 'exam' -> Examination Fees), not a confirmed business
     * decision. Review before go-live.
     */
    public function run(): void
    {
        $accountCodes = ChartOfAccount::pluck('id', 'account_code');

        if ($accountCodes->isEmpty()) {
            $this->command?->warn('ChartOfAccountsSeeder must run before FeeCategoryAccountMapSeeder — skipping.');

            return;
        }

        $map = [
            'tuition' => '4010',        // Tuition Fees
            'student_once' => '4020',   // Registration Fees
            'course_charge' => '4010',  // Tuition Fees
            'exam' => '4030',           // Examination Fees
            'adjustment' => '4099',     // Other Income
        ];

        foreach ($map as $categoryCode => $accountCode) {
            $accountId = $accountCodes->get($accountCode);

            if (!$accountId) {
                continue;
            }

            FeeCategory::where('code', $categoryCode)->update(['revenue_account_id' => $accountId]);
        }
    }
}
