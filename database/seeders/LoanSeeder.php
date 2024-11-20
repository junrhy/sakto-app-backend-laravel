<?php

namespace Database\Seeders;

use App\Models\Loan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class LoanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('loans')->delete();

        Loan::create([
            'borrower_name' => 'John Doe',
            'amount' => 1000,
            'interest_rate' => 5,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'compounding_frequency' => 'monthly',
            'status' => 'active',
            'total_amount' => 1000,
            'paid_amount' => 0,
            'remaining_amount' => 1000,
            'overpayment_balance' => 0,
            'client_identifier' => 'c3de000a-9b28-11ef-8470-0242ac1d0002'
        ]);

        Loan::create([
            'borrower_name' => 'Jane Doe',
            'amount' => 2000,
            'interest_rate' => 5,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'compounding_frequency' => 'monthly',
            'status' => 'active',
            'total_amount' => 2000,
            'paid_amount' => 0,
            'remaining_amount' => 2000,
            'overpayment_balance' => 0,
            'client_identifier' => 'c3de000a-9b28-11ef-8470-0242ac1d0002'
        ]);

        Loan::create([
            'borrower_name' => 'Jim Doe',
            'amount' => 3000,
            'interest_rate' => 5,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'compounding_frequency' => 'monthly',
            'status' => 'active',
            'total_amount' => 3000,
            'paid_amount' => 0,
            'remaining_amount' => 3000,
            'overpayment_balance' => 0,
            'client_identifier' => 'c3de000a-9b28-11ef-8470-0242ac1d0002'
        ]);
    }
}
