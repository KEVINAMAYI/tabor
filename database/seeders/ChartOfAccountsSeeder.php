<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Flat leaf accounts (no header/group rows) matching the SRS's Chart of
     * Accounts (§5), plus "Opening Balance Equity" — needed by the
     * accounting:post-opening-balance go-live command — which the SRS
     * doesn't list but every GL go-live needs.
     */
    public function run(): void
    {
        $accounts = [
            // Assets
            ['account_code' => '1010', 'name' => 'Cash at Bank', 'account_type' => 'asset', 'normal_balance' => 'dr'],
            ['account_code' => '1020', 'name' => 'Petty Cash', 'account_type' => 'asset', 'normal_balance' => 'dr'],
            ['account_code' => '1100', 'name' => 'Student Debtors', 'account_type' => 'asset', 'normal_balance' => 'dr'],
            ['account_code' => '1110', 'name' => 'Accounts Receivable (Other)', 'account_type' => 'asset', 'normal_balance' => 'dr'],
            ['account_code' => '1200', 'name' => 'Inventory', 'account_type' => 'asset', 'normal_balance' => 'dr'],
            ['account_code' => '1300', 'name' => 'Fixed Assets', 'account_type' => 'asset', 'normal_balance' => 'dr'],
            ['account_code' => '1400', 'name' => 'Prepaid Expenses', 'account_type' => 'asset', 'normal_balance' => 'dr'],

            // Liabilities
            ['account_code' => '2010', 'name' => 'Accounts Payable', 'account_type' => 'liability', 'normal_balance' => 'cr'],
            ['account_code' => '2020', 'name' => 'Payroll Liabilities', 'account_type' => 'liability', 'normal_balance' => 'cr'],
            ['account_code' => '2030', 'name' => 'Taxes Payable', 'account_type' => 'liability', 'normal_balance' => 'cr'],
            ['account_code' => '2040', 'name' => 'Accrued Expenses', 'account_type' => 'liability', 'normal_balance' => 'cr'],

            // Equity
            ['account_code' => '3010', 'name' => 'Capital Fund', 'account_type' => 'equity', 'normal_balance' => 'cr'],
            ['account_code' => '3020', 'name' => 'Retained Earnings', 'account_type' => 'equity', 'normal_balance' => 'cr'],
            ['account_code' => '3030', 'name' => 'Current Year Surplus', 'account_type' => 'equity', 'normal_balance' => 'cr'],
            ['account_code' => '3040', 'name' => 'Opening Balance Equity', 'account_type' => 'equity', 'normal_balance' => 'cr'],

            // Revenue
            ['account_code' => '4010', 'name' => 'Tuition Fees', 'account_type' => 'revenue', 'normal_balance' => 'cr'],
            ['account_code' => '4020', 'name' => 'Registration Fees', 'account_type' => 'revenue', 'normal_balance' => 'cr'],
            ['account_code' => '4030', 'name' => 'Examination Fees', 'account_type' => 'revenue', 'normal_balance' => 'cr'],
            ['account_code' => '4040', 'name' => 'Hostel Fees', 'account_type' => 'revenue', 'normal_balance' => 'cr'],
            ['account_code' => '4050', 'name' => 'Uniform Sales', 'account_type' => 'revenue', 'normal_balance' => 'cr'],
            ['account_code' => '4060', 'name' => 'Book Sales', 'account_type' => 'revenue', 'normal_balance' => 'cr'],
            ['account_code' => '4070', 'name' => 'Consultancy Income', 'account_type' => 'revenue', 'normal_balance' => 'cr'],
            ['account_code' => '4080', 'name' => 'Short Courses Income', 'account_type' => 'revenue', 'normal_balance' => 'cr'],
            ['account_code' => '4090', 'name' => 'Donations', 'account_type' => 'revenue', 'normal_balance' => 'cr'],
            ['account_code' => '4099', 'name' => 'Other Income', 'account_type' => 'revenue', 'normal_balance' => 'cr'],

            // Expenses
            ['account_code' => '5010', 'name' => 'Salaries', 'account_type' => 'expense', 'normal_balance' => 'dr'],
            ['account_code' => '5020', 'name' => 'Administration', 'account_type' => 'expense', 'normal_balance' => 'dr'],
            ['account_code' => '5030', 'name' => 'Academics', 'account_type' => 'expense', 'normal_balance' => 'dr'],
            ['account_code' => '5040', 'name' => 'Marketing', 'account_type' => 'expense', 'normal_balance' => 'dr'],
            ['account_code' => '5050', 'name' => 'ICT', 'account_type' => 'expense', 'normal_balance' => 'dr'],
            ['account_code' => '5060', 'name' => 'Transport', 'account_type' => 'expense', 'normal_balance' => 'dr'],
            ['account_code' => '5070', 'name' => 'Utilities', 'account_type' => 'expense', 'normal_balance' => 'dr'],
            ['account_code' => '5080', 'name' => 'Maintenance', 'account_type' => 'expense', 'normal_balance' => 'dr'],
            ['account_code' => '5090', 'name' => 'Procurement', 'account_type' => 'expense', 'normal_balance' => 'dr'],
            ['account_code' => '5100', 'name' => 'Student Welfare (Scholarships & Waivers)', 'account_type' => 'expense', 'normal_balance' => 'dr'],
            ['account_code' => '5110', 'name' => 'Compliance', 'account_type' => 'expense', 'normal_balance' => 'dr'],
        ];

        foreach ($accounts as $account) {
            ChartOfAccount::updateOrCreate(
                ['account_code' => $account['account_code']],
                $account
            );
        }
    }
}
