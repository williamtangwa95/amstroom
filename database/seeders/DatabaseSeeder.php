<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default System Settings
        \App\Models\Setting::set('system_name', 'AMSTROOM');
        \App\Models\Setting::set('slogan', 'Technology Innovations');

        // Owner
        $owner = User::create([
            'name'     => 'System Owner',
            'email'    => 'owner@amstroom.com',
            'phone'    => '+255700000001',
            'password' => Hash::make('password'),
            'role'     => 'owner',
            'shop_id'  => null,
        ]);

        // Shops
        $shop1 = Shop::create([
            'shop_name' => 'AmstRoom City Branch',
            'location'  => 'City Center, Dar es Salaam',
            'phone'     => '+255700000100',
            'email'     => 'city@amstroom.com',
            'status'    => 'active',
        ]);

        $shop2 = Shop::create([
            'shop_name' => 'AmstRoom Kariakoo Branch',
            'location'  => 'Kariakoo, Dar es Salaam',
            'phone'     => '+255700000200',
            'email'     => 'kariakoo@amstroom.com',
            'status'    => 'active',
        ]);

        // Employees
        $admin1 = User::create([
            'name'     => 'John Admin',
            'email'    => 'admin1@amstroom.com',
            'phone'    => '+255700000011',
            'password' => Hash::make('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $shop1->id,
        ]);

        $admin2 = User::create([
            'name'     => 'Jane Admin',
            'email'    => 'admin2@amstroom.com',
            'phone'    => '+255700000012',
            'password' => Hash::make('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $shop2->id,
        ]);

        $seller1 = User::create([
            'name'     => 'Alice Seller',
            'email'    => 'seller1@amstroom.com',
            'phone'    => '+255700000021',
            'password' => Hash::make('password'),
            'role'     => 'seller',
            'shop_id'  => $shop1->id,
        ]);

        $seller2 = User::create([
            'name'     => 'Bob Seller',
            'email'    => 'seller2@amstroom.com',
            'phone'    => '+255700000022',
            'password' => Hash::make('password'),
            'role'     => 'seller',
            'shop_id'  => $shop2->id,
        ]);

        // Categories
        $categories = [
            ['category_name' => 'Laptop', 'description' => 'Portable computers and notebooks'],
            ['category_name' => 'Desktop Computer', 'description' => 'Desktop PCs and towers'],
            ['category_name' => 'Monitor', 'description' => 'Display screens and monitors'],
            ['category_name' => 'Keyboard', 'description' => 'Input keyboards wired and wireless'],
            ['category_name' => 'Mouse', 'description' => 'Computer mice optical and laser'],
            ['category_name' => 'USB Flash Drive', 'description' => 'Portable USB storage drives'],
            ['category_name' => 'Printer', 'description' => 'Inkjet and laser printers'],
            ['category_name' => 'Router', 'description' => 'Network routers and WiFi access points'],
            ['category_name' => 'CCTV Camera', 'description' => 'Security cameras and surveillance systems'],
        ];

        $createdCategories = [];
        foreach ($categories as $cat) {
            $createdCategories[$cat['category_name']] = Category::create($cat);
        }

        // Items
        $items = [
            [
                'item_name' => 'HP EliteBook 840 G8',
                'category'  => 'Laptop',
                'spec'      => "Core i7, 16GB RAM, 512GB SSD, 14\" FHD",
                'brand'     => 'HP',
                'model'     => 'EliteBook 840 G8',
                'warranty'  => '1 Year',
                'buy'       => 1200000, 'sell' => 1500000, 'qty' => 20,
            ],
            [
                'item_name' => 'Dell Latitude 5420',
                'category'  => 'Laptop',
                'spec'      => "Core i5, 8GB RAM, 256GB SSD, 14\" FHD",
                'brand'     => 'Dell',
                'model'     => 'Latitude 5420',
                'warranty'  => '1 Year',
                'buy'       => 900000, 'sell' => 1150000, 'qty' => 15,
            ],
            [
                'item_name' => 'Samsung 24" FHD Monitor',
                'category'  => 'Monitor',
                'spec'      => "24 inch, 1920x1080, IPS Panel, HDMI/VGA",
                'brand'     => 'Samsung',
                'model'     => 'S24F350',
                'warranty'  => '2 Years',
                'buy'       => 250000, 'sell' => 320000, 'qty' => 30,
            ],
            [
                'item_name' => 'Logitech MK270 Wireless Combo',
                'category'  => 'Keyboard',
                'spec'      => "Wireless keyboard and mouse combo, 2.4GHz",
                'brand'     => 'Logitech',
                'model'     => 'MK270',
                'warranty'  => '1 Year',
                'buy'       => 45000, 'sell' => 65000, 'qty' => 50,
            ],
            [
                'item_name' => 'Logitech M100 Wired Mouse',
                'category'  => 'Mouse',
                'spec'      => "USB wired optical mouse, 1000 DPI",
                'brand'     => 'Logitech',
                'model'     => 'M100',
                'warranty'  => '1 Year',
                'buy'       => 12000, 'sell' => 18000, 'qty' => 100,
            ],
            [
                'item_name' => 'SanDisk 64GB USB 3.0 Flash',
                'category'  => 'USB Flash Drive',
                'spec'      => "64GB, USB 3.0, Read 100MB/s",
                'brand'     => 'SanDisk',
                'model'     => 'Ultra USB 3.0',
                'warranty'  => '6 Months',
                'buy'       => 18000, 'sell' => 28000, 'qty' => 200,
            ],
            [
                'item_name' => 'Canon PIXMA G3020',
                'category'  => 'Printer',
                'spec'      => "Inkjet, Print/Scan/Copy, WiFi, A4",
                'brand'     => 'Canon',
                'model'     => 'PIXMA G3020',
                'warranty'  => '1 Year',
                'buy'       => 280000, 'sell' => 360000, 'qty' => 10,
            ],
            [
                'item_name' => 'TP-Link Archer AX23',
                'category'  => 'Router',
                'spec'      => "WiFi 6, AX1800, Dual Band",
                'brand'     => 'TP-Link',
                'model'     => 'Archer AX23',
                'warranty'  => '2 Years',
                'buy'       => 95000, 'sell' => 130000, 'qty' => 25,
            ],
            [
                'item_name' => 'Hikvision 2MP Dome Camera',
                'category'  => 'CCTV Camera',
                'spec'      => "2MP, 1080p, IR Night Vision, IP67",
                'brand'     => 'Hikvision',
                'model'     => 'DS-2CD2143G2-I',
                'warranty'  => '2 Years',
                'buy'       => 85000, 'sell' => 120000, 'qty' => 40,
            ],
            [
                'item_name' => 'HP Desktop 280 G9',
                'category'  => 'Desktop Computer',
                'spec'      => "Core i5-12500, 8GB RAM, 256GB SSD",
                'brand'     => 'HP',
                'model'     => 'Desktop 280 G9',
                'warranty'  => '1 Year',
                'buy'       => 850000, 'sell' => 1100000, 'qty' => 12,
            ],
        ];

        foreach ($items as $itemData) {
            $item = Item::create([
                'item_name'      => $itemData['item_name'],
                'category_id'    => $createdCategories[$itemData['category']]->id,
                'specification'  => $itemData['spec'],
                'brand'          => $itemData['brand'],
                'model'          => $itemData['model'],
                'warranty_period'=> $itemData['warranty'],
            ]);

            MainStock::create([
                'item_id'           => $item->id,
                'buying_price'      => $itemData['buy'],
                'selling_price'     => $itemData['sell'],
                'stocked_quantity'  => $itemData['qty'],
                'remaining_quantity'=> $itemData['qty'],
                'date_received'     => now()->subDays(rand(1, 30))->toDateString(),
            ]);
        }
    }
}
