<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\InventoryCategory;
use Illuminate\Database\Seeder;

class RetailItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'name' => "Laptop Pro Y4",
                'sku' => "LPT-001",
                'quantity' => 25,
                'price' => 1299.99,
                'images' => [
                    'https://picsum.photos/200/200?random=1',
                    'https://picsum.photos/200/200?random=2'
                ]
            ],
            [
                'name' => "Wireless Mouse M1",
                'sku' => "WM-002",
                'quantity' => 150,
                'price' => 49.99,
                'images' => [
                    'https://picsum.photos/200/200?random=3'
                ]
            ],
            [
                'name' => "4K Monitor 27\"",
                'sku' => "MON-003",
                'quantity' => 45,
                'price' => 399.99,
                'images' => [
                    'https://picsum.photos/200/200?random=4',
                    'https://picsum.photos/200/200?random=5',
                    'https://picsum.photos/200/200?random=6'
                ]
            ],
            [
                'name' => "Mechanical Keyboard",
                'sku' => "KB-004",
                'quantity' => 75,
                'price' => 129.99,
                'images' => []
            ],
            [
                'name' => "USB-C Hub",
                'sku' => "USB-005",
                'quantity' => 200,
                'price' => 79.99,
                'images' => [
                    'https://picsum.photos/200/200?random=7'
                ]
            ],
            [
                'name' => "Wireless Headphones",
                'sku' => "WH-006",
                'quantity' => 60,
                'price' => 199.99,
                'images' => [
                    'https://picsum.photos/200/200?random=8',
                    'https://picsum.photos/200/200?random=9'
                ]
            ],
            [
                'name' => "Gaming Mouse Pad XL",
                'sku' => "MP-007",
                'quantity' => 100,
                'price' => 29.99,
                'images' => [
                    'https://picsum.photos/200/200?random=10'
                ]
            ],
            [
                'name' => "Webcam Pro 4K",
                'sku' => "WC-008",
                'quantity' => 30,
                'price' => 149.99,
                'images' => [
                    'https://picsum.photos/200/200?random=11',
                    'https://picsum.photos/200/200?random=12'
                ]
            ],
            [
                'name' => "External SSD 1TB",
                'sku' => "SSD-009",
                'quantity' => 40,
                'price' => 159.99,
                'images' => [
                    'https://picsum.photos/200/200?random=13'
                ]
            ],
            [
                'name' => "Graphics Card RTX",
                'sku' => "GPU-010",
                'quantity' => 15,
                'price' => 799.99,
                'images' => [
                    'https://picsum.photos/200/200?random=14',
                    'https://picsum.photos/200/200?random=15',
                    'https://picsum.photos/200/200?random=16'
                ]
            ]
        ];

        InventoryCategory::create([
            'id' => 1,
            'name' => 'Retail'
        ]);

        foreach ($items as $item) {
            Inventory::create([
                'name' => $item['name'],
                'sku' => $item['sku'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'images' => json_encode($item['images']),
                'category_id' => 1
            ]);
        }
    }
}
