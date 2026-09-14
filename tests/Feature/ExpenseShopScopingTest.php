<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Notification;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseShopScopingTest extends TestCase
{
    use RefreshDatabase;

    protected Shop $shopA;
    protected Shop $shopB;
    protected User $adminA;
    protected User $adminB;
    protected User $sellerA;
    protected User $sellerB;
    protected ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shopA = Shop::create(['shop_name' => 'Shop A', 'location' => 'Loc A']);
        $this->shopB = Shop::create(['shop_name' => 'Shop B', 'location' => 'Loc B']);

        $this->adminA = User::create([
            'name' => 'Admin A', 'email' => 'adminA@example.com', 'password' => bcrypt('password'),
            'role' => 'shop_admin', 'shop_id' => $this->shopA->id,
        ]);
        $this->adminB = User::create([
            'name' => 'Admin B', 'email' => 'adminB@example.com', 'password' => bcrypt('password'),
            'role' => 'shop_admin', 'shop_id' => $this->shopB->id,
        ]);

        $this->sellerA = User::create([
            'name' => 'Seller A', 'email' => 'sellerA@example.com', 'password' => bcrypt('password'),
            'role' => 'seller', 'shop_id' => $this->shopA->id,
        ]);
        $this->sellerB = User::create([
            'name' => 'Seller B', 'email' => 'sellerB@example.com', 'password' => bcrypt('password'),
            'role' => 'seller', 'shop_id' => $this->shopB->id,
        ]);

        $this->category = ExpenseCategory::create(['name' => 'Utilities', 'created_by' => $this->adminA->id]);
    }

    public function test_shop_admin_only_sees_expenses_from_their_own_shop_in_datatable()
    {
        $expenseA = Expense::create([
            'expense_category_id' => $this->category->id,
            'activity' => 'Electricity A',
            'amount' => 5000,
            'activity_date' => now()->toDateString(),
            'recorded_by' => $this->sellerA->id,
            'status' => 'pending',
        ]);

        $expenseB = Expense::create([
            'expense_category_id' => $this->category->id,
            'activity' => 'Electricity B',
            'amount' => 7000,
            'activity_date' => now()->toDateString(),
            'recorded_by' => $this->sellerB->id,
            'status' => 'pending',
        ]);

        // Acting as Admin A
        $this->actingAs($this->adminA);
        $response = $this->getJson(route('expenses.data'));
        $response->assertStatus(200);

        $data = $response->json('data');
        $activities = array_column($data, 'activity');

        $this->assertCount(1, $data);
        $this->assertStringContainsString('Electricity A', $activities[0]);
    }

    public function test_seller_recording_expense_only_notifies_their_shop_admin()
    {
        $this->actingAs($this->sellerA);

        $response = $this->post(route('expenses.store'), [
            'expense_category_id' => $this->category->id,
            'activity' => 'Water Bill A',
            'amount' => 3000,
            'activity_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('expenses.index'));

        // Admin A should have notification, Admin B should NOT
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->adminA->id,
            'title' => 'New Expense Recorded',
        ]);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->adminB->id,
            'title' => 'New Expense Recorded',
        ]);
    }

    public function test_shop_admin_cannot_approve_expense_from_another_shop()
    {
        $expenseB = Expense::create([
            'expense_category_id' => $this->category->id,
            'activity' => 'Cleaning B',
            'amount' => 4000,
            'activity_date' => now()->toDateString(),
            'recorded_by' => $this->sellerB->id,
            'status' => 'pending',
        ]);

        // Admin A attempts to approve Admin B / Seller B expense
        $this->actingAs($this->adminA);
        $response = $this->post(route('expenses.approve', $expenseB));

        $response->assertStatus(403);
        $this->assertEquals('pending', $expenseB->fresh()->status);
    }
}
