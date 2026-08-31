<?php

namespace Database\Seeders;

use App\Models\AccountingPeriod;
use App\Models\FinancialYear;
use Illuminate\Database\Seeder;

class AccountingPeriodSeeder extends Seeder
{
    /**
     * Phase 1 shipped the Financial Year/Accounting Period admin UI but no
     * seeder for it — without at least one open period covering "today",
     * JournalPostingService::post() throws on every single call (charges,
     * payments, waivers, refunds, petty cash). Seeds one financial year for
     * the current calendar year with twelve open monthly periods so the GL
     * is immediately usable after a fresh install.
     */
    public function run(): void
    {
        $year = now()->year;

        $financialYear = FinancialYear::updateOrCreate(
            ['name' => "FY{$year}"],
            [
                'start_date' => "{$year}-01-01",
                'end_date' => "{$year}-12-31",
                'active' => true,
            ]
        );

        for ($month = 1; $month <= 12; $month++) {
            $start = sprintf('%d-%02d-01', $year, $month);
            $end = date('Y-m-t', strtotime($start));

            AccountingPeriod::updateOrCreate(
                ['financial_year_id' => $financialYear->id, 'period_number' => $month],
                [
                    'name' => date('F Y', strtotime($start)),
                    'start_date' => $start,
                    'end_date' => $end,
                    'status' => 'open',
                ]
            );
        }
    }
}
