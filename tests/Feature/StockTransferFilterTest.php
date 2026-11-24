<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\Shop;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $secondOwner;
    protected Shop $shopA;
    protected Shop $shopB;
    protected Category $category;
    protected Item $laptop;
    protected Item $phone;
    protected StockTransfer $transfer1;
    protected StockTransfer $transfer2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->owner = User::create([
            'name'     => 'Alice Owner',
            'email'    => 'alice@transfers-test.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->secondOwner = User::create([
            'name'     => 'Bob SystemAdmin',
            'email'    => 'bob@transfers-test.com',
            'password' => bcrypt('password'),
            'role'     => 'system_admin',
        ]);

        $this->shopA = Shop::create([
            'shop_name' => 'Downtown Branch',
            'location'  => 'Downtown',
        ]);

        $this->shopB = Shop::create([
            'shop_name' => 'Uptown Branch',
            'location'  => 'Uptown',
        ]);

        $this->category = Category::create([
            'category_name'     => 'Gadgets',
            'is_admin_category' => false,
        ]);

        $this->laptop = Item::create([
            'item_name'     => 'Dell Precision',
            'brand'         => 'Dell',
            'category_id'   => $this->category->id,
            'is_admin_item' => false,
        ]);

        $this->phone = Item::create([
            'item_name'     => 'Pixel 8',
            'brand'         => 'Google',
            'category_id'   => $this->category->id,
            'is_admin_item' => false,
        ]);

        // Transfer 1: Alice dispatched to Shop A containing Laptop
        $this->transfer1 = StockTransfer::create([
            'from_store'    => 'Main Warehouse',
            'to_shop'       => $this->shopA->id,
            'approved_by'   => $this->owner->id,
            'transfer_date' => now()->toDateString(),
            'status'        => 'pending_receipt',
        ]);

        StockTransferItem::create([
            'transfer_id'   => $this->transfer1->id,
            'item_id'       => $this->laptop->id,
            'quantity'      => 3,
            'buying_price'  => 500000,
            'selling_price' => 700000,
            'status'        => 'pending',
        ]);

        // Transfer 2: Bob dispatched to Shop B containing Phone
        $this->transfer2 = StockTransfer::create([
            'from_store'    => 'Main Warehouse',
            'to_shop'       => $this->shopB->id,
            'approved_by'   => $this->secondOwner->id,
            'transfer_date' => now()->toDateString(),
            'status'        => 'received',
        ]);

        StockTransferItem::create([
            'transfer_id'   => $this->transfer2->id,
            'item_id'       => $this->phone->id,
            'quantity'      => 2,
            'buying_price'  => 800000,
            'selling_price' => 1100000,
            'status'        => 'received',
        ]);
    }

    public function test_transfers_index_loads_filter_dropdowns(): void
    {
        $response = $this->actingAs($this->owner)->get(route('stock-transfers.index'));

        $response->assertStatus(200);
        $response->assertViewHas('items');
        $response->assertViewHas('shops');
        $response->assertViewHas('dispatchers');
        $response->assertSee('Filter by Product / Item');
        $response->assertSee('Destination Shop');
        $response->assertSee('Dispatched By');
    }

    public function test_transfers_data_can_filter_by_item(): void
    {
        // Filter by laptop
        $response = $this->actingAs($this->owner)->getJson(route('stock-transfers.data', [
            'item_id' => $this->laptop->id,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Downtown Branch', $data[0]['destination_shop']);
        $this->assertStringContainsString('Dell Precision', $data[0]['items']);

        // Filter by phone
        $responsePhone = $this->actingAs($this->owner)->getJson(route('stock-transfers.data', [
            'item_id' => $this->phone->id,
        ]));

        $responsePhone->assertStatus(200);
        $dataPhone = $responsePhone->json('data');
        $this->assertCount(1, $dataPhone);
        $this->assertStringContainsString('Uptown Branch', $dataPhone[0]['destination_shop']);
        $this->assertStringContainsString('Pixel 8', $dataPhone[0]['items']);
    }

    public function test_transfers_data_can_filter_by_destination_shop(): void
    {
        // Filter by Shop A
        $response = $this->actingAs($this->owner)->getJson(route('stock-transfers.data', [
            'shop_id' => $this->shopA->id,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Downtown Branch', $data[0]['destination_shop']);

        // Filter by Shop B
        $responseB = $this->actingAs($this->owner)->getJson(route('stock-transfers.data', [
            'shop_id' => $this->shopB->id,
        ]));

        $responseB->assertStatus(200);
        $dataB = $responseB->json('data');
        $this->assertCount(1, $dataB);
        $this->assertStringContainsString('Uptown Branch', $dataB[0]['destination_shop']);
    }

    public function test_transfers_data_can_filter_by_dispatched_by(): void
    {
        // Filter by Alice (owner)
        $response = $this->actingAs($this->owner)->getJson(route('stock-transfers.data', [
            'dispatched_by' => $this->owner->id,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Alice Owner', $data[0]['dispatched_by']);

        // Filter by Bob (system admin)
        $responseBob = $this->actingAs($this->owner)->getJson(route('stock-transfers.data', [
            'dispatched_by' => $this->secondOwner->id,
        ]));

        $responseBob->assertStatus(200);
        $dataBob = $responseBob->json('data');
        $this->assertCount(1, $dataBob);
        $this->assertStringContainsString('Bob SystemAdmin', $dataBob[0]['dispatched_by']);
    }
}
