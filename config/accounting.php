<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Core Account Codes
    |--------------------------------------------------------------------------
    |
    | Account codes the automatic GL posting services (JournalPostingService,
    | StudentFeeItemObserver, PaymentPostingService, RefundService,
    | CreditService) resolve against. These must exist in `chart_of_accounts`
    | (see database/seeders/ChartOfAccountsSeeder.php).
    |
    */

    // Debited when a StudentFeeItem is charged; credited when a payment is
    // received, a fee is waived, or a refund is issued.
    'student_debtors_account_code' => '1100',

    // Fallback cash/bank account used when a payment's `method` value has no
    // row in `payment_method_account_map` (see PaymentMethodAccountMapSeeder).
    // Unmapped methods are logged as a warning rather than blocking posting,
    // since the money has already changed hands by the time this runs.
    'default_cash_account_code' => '1010',

    // Debited when a float is issued/topped up to a Petty Cash custodian;
    // credited when a custodian's approved expense is posted (PettyCashService).
    'petty_cash_account_code' => '1020',

    // Credited when a SupplierInvoice is recorded (accrual — the liability is
    // recognized before it's paid); debited when a SupplierPayment settles it
    // (ProcurementService).
    'accounts_payable_account_code' => '2010',

    // Fallback revenue account used when a StudentFeeItem's fee category has
    // no `revenue_account_id` set (see FeeCategoryAccountMapSeeder).
    'default_revenue_account_code' => '4099',

    // Expense account credited-out-of for fee waivers (CreditService::waiveFee()).
    'waiver_expense_account_code' => '5100',

    /*
    |--------------------------------------------------------------------------
    | Opening Balance / Go-Live Cutover
    |--------------------------------------------------------------------------
    |
    | Phase 1 does not replay historical Payment/StudentFeeItem rows into the
    | GL (see plan doc, "Backfill strategy"). Instead, `php artisan
    | accounting:post-opening-balance` posts one entry for the sum of
    | outstanding StudentFeeItem balances as of this date, and real GL
    | posting begins from this date forward. Adjust before running that
    | command for go-live.
    |
    */

    'opening_balance' => [
        // No fallback tied to "today" on purpose — config files can be cached,
        // which would freeze that default at cache-build time. Set explicitly
        // via .env before running the opening-balance command.
        'cutover_date' => env('ACCOUNTING_CUTOVER_DATE', '2026-08-03'),
        'equity_account_code' => '3040',
    ],

];
