<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\SubVoteHead;
use App\Models\VoteHead;
use Illuminate\Database\Seeder;

class VoteHeadSeeder extends Seeder
{
    /**
     * Vote heads mirror the SRS §15 list, mapped 1:1 onto the expense
     * accounts ChartOfAccountsSeeder already created — Salaries (5010) is
     * excluded since payroll isn't petty-cash-funded.
     */
    public function run(): void
    {
        $voteHeads = [
            ['code' => 'ADMIN', 'name' => 'Administration', 'account_code' => '5020'],
            ['code' => 'ACAD', 'name' => 'Academics', 'account_code' => '5030'],
            ['code' => 'MKT', 'name' => 'Marketing', 'account_code' => '5040'],
            ['code' => 'ICT', 'name' => 'ICT', 'account_code' => '5050'],
            ['code' => 'TRANS', 'name' => 'Transport', 'account_code' => '5060'],
            ['code' => 'UTIL', 'name' => 'Utilities', 'account_code' => '5070'],
            ['code' => 'MAINT', 'name' => 'Maintenance', 'account_code' => '5080'],
            ['code' => 'PROC', 'name' => 'Procurement', 'account_code' => '5090'],
            ['code' => 'WELFARE', 'name' => 'Student Welfare', 'account_code' => '5100'],
            ['code' => 'COMP', 'name' => 'Compliance', 'account_code' => '5110'],
        ];

        $subVoteHeads = [
            'ADMIN' => ['Office Supplies', 'Postage & Courier'],
            'ICT' => ['Internet Bills', 'Minor Repairs'],
            'TRANS' => ['Fuel', 'Vehicle Maintenance'],
        ];

        foreach ($voteHeads as $vh) {
            $account = ChartOfAccount::where('account_code', $vh['account_code'])->first();

            if (!$account) {
                continue;
            }

            $voteHead = VoteHead::updateOrCreate(
                ['code' => $vh['code']],
                ['name' => $vh['name'], 'expense_account_id' => $account->id, 'is_active' => true]
            );

            foreach ($subVoteHeads[$vh['code']] ?? [] as $i => $name) {
                SubVoteHead::updateOrCreate(
                    ['vote_head_id' => $voteHead->id, 'code' => $vh['code'] . '-' . ($i + 1)],
                    ['name' => $name, 'is_active' => true]
                );
            }
        }
    }
}
