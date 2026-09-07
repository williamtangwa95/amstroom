<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Shop;
use App\Models\ShopStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopAdminStockActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected Shop $shop;
    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'Test Shop 1',
            'location'  => 'Dar es Salaam',
        ]);

        $this->owner = User::create([
            'name'     => 'Main Owner',
            'email'    => 'owner@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->admin = User::create([
            'name'     => 'Shop Admin',
            'email'    => 'admin@example.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shop->id,
        ]);

        $category = Category::create(['category_name' => 'Electronics']);
        $this->item = Item::create([
            'item_name'     => 'Dell Laptop',
            'category_id'   => $category->id,
            'brand'         => 'Dell',
            'model'         => 'XPS',
        ]);
    }

    public function test_shop_admin_deleting_admin_stock_deletes_permanently_without_notifying_owner()
    {
        $adminStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 500,
            'selling_price'      => 800,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'date_received'      => now()->toDateString(),
            'is_admin_stock'     => true,
            'is_sellable'        => true,
        ]);

        $this->actingAs($this->admin);

        $response = $this->delete(route('shop-stock.destroy', $adminStock));

        $response->assertRedirect(route('shop-stock.index', ['shop_id' => $this->shop->id]));
        $response->assertSessionHas('success', 'Admin stock batch "' . $this->item->item_name . '" deleted permanently.');

        // Stock should be deleted permanently
        $this->assertDatabaseMissing('shop_stocks', [
            'id' => $adminStock->id,
        ]);

        // Owner should NOT receive any notification
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->owner->id,
            'title'   => 'Stock Deletion Request',
        ]);
    }

    public function test_shop_admin_bulk_deleting_admin_stock_deletes_permanently_without_notifying_owner()
    {
        $adminStock1 = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 500,
            'selling_price'      => 800,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'date_received'      => now()->toDateString(),
            'is_admin_stock'     => true,
            'is_sellable'        => true,
        ]);

        $adminStock2 = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 600,
            'selling_price'      => 900,
            'quantity'           => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
            'is_admin_stock'     => true,
            'is_sellable'        => true,
        ]);

        $this->actingAs($this->admin);

        $response = $this->deleteJson(route('shop-stock.bulk-destroy'), [
            'ids' => [$adminStock1->id, $adminStock2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'deleted_count' => 2,
        ]);

        $this->assertDatabaseMissing('shop_stocks', ['id' => $adminStock1->id]);
        $this->assertDatabaseMissing('shop_stocks', ['id' => $adminStock2->id]);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->owner->id,
            'title'   => 'Bulk Stock Deletion Request',
        ]);
    }

    public function test_shop_admin_updating_admin_stock_updates_directly_without_notifying_owner()
    {
        $adminStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 500,
            'selling_price'      => 800,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'date_received'      => now()->toDateString(),
            'is_admin_stock'     => true,
            'is_sellable'        => true,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('shop-stock.update', $adminStock), [
            'buying_price'       => 600,
            'selling_price'      => 900,
            'remaining_quantity' => 8,
            'date_received'      => now()->toDateString(),
        ]);

        $response->assertRedirect(route('shop-stock.index', ['shop_id' => $this->shop->id]));
        $response->assertSessionHas('success', 'Admin stock updated successfully.');

        $this->assertDatabaseHas('shop_stocks', [
            'id'                 => $adminStock->id,
            'buying_price'       => 600,
            'selling_price'      => 900,
            'remaining_quantity' => 8,
            'is_price_pending'   => false,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->owner->id,
            'title'   => 'Shop Stock Change Pending Approval',
        ]);
    }

    public function test_shop_admin_cannot_delete_admin_stock_when_sales_occurred()
    {
        // Batch with initial Qty = 10, remaining Qty = 7 (3 sold)
        $partiallySoldStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 500,
            'selling_price'      => 800,
            'quantity'           => 10,
            'remaining_quantity' => 7,
            'date_received'      => now()->toDateString(),
            'is_admin_stock'     => true,
            'is_sellable'        => true,
        ]);

        $this->actingAs($this->admin);

        // 1. Direct destroy route should fail with session error
        $response = $this->delete(route('shop-stock.destroy', $partiallySoldStock));
        $response->assertRedirect(route('shop-stock.index', ['shop_id' => $this->shop->id]));
        $response->assertSessionHas('error', 'Cannot delete stock batch because some items have already been sold or modified.');
        $this->assertDatabaseHas('shop_stocks', ['id' => $partiallySoldStock->id]);

        // 2. Bulk destroy route should be blocked
        $bulkResponse = $this->deleteJson(route('shop-stock.bulk-destroy'), [
            'ids' => [$partiallySoldStock->id],
        ]);
        $bulkResponse->assertStatus(422);
        $bulkResponse->assertJsonFragment([
            'success' => false,
        ]);
        $this->assertDatabaseHas('shop_stocks', ['id' => $partiallySoldStock->id]);

        // 3. DataTables data response should render disabled delete button
        $dataResponse = $this->getJson(route('shop-stock.data', ['shop_id' => $this->shop->id]));
        $dataResponse->assertStatus(200);
        $data = $dataResponse->json('data.0');
        $this->assertStringContainsString('disabled', $data['actions']);
        $this->assertStringContainsString('Cannot delete stock batch because sales have already occurred', $data['actions']);
    }

    public function test_finished_stock_actions_renders_request_stock_for_owner_stock_and_quick_restock_for_admin_stock()
    {
        // Finished owner stock
        $ownerFinishedStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 500,
            'selling_price'      => 800,
            'quantity'           => 10,
            'remaining_quantity' => 0,
            'date_received'      => now()->toDateString(),
            'is_admin_stock'     => false,
            'is_sellable'        => true,
        ]);

        $this->actingAs($this->admin);

        $response = $this->getJson(route('shop-stock.finished-data', ['shop_id' => $this->shop->id]));
        $response->assertStatus(200);
        $data = $response->json('data.0');

        // Owner stock finished item for shop admin should show "Request Stock" button
        $this->assertStringContainsString('Request Stock', $data['actions']);
        $this->assertStringContainsString(route('stock-requests.create', ['item_id' => $this->item->id]), $data['actions']);

        // Now test for Admin stock finished item
        $ownerFinishedStock->update(['is_admin_stock' => true]);

        $adminStockResponse = $this->getJson(route('shop-stock.finished-data', ['shop_id' => $this->shop->id]));
        $adminStockResponse->assertStatus(200);
        $adminData = $adminStockResponse->json('data.0');

        // Admin stock finished item for shop admin should show "Quick Restock" button
        $this->assertStringContainsString('btn-quick-restock', $adminData['actions']);
        $this->assertStringContainsString('Quick Restock', $adminData['actions']);
    }

    public function test_category_component_toggle_enables_and_disables_components_by_category_for_shop_admin()
    {
        $desktopCategory = Category::create(['category_name' => 'Desktop']);
        $laptopCategory  = Category::create(['category_name' => 'Laptop']);

        $desktopItem = Item::create([
            'item_name'   => 'Custom PC Desktop',
            'category_id' => $desktopCategory->id,
        ]);
        $laptopItem = Item::create([
            'item_name'   => 'HP Laptop',
            'category_id' => $laptopCategory->id,
        ]);

        $desktopStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $desktopItem->id,
            'buying_price'       => 1000,
            'selling_price'      => 1500,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'date_received'      => now()->toDateString(),
            'allow_components'   => false,
        ]);

        $laptopStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $laptopItem->id,
            'buying_price'       => 800,
            'selling_price'      => 1200,
            'quantity'           => 3,
            'remaining_quantity' => 3,
            'date_received'      => now()->toDateString(),
            'allow_components'   => false,
        ]);

        $this->actingAs($this->admin);

        // Enable components for Desktop category
        $response = $this->postJson(route('settings.toggle-components'), [
            'shop_stock_category_toggle' => 1,
            'category_id'                => $desktopCategory->id,
            'enabled'                    => 1,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertTrue((bool)$desktopStock->fresh()->allow_components);
        $this->assertFalse((bool)$laptopStock->fresh()->allow_components);

        // Disable components for Desktop category
        $response2 = $this->postJson(route('settings.toggle-components'), [
            'shop_stock_category_toggle' => 1,
            'category_id'                => $desktopCategory->id,
            'enabled'                    => 0,
        ]);

        $response2->assertStatus(200);
        $this->assertFalse((bool)$desktopStock->fresh()->allow_components);
    }

    public function test_category_component_toggle_for_owner()
    {
        $shop2 = Shop::create([
            'shop_name' => 'Test Shop 2',
            'location'  => 'Arusha',
        ]);

        $monitorCategory = Category::create(['category_name' => 'Monitor']);
        $monitorItem = Item::create([
            'item_name'   => 'Dell 27-inch Monitor',
            'category_id' => $monitorCategory->id,
        ]);

        $shop1Stock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $monitorItem->id,
            'buying_price'       => 300,
            'selling_price'      => 450,
            'quantity'           => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
            'allow_components'   => false,
        ]);

        $shop2Stock = ShopStock::create([
            'shop_id'            => $shop2->id,
            'item_id'            => $monitorItem->id,
            'buying_price'       => 300,
            'selling_price'      => 450,
            'quantity'           => 5,
            'remaining_quantity' => 5,
            'date_received'      => now()->toDateString(),
            'allow_components'   => false,
        ]);

        $this->actingAs($this->owner);

        // Enable components for Shop 1 stock
        $response = $this->postJson(route('settings.toggle-components'), [
            'shop_stock_category_toggle' => 1,
            'category_id'                => $monitorCategory->id,
            'enabled'                    => 1,
            'shop_id'                    => $this->shop->id,
        ]);

        $response->assertStatus(200);
        $this->assertTrue((bool)$shop1Stock->fresh()->allow_components);
        $this->assertFalse((bool)$shop2Stock->fresh()->allow_components);

        // Enable components for Shop 2 stock
        $response2 = $this->postJson(route('settings.toggle-components'), [
            'shop_stock_category_toggle' => 1,
            'category_id'                => $monitorCategory->id,
            'enabled'                    => 1,
            'shop_id'                    => $shop2->id,
        ]);

        $response2->assertStatus(200);
        $this->assertTrue((bool)$shop2Stock->fresh()->allow_components);
    }
}

