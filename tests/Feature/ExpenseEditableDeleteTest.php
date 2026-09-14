<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseEditableDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected Shop $shop;
    protected User $owner;
    protected User $admin;
    protected User $seller;
    protected ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create(['shop_name' => 'Main Shop', 'location' => 'Main Location']);

        $this->owner = User::create([
            'name' => 'Owner', 'email' => 'owner_exp@example.com', 'password' => bcrypt('password'),
            'role' => 'owner',
        ]);

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin_exp@example.com', 'password' => bcrypt('password'),
            'role' => 'shop_admin', 'shop_id' => $this->shop->id,
        ]);

        $this->seller = User::create([
            'name' => 'Seller', 'email' => 'seller_exp@example.com', 'password' => bcrypt('password'),
            'role' => 'seller', 'shop_id' => $this->shop->id,
        ]);

        $this->category = ExpenseCategory::create([
            'name' => 'Utilities',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_shop_admin_can_delete_editable_expense()
    {
        $expense = Expense::create([
            'expense_category_id' => $this->category->id,
            'activity' => 'Office Refreshments',
            'amount' => 15000,
            'activity_date' => now()->toDateString(),
            'recorded_by' => $this->seller->id,
            'status' => 'editable',
        ]);

        $this->actingAs($this->admin);

        $response = $this->delete(route('expenses.destroy', $expense));
        $response->assertRedirect(route('expenses.index'));

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_seller_can_delete_their_own_editable_expense()
    {
        $expense = Expense::create([
            'expense_category_id' => $this->category->id,
            'activity' => 'Delivery Transport',
            'amount' => 8000,
            'activity_date' => now()->toDateString(),
            'recorded_by' => $this->seller->id,
            'status' => 'editable',
        ]);

        $this->actingAs($this->seller);

        $response = $this->delete(route('expenses.destroy', $expense));
        $response->assertRedirect(route('expenses.index'));

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_seller_cannot_delete_approved_expense()
    {
        $expense = Expense::create([
            'expense_category_id' => $this->category->id,
            'activity' => 'Stationery',
            'amount' => 5000,
            'activity_date' => now()->toDateString(),
            'recorded_by' => $this->seller->id,
            'status' => 'approved',
        ]);

        $this->actingAs($this->seller);

        $response = $this->delete(route('expenses.destroy', $expense));
        $response->assertStatus(403);

        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }
}
