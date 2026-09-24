<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Shop;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferDataSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Shop $shop;
    protected Item $item;
    protected StockTransfer $transfer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::create([
            'shop_name' => 'Legacy Infotech',
            'location'  => 'Branch A',
        ]);

        $this->owner = User::create([
            'name'     => 'Adam Juma',
            'email'    => 'adam@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $category = Category::create(['category_name' => 'Laptops']);

        $this->item = Item::create([
            'category_id' => $category->id,
            'item_name'   => 'HP EliteBook 840 G5',
            'brand'       => 'HP',
            'model'       => '840 G5',
        ]);

        $this->transfer = StockTransfer::create([
            'from_store'    => 'Main Store',
            'to_shop'       => $this->shop->id,
            'approved_by'   => $this->owner->id,
            'transfer_date' => now(),
            'status'        => 'pending_receipt',
        ]);

        StockTransferItem::create([
            'transfer_id'   => $this->transfer->id,
            'item_id'       => $this->item->id,
            'quantity'      => 3,
            'buying_price'  => 500000,
            'selling_price' => 700000,
            'status'        => 'pending',
        ]);
    }

    public function test_datatable_displays_item_name_in_transfers_list()
    {
        $response = $this->actingAs($this->owner)
            ->getJson(route('stock-transfers.data'));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertNotEmpty($json['data']);
        $this->assertStringContainsString('HP EliteBook 840 G5', $json['data'][0]['items']);
        $this->assertStringContainsString('Qty: 3', $json['data'][0]['items']);
    }

    public function test_datatable_can_search_by_item_name()
    {
        $response = $this->actingAs($this->owner)
            ->getJson(route('stock-transfers.data', [
                'search' => ['value' => 'EliteBook'],
            ]));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['recordsFiltered']);
        $this->assertStringContainsString('HP EliteBook 840 G5', $json['data'][0]['items']);

        // Search for something non-existent
        $nonExistentResponse = $this->actingAs($this->owner)
            ->getJson(route('stock-transfers.data', [
                'search' => ['value' => 'NonExistentLaptopXYZ'],
            ]));

        $nonExistentResponse->assertStatus(200);
        $this->assertEquals(0, $nonExistentResponse->json()['recordsFiltered']);
    }
}
