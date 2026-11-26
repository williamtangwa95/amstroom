<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\HandoverEditRequest;
use App\Models\HandoverReport;
use App\Models\Item;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandoverLockProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $shopAdmin;
    protected User $seller;
    protected Shop $shop;
    protected Item $item;
    protected ShopStock $shopStock;
    protected ExpenseCategory $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->shop = Shop::create([
            'shop_name' => 'Kariakoo Branch',
            'location'  => 'Dar es Salaam',
            'status'    => 'active',
        ]);
        $this->shopAdmin = User::factory()->create([
            'role'    => 'shop_admin',
            'shop_id' => $this->shop->id,
        ]);
        $this->seller = User::factory()->create([
            'role'    => 'seller',
            'shop_id' => $this->shop->id,
        ]);

        $category = Category::create(['category_name' => 'Electronics']);
        $this->item = Item::create(['item_name' => 'Wired Mouse', 'category_id' => $category->id]);
        $this->shopStock = ShopStock::create([
            'shop_id'            => $this->shop->id,
            'item_id'            => $this->item->id,
            'buying_price'       => 10000,
            'selling_price'      => 15000,
            'quantity'           => 10,
            'remaining_quantity' => 8,
            'is_sellable'        => true,
            'is_admin_stock'     => false,
        ]);

        $this->expenseCategory = ExpenseCategory::create(['name' => 'Transport']);
    }

    private function createSubmittedHandoverWithRecords(string $status = 'submitted'): array
    {
        $handover = HandoverReport::create([
            'handover_no'       => 'HO-20261126-001',
            'shop_id'           => $this->shop->id,
            'shop_admin_id'     => $this->shopAdmin->id,
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->toDateString(),
            'total_owner_sales' => 30000,
            'total_expenses'    => 5000,
            'expected_amount'   => 25000,
            'actual_amount'     => 25000,
            'difference'        => 0,
            'difference_status' => 'exact',
            'status'            => $status,
            'created_by'        => $this->shopAdmin->id,
            'submitted_at'      => $status === 'draft' ? null : now(),
        ]);

        $sale = Sale::create([
            'shop_id'            => $this->shop->id,
            'seller_id'          => $this->seller->id,
            'customer_name'      => 'Walk-in',
            'total_amount'       => 30000,
            'payment_method'     => 'cash',
            'sale_date'          => now()->toDateString(),
            'status'             => 'completed',
            'handover_report_id' => $handover->id,
        ]);

        $saleItem = SaleItem::create([
            'sale_id'           => $sale->id,
            'item_id'           => $this->item->id,
            'quantity'          => 2,
            'selling_price'     => 15000,
            'owner_realized_sp' => 15000,
            'shop_realized_sp'  => 15000,
            'owner_cost_price'  => 10000,
            'shop_cost_price'   => 10000,
        ]);

        $expense = Expense::create([
            'expense_category_id' => $this->expenseCategory->id,
            'activity'            => 'Boda Delivery',
            'description'         => 'Customer parcel',
            'amount'              => 5000,
            'activity_date'       => now()->toDateString(),
            'recorded_by'         => $this->seller->id,
            'approved_by'         => $this->shopAdmin->id,
            'status'              => 'approved',
            'handover_report_id'  => $handover->id,
        ]);

        return [$handover, $sale, $saleItem, $expense];
    }

    public function test_submitted_handover_blocks_sale_edit_return_and_expense_edit_delete_until_owner_approves(): void
    {
        [$handover, $sale, $saleItem, $expense] = $this->createSubmittedHandoverWithRecords('submitted');

        // 1. Shop Admin and Seller are blocked from editing sale customer or processing sale return
        $this->actingAs($this->shopAdmin)
            ->patch(route('sales.update-customer', $sale), ['customer_name' => 'Edited'])
            ->assertStatus(403);

        $this->actingAs($this->shopAdmin)
            ->post(route('sales-returns.store', $sale), [
                'reason' => 'Damaged item',
                'items'  => [['sale_item_id' => $saleItem->id, 'qty' => 1]],
            ])
            ->assertStatus(403);

        // 2. Shop Admin and Seller are blocked from editing or deleting handover-locked expense
        $this->actingAs($this->shopAdmin)
            ->put(route('expenses.update', $expense), [
                'expense_category_id' => $this->expenseCategory->id,
                'activity'            => 'Altered',
                'amount'              => 9000,
                'activity_date'       => now()->toDateString(),
            ])
            ->assertStatus(403);

        $this->actingAs($this->seller)
            ->delete(route('expenses.destroy', $expense))
            ->assertStatus(403);

        // 3. Seller submits unlock request to edit the expense
        $reqRes = $this->actingAs($this->seller)->postJson(route('handover-edit-requests.store'), [
            'record_type' => 'expense',
            'record_id'   => $expense->id,
            'action_type' => 'edit',
            'reason'      => 'Typed 5000 instead of 8000 on receipt',
        ]);
        $reqRes->assertOk()->assertJson(['success' => true]);

        $editRequest = HandoverEditRequest::latest('id')->first();
        $this->assertNotNull($editRequest);
        $this->assertEquals('pending', $editRequest->status);

        // Still blocked while pending
        $this->actingAs($this->seller)
            ->put(route('expenses.update', $expense), [
                'expense_category_id' => $this->expenseCategory->id,
                'activity'            => 'Boda Delivery',
                'amount'              => 8000,
                'activity_date'       => now()->toDateString(),
            ])
            ->assertStatus(403);

        // 4. Owner approves unlock request
        $this->actingAs($this->owner)
            ->postJson(route('handover-edit-requests.approve', $editRequest), [
                'review_remarks' => 'Receipt verified',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // 5. Seller can now edit the expense ONCE -> request becomes consumed, record re-locks, handover recalculates
        $this->actingAs($this->seller)
            ->put(route('expenses.update', $expense), [
                'expense_category_id' => $this->expenseCategory->id,
                'activity'            => 'Boda Delivery Updated',
                'amount'              => 8000,
                'activity_date'       => now()->toDateString(),
            ])
            ->assertRedirect(route('expenses.index'));

        $editRequest->refresh();
        $this->assertEquals('consumed', $editRequest->status);

        $handover->refresh();
        $this->assertEquals(8000.0, (float) $handover->total_expenses);
        $this->assertEquals(22000.0, (float) $handover->expected_amount);

        // 6. Second edit attempt is automatically blocked again (auto re-locked!)
        $this->actingAs($this->seller)
            ->put(route('expenses.update', $expense), [
                'expense_category_id' => $this->expenseCategory->id,
                'activity'            => 'Second Unauthorized Edit',
                'amount'              => 1000,
                'activity_date'       => now()->toDateString(),
            ])
            ->assertStatus(403);
    }

    public function test_draft_handover_records_remain_editable_and_returnable(): void
    {
        [$handover, $sale, $saleItem, $expense] = $this->createSubmittedHandoverWithRecords('draft');

        $this->assertFalse($sale->isLockedByHandover());
        $this->assertFalse($expense->isLockedByHandover());

        $this->actingAs($this->shopAdmin)
            ->patch(route('sales.update-customer', $sale), ['customer_name' => 'Draft Customer'])
            ->assertRedirect();
    }

    public function test_owner_rejecting_unlock_request_keeps_record_locked(): void
    {
        [$handover, $sale, $saleItem, $expense] = $this->createSubmittedHandoverWithRecords('submitted');

        $this->actingAs($this->shopAdmin)->postJson(route('handover-edit-requests.store'), [
            'record_type' => 'sale',
            'record_id'   => $sale->id,
            'action_type' => 'return',
            'reason'      => 'Customer changed mind',
        ])->assertOk();

        $editRequest = HandoverEditRequest::latest('id')->first();

        $this->actingAs($this->owner)
            ->postJson(route('handover-edit-requests.reject', $editRequest), [
                'review_remarks' => 'Return window closed after handover.',
            ])
            ->assertOk();

        $this->assertEquals('rejected', $editRequest->fresh()->status);

        $this->actingAs($this->shopAdmin)
            ->post(route('sales-returns.store', $sale), [
                'reason' => 'Still trying',
                'items'  => [['sale_item_id' => $saleItem->id, 'qty' => 1]],
            ])
            ->assertStatus(403);
    }
}
