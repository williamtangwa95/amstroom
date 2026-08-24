<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\HandoverReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandoverReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected Shop $shop;
    protected Item $item;
    protected ShopStock $ownerStock;
    protected ShopStock $adminStock;
    protected ExpenseCategory $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'Test Amstroom Shop',
            'location'  => 'Dar es Salaam',
        ]);

        $this->owner = User::create([
            'name'     => 'System Owner',
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
            'item_name'     => 'Smart TV',
            'category_id'   => $category->id,
            'specification' => '55 inch UHD',
            'brand'         => 'LG',
            'model'         => '2026',
        ]);

        // Create owner stock
        $this->ownerStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 1000,
            'selling_price'      => 2000,
            'quantity'           => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
            'is_admin_stock'     => false,
            'is_sellable'        => true,
        ]);

        // Create admin stock
        $this->adminStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 1800,
            'selling_price'      => 3000,
            'quantity'           => 10,
            'remaining_quantity' => 10,
            'date_received'      => now()->toDateString(),
            'is_admin_stock'     => true,
            'is_sellable'        => true,
        ]);

        $this->expenseCategory = ExpenseCategory::create([
            'name' => 'Utilities',
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_can_view_handovers_dashboard()
    {
        $this->actingAs($this->admin);
        $response = $this->get(route('handovers.index'));
        $response->assertStatus(200);
    }

    public function test_correct_calculations_on_creation_preview()
    {
        // 1. Create owner sale
        $ownerSale = Sale::create([
            'shop_id'        => $this->shop->id,
            'seller_id'      => $this->admin->id,
            'customer_name'  => 'Walk-in',
            'payment_method' => 'cash',
            'sale_date'      => now()->toDateString(),
            'total_amount'   => 2000,
            'status'         => 'completed',
            'is_admin_stock' => false,
        ]);

        SaleItem::create([
            'sale_id'           => $ownerSale->id,
            'item_id'           => $this->item->id,
            'quantity'          => 1,
            'selling_price'     => 2000,
            'owner_cost_price'  => 1000,
            'owner_realized_sp' => 2000,
            'shop_cost_price'   => 1000,
            'shop_realized_sp'  => 2000,
            'is_admin_stock'    => false,
        ]);

        // 2. Create admin sale
        $adminSale = Sale::create([
            'shop_id'        => $this->shop->id,
            'seller_id'      => $this->admin->id,
            'customer_name'  => 'Walk-in',
            'payment_method' => 'cash',
            'sale_date'      => now()->toDateString(),
            'total_amount'   => 3000,
            'status'         => 'completed',
            'is_admin_stock' => true,
        ]);

        SaleItem::create([
            'sale_id'           => $adminSale->id,
            'item_id'           => $this->item->id,
            'quantity'          => 1,
            'selling_price'     => 3000,
            'owner_cost_price'  => 1800,
            'owner_realized_sp' => 3000,
            'shop_cost_price'   => 1800,
            'shop_realized_sp'  => 3000,
            'is_admin_stock'    => true,
        ]);

        // 3. Create approved expense
        Expense::create([
            'expense_category_id' => $this->expenseCategory->id,
            'activity'            => 'Electric Bill',
            'description'         => 'Shop electricity bill',
            'amount'              => 500,
            'activity_date'       => now()->toDateString(),
            'recorded_by'         => $this->admin->id,
            'status'              => 'approved',
        ]);

        $this->actingAs($this->admin);
        
        $response = $this->get(route('handovers.create', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertSee('TZS 2,000'); // Owner sales
        $response->assertDontSee('TZS 3,000'); // Admin sales
        $response->assertDontSee('TZS 1,800'); // Admin cost attributable to Owner
        $response->assertSee('TZS 500');   // Expenses
        $response->assertSee('TZS 1,500'); // Expected submission amount (2000 - 500)
        $response->assertSee('TZS 1,000'); // Admin profit (2000 - 1000)
    }

    public function test_can_submit_handover_report_with_shortage_and_validation()
    {
        // Setup a sale
        $ownerSale = Sale::create([
            'shop_id'        => $this->shop->id,
            'seller_id'      => $this->admin->id,
            'customer_name'  => 'Walk-in',
            'payment_method' => 'cash',
            'sale_date'      => now()->toDateString(),
            'total_amount'   => 2000,
            'status'         => 'completed',
            'is_admin_stock' => false,
        ]);

        SaleItem::create([
            'sale_id'           => $ownerSale->id,
            'item_id'           => $this->item->id,
            'quantity'          => 1,
            'selling_price'     => 2000,
            'owner_cost_price'  => 1000,
            'owner_realized_sp' => 2000,
            'shop_cost_price'   => 1000,
            'shop_realized_sp'  => 2000,
            'is_admin_stock'    => false,
        ]);

        $this->actingAs($this->admin);

        // Submit with shortage, but without providing difference_reason -> must fail validation
        $response = $this->post(route('handovers.store'), [
            'start_date'    => now()->toDateString(),
            'end_date'      => now()->toDateString(),
            'actual_amount' => 1800, // Expected is 2000
            'needs_reason'  => '1',
        ]);
        $response->assertSessionHasErrors(['difference_reason']);

        // Submit successfully with difference_reason
        $response = $this->post(route('handovers.store'), [
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->toDateString(),
            'actual_amount'     => 1800,
            'needs_reason'      => '1',
            'difference_reason' => 'Expense paid directly',
            'notes'             => 'Paid token bill',
            'submit_action'     => 'submit',
        ]);

        $response->assertRedirect(route('handovers.index'));
        $this->assertDatabaseHas('handover_reports', [
            'expected_amount'   => 2000,
            'actual_amount'     => 1800,
            'difference'        => -200,
            'difference_status' => 'shortage',
            'difference_reason' => 'Expense paid directly',
            'status'            => 'submitted',
        ]);

        // Verify the sale is linked
        $this->assertEquals(1, Sale::whereNotNull('handover_report_id')->count());
    }

    public function test_prevent_overlapping_handover_periods()
    {
        $this->actingAs($this->admin);

        // Save a completed handover
        HandoverReport::create([
            'handover_no'       => 'HO-TEST-001',
            'shop_id'           => $this->shop->id,
            'shop_admin_id'     => $this->admin->id,
            'start_date'        => now()->subDays(5)->toDateString(),
            'end_date'          => now()->subDays(1)->toDateString(),
            'total_owner_sales' => 1000,
            'total_admin_sales' => 0,
            'admin_stock_cost'  => 0,
            'total_expenses'    => 0,
            'net_profit'        => 1000,
            'expected_amount'   => 1000,
            'actual_amount'     => 1000,
            'difference'        => 0,
            'difference_status' => 'exact',
            'status'            => 'completed',
            'created_by'        => $this->admin->id,
        ]);

        // Attempting to create one covering overlapping dates should be rejected
        $response = $this->post(route('handovers.store'), [
            'start_date'    => now()->subDays(3)->toDateString(), // Overlaps!
            'end_date'      => now()->toDateString(),
            'actual_amount' => 500,
        ]);

        $response->assertSessionHas('error');
    }

    public function test_owner_can_approve_reject_and_confirm_receipt()
    {
        $handover = HandoverReport::create([
            'handover_no'       => 'HO-TEST-002',
            'shop_id'           => $this->shop->id,
            'shop_admin_id'     => $this->admin->id,
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->toDateString(),
            'total_owner_sales' => 1000,
            'total_admin_sales' => 0,
            'admin_stock_cost'  => 0,
            'total_expenses'    => 0,
            'net_profit'        => 1000,
            'expected_amount'   => 1000,
            'actual_amount'     => 900,
            'difference'        => -100,
            'difference_status' => 'shortage',
            'status'            => 'submitted',
            'created_by'        => $this->admin->id,
        ]);

        // 1. Try rejecting
        $this->actingAs($this->owner);
        $response = $this->post(route('handovers.reject', $handover), [
            'remarks' => 'Wrong expenses included',
        ]);
        $response->assertRedirect();
        $this->assertEquals('rejected', $handover->fresh()->status);

        // Reset status for approval test
        $handover->update(['status' => 'submitted']);

        // 2. Approve
        $response = $this->post(route('handovers.approve', $handover));
        $response->assertRedirect();
        $this->assertEquals('approved', $handover->fresh()->status);

        // 3. Confirm Cash Received
        $response = $this->post(route('handovers.confirm-receipt', $handover), [
            'amount_received'  => 900,
            'received_remarks' => 'Received exact cash',
        ]);
        $response->assertRedirect();

        $fresh = $handover->fresh();
        $this->assertEquals('completed', $fresh->status);
        $this->assertEquals(900, $fresh->amount_received);
        $this->assertEquals($this->owner->id, $fresh->received_by);
    }

    public function test_can_submit_handover_report_with_commission()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('handovers.store'), [
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->toDateString(),
            'actual_amount'     => 1000,
            'commission_amount' => 150,
            'submit_action'     => 'submit',
        ]);

        $response->assertRedirect(route('handovers.index'));
        $this->assertDatabaseHas('handover_reports', [
            'actual_amount'     => 1000,
            'commission_amount' => 150,
            'status'            => 'submitted',
        ]);
    }

    public function test_owner_can_return_handover_report_for_modification()
    {
        $handover = HandoverReport::create([
            'handover_no'       => 'HO-TEST-RETURN',
            'shop_id'           => $this->shop->id,
            'shop_admin_id'     => $this->admin->id,
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->toDateString(),
            'total_owner_sales' => 1000,
            'total_admin_sales' => 0,
            'admin_stock_cost'  => 0,
            'total_expenses'    => 0,
            'net_profit'        => 1000,
            'expected_amount'   => 1000,
            'actual_amount'     => 1000,
            'difference'        => 0,
            'difference_status' => 'exact',
            'status'            => 'submitted',
            'created_by'        => $this->admin->id,
        ]);

        $this->actingAs($this->owner);
        $response = $this->post(route('handovers.return', $handover), [
            'remarks' => 'Please adjust commission details',
        ]);
        $response->assertRedirect();
        
        $fresh = $handover->fresh();
        $this->assertEquals('returned', $fresh->status);
        $this->assertEquals('Please adjust commission details', $fresh->received_remarks);
    }

    public function test_shop_admin_can_edit_and_update_returned_report()
    {
        $handover = HandoverReport::create([
            'handover_no'       => 'HO-TEST-EDIT-RETURN',
            'shop_id'           => $this->shop->id,
            'shop_admin_id'     => $this->admin->id,
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->toDateString(),
            'total_owner_sales' => 1000,
            'total_admin_sales' => 0,
            'admin_stock_cost'  => 0,
            'total_expenses'    => 0,
            'net_profit'        => 1000,
            'expected_amount'   => 1000,
            'actual_amount'     => 1000,
            'difference'        => 0,
            'difference_status' => 'exact',
            'status'            => 'returned',
            'created_by'        => $this->admin->id,
        ]);

        $this->actingAs($this->admin);

        // Edit page loads
        $response = $this->get(route('handovers.edit', $handover));
        $response->assertStatus(200);

        // Update form submission (change actual amount and commission)
        $response = $this->put(route('handovers.update', $handover), [
            'actual_amount'     => 950,
            'commission_amount' => 200,
            'needs_reason'      => '1',
            'difference_reason' => 'Expense paid directly',
            'submit_action'     => 'submit',
        ]);

        $response->assertRedirect(route('handovers.show', $handover));
        
        $fresh = $handover->fresh();
        $this->assertEquals('submitted', $fresh->status);
        $this->assertEquals(950, $fresh->actual_amount);
        $this->assertEquals(200, $fresh->commission_amount);
        $this->assertEquals(-50, $fresh->difference);
    }
}
