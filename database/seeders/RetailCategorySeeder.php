<?php

namespace Database\Seeders;

use App\Models\RetailCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class RetailCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('retail_categories')->delete();

        RetailCategory::create([
            'name' => 'Retail'
        ]);
    }
}
