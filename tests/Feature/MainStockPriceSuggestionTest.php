<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainStockPriceSuggestionTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->owner = User::create([
            'name'     => 'Store Owner',
            'email'    => 'owner_suggest@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);
    }

    public function test_create_stock_form_includes_existing_main_store_prices_data_attributes()
    {
        $category = Category::create(['category_name' => 'Smartphones']);
        $item = Item::create([
            'item_name'   => 'iPhone 15 Pro',
            'category_id' => $category->id,
        ]);

        MainStock::create([
            'item_id'            => $item->id,
            'buying_price'       => 2500000,
            'selling_price'      => 3200000,
            'stocked_quantity'   => 5,
            'remaining_quantity' => 5,
            'date_received'      => now()->toDateString(),
        ]);

        $this->actingAs($this->owner);

        $response = $this->get(route('main-stock.create'));

        $response->assertStatus(200);
        $response->assertSee('data-buying-price="2500000"', false);
        $response->assertSee('data-selling-price="3200000"', false);
    }
}
