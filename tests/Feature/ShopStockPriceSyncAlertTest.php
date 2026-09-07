<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopStockPriceSyncAlertTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;
    protected $admin;
    protected $shop;
    protected $item;
    protected $mainStock;
    protected $shopStock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->shop = Shop::create([
            'shop_name' => 'Main Branch',
            'location'  => 'Kariakoo',
        ]);
        $this->admin = User::factory()->create([
            'role'    => 'shop_admin',
            'shop_id' => $this->shop->id,
        ]);

        $category = Category::create(['category_name' => 'Electronics']);
        $this->item = Item::create([
            'item_name'   => 'Test Smartphone X',
            'category_id' => $category->id,
            'brand'       => 'BrandX',
        ]);

        $this->mainStock = MainStock::create([
            'item_id'            => $this->item->id,
            'buying_price'       => 100000,
            'selling_price'      => 150000,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
        ]);

        $this->shopStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 150000,
            'selling_price'      => 180000,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'is_sellable'        => true,
            'is_price_pending'   => false,
            'is_admin_stock'     => false,
        ]);
    }

    public function test_updating_main_store_selling_price_updates_shop_stock_buying_price_and_sets_pending()
    {
        $this->actingAs($this->owner);

        // Update main store selling price from 150,000 to 170,000
        $response = $this->put(route('main-stock.update', $this->mainStock), [
            'buying_price'  => 100000,
            'selling_price' => 170000,
            'date_received' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('main-stock.index'));

        $this->shopStock->refresh();
        $this->assertEquals(170000, (float)$this->shopStock->buying_price);
        $this->assertTrue($this->shopStock->is_price_pending);
    }

    public function test_pending_price_items_sorted_at_top_in_shop_stock_data_and_alert_in_index()
    {
        // Create second item without price pending
        $category = Category::first();
        $item2 = Item::create([
            'item_name'   => 'Alpha Headphones',
            'category_id' => $category->id,
            'brand'       => 'BrandY',
        ]);
        ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $item2->id,
            'buying_price'       => 20000,
            'selling_price'      => 30000,
            'quantity'           => 10,
            'remaining_quantity' => 10,
            'is_sellable'        => true,
            'is_price_pending'   => false,
            'is_admin_stock'     => false,
        ]);

        // Mark first item as price pending
        $this->shopStock->update(['is_price_pending' => true, 'buying_price' => 170000]);

        $this->actingAs($this->admin);

        // Check index page alert count
        $indexResponse = $this->get(route('shop-stock.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Price Action Needed');
        $indexResponse->assertSee('1 price update(s) required');

        // Check data endpoint sorting - pending item must be first
        $dataResponse = $this->getJson(route('shop-stock.data'));
        $dataResponse->assertStatus(200);
        
        $data = $dataResponse->json('data');
        $this->assertCount(2, $data);
        // First row should be Test Smartphone X (which has pending price), even though Alpha Headphones is alphabetically first
        $this->assertStringContainsString('Test Smartphone X', $data[0]['product']);
        $this->assertStringContainsString('Price Pending', $data[0]['product']);
        $this->assertStringContainsString('Update Selling Price', $data[0]['actions']);
    }

    public function test_shop_admin_can_update_shop_stock_selling_price_and_clear_pending_status()
    {
        $this->shopStock->update(['is_price_pending' => true, 'buying_price' => 170000]);

        $this->actingAs($this->admin);

        $response = $this->patch(route('shop-stock.update-price', $this->shopStock), [
            'selling_price' => 200000,
        ]);

        $response->assertSessionHasNoErrors();

        $this->shopStock->refresh();
        $this->assertEquals(200000, (float)$this->shopStock->selling_price);
        $this->assertFalse($this->shopStock->is_price_pending);
        $this->assertTrue($this->shopStock->is_sellable);
    }

    public function test_zero_remaining_quantity_batches_hidden_from_breakdown_and_buying_price_before_selling_price()
    {
        // Add a zero remaining quantity batch for the same item
        $zeroBatch = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 150000,
            'selling_price'      => 180000,
            'quantity'           => 5,
            'remaining_quantity' => 0,
            'is_sellable'        => true,
            'is_admin_stock'     => false,
        ]);

        $this->actingAs($this->admin);

        $dataResponse = $this->getJson(route('shop-stock.data'));
        $dataResponse->assertStatus(200);

        $data = $dataResponse->json('data');
        $rowActions = $data[0]['actions'];

        // Batch #zeroBatch->id should NOT be present in child table HTML
        $this->assertStringNotContainsString('#' . $zeroBatch->id, $rowActions);
        // Active batch #this->shopStock->id SHOULD be present
        $this->assertStringContainsString('#' . $this->shopStock->id, $rowActions);

        // Breakdown header should show 1 Batch (excluding the 0 remaining batch)
        $this->assertStringContainsString('Stock Batches Breakdown (1 Batch)', $rowActions);

        // Check header columns order: Remaining Qty before Buying Price, then Selling Price
        $this->assertStringContainsString('<th>Remaining Qty</th><th>Buying Price</th><th>Selling Price</th>', $rowActions);
    }
}
