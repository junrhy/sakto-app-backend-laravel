<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\fnbMenuItem;

class FnbMenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        fnbMenuItem::create([
            'name' => 'Sample FNB Menu Item',
            'price' => 10.99,
            'category' => 'Sample Category',
            'image' => 'https://picsum.photos/400/300?random=1'
        ]);

        fnbMenuItem::create([
            'name' => 'Sample FNB Menu Item 2',
            'price' => 15.99,
            'category' => 'Sample Category 2',
            'image' => 'https://picsum.photos/400/300?random=2'
        ]);

        fnbMenuItem::create([
            'name' => 'Sample FNB Menu Item 3',
            'price' => 20.99,
            'category' => 'Sample Category 3',
            'image' => 'https://picsum.photos/400/300?random=3'
        ]);
    }
}
