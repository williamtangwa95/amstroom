<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\Shop;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferCreateViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Shop $shop;
    protected Category $category;
    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->owner = User::create([
            'name'     => 'Warehouse Owner',
            'email'    => 'owner@transfer-create.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'Kariakoo Branch',
            'location'  => 'Msimbazi Street',
        ]);

        $this->category = Category::create([
            'category_name'     => 'Electronics',
            'is_admin_category' => false,
        ]);

        $this->item = Item::create([
            'item_name'     => 'HP EliteBook 840',
            'brand'         => 'HP',
            'category_id'   => $this->category->id,
            'is_admin_item' => false,
        ]);

        MainStock::create([
            'item_id'            => $this->item->id,
            'stocked_quantity'   => 20,
            'remaining_quantity' => 20,
            'buying_price'       => 400000,
            'selling_price'      => 600000,
            'date_received'      => now(),
        ]);
    }

    public function test_stock_transfers_create_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->owner)->get(route('stock-transfers.create'));

        $response->assertStatus(200);
        $response->assertSee('Direct Stock Assignment');
        $response->assertSee('Target Destination Shop');
        $response->assertSee('Kariakoo Branch');
        $response->assertSee('Msimbazi Street');
        $response->assertSee('HP EliteBook 840');
        $response->assertSee('initShopSelect');
        $response->assertSee('initItemSelect');
        $response->assertSee('Dispatch Stock Now');
    }

    public function test_stock_transfers_store_dispatches_successfully(): void
    {
        $response = $this->actingAs($this->owner)->post(route('stock-transfers.store'), [
            'shop_id' => $this->shop->id,
            'items'   => [
                [
                    'id'  => $this->item->id,
                    'qty' => 5,
                ],
            ],
        ]);

        $response->assertRedirect(route('stock-transfers.index'));
        $this->assertDatabaseHas('stock_transfers', [
            'to_shop'    => $this->shop->id,
            'status'     => 'pending_receipt',
            'from_store' => 'Main Warehouse',
        ]);
    }
}
