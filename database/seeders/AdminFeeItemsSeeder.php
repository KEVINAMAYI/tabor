<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AdminFeeItem;

class AdminFeeItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fees = [
            [
                'code' => 'REGISTRATION',
                'name' => 'Registration Fee',
                'default_amount' => 1000,
                'is_active' => true,
            ],
            [
                'code' => 'STUDENT_ID',
                'name' => 'Student ID Fee',
                'default_amount' => 500,
                'is_active' => true,
            ],
            [
                'code' => 'STATIONERY',
                'name' => 'Stationery Fee',
                'default_amount' => 1500,
                'is_active' => true,
            ],
            [
                'code' => 'CAUTION',
                'name' => 'Caution Fee',
                'default_amount' => 2000,
                'is_active' => true,
            ],
        ];

        foreach ($fees as $fee) {
            AdminFeeItem::updateOrCreate(
                ['code' => $fee['code']],
                $fee
            );
        }
    }
}
