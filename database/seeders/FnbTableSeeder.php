<?php

namespace Database\Seeders;

use App\Models\FnbTable;
use Illuminate\Database\Seeder;

class FnbTableSeeder extends Seeder
{
    public function run()
    {
        FnbTable::truncate();

        $tables = [
            ['name' => 'Table 1', 'seats' => 4, 'status' => 'available'],
            ['name' => 'Table 2', 'seats' => 2, 'status' => 'available'],
            ['name' => 'Table 3', 'seats' => 6, 'status' => 'available'],
            ['name' => 'Table 4', 'seats' => 4, 'status' => 'available'],
        ];

        foreach ($tables as $table) {
            FnbTable::create($table);
        }
    }
} 