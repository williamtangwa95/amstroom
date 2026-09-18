<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Defect;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\StockRequest;
use App\Models\StockRequestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReportsEmailTest extends TestCase
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
            'shop_name' => 'Main Outlet',
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
            'item_name'       => 'MacBook Pro M3',
            'category_id'     => $category->id,
            'specification'   => '16GB, 512GB',
            'brand'           => 'Apple',
            'model'           => 'M3',
            'warranty_period' => '1 Year',
        ]);
    }

    public function test_owner_can_send_stock_report_email()
    {
        Mail::fake();

        MainStock::create([
            'item_id'            => $this->item->id,
            'stocked_quantity'   => 10,
            'remaining_quantity' => 10,
            'buying_price'       => 1000000,
            'selling_price'      => 1500000,
            'date_received'      => now(),
        ]);

        $this->actingAs($this->owner);

        $response = $this->post(route('reports.stock.send-email'), [
            'emails' => 'boss@example.com, manager@example.com',
            'type'   => 'main',
            'note'   => 'Main warehouse inventory snapshot.',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        Mail::assertSent(\App\Mail\FilteredStockReportMail::class, function ($mail) {
            return $mail->hasTo('boss@example.com')
                && $mail->hasTo('manager@example.com')
                && $mail->reportData['type'] === 'main'
                && $mail->reportData['total_qty'] === 10.0
                && (float)$mail->reportData['total_cost_value'] === 10000000.0;
        });
    }

    public function test_user_can_send_expenses_report_email()
    {
        Mail::fake();

        $expenseCategory = ExpenseCategory::create([
            'name'       => 'Utilities',
            'created_by' => $this->owner->id,
        ]);

        Expense::create([
            'expense_category_id' => $expenseCategory->id,
            'activity'            => 'Power payment',
            'recorded_by'         => $this->admin->id,
            'amount'              => 75000,
            'activity_date'       => now()->format('Y-m-d'),
            'description'         => 'Electricity bill',
            'status'              => 'approved',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('reports.expenses.send-email'), [
            'emails' => 'accountant@example.com',
            'period' => 'monthly',
            'note'   => 'Monthly operational expenses.',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        Mail::assertSent(\App\Mail\FilteredExpensesReportMail::class, function ($mail) {
            return $mail->hasTo('accountant@example.com')
                && (float)$mail->reportData['total_amount'] === 75000.0;
        });
    }

    public function test_owner_can_send_sales_vs_expenses_report_email()
    {
        Mail::fake();

        $expenseCategory = ExpenseCategory::create([
            'name'       => 'Rent',
            'created_by' => $this->owner->id,
        ]);
        Expense::create([
            'expense_category_id' => $expenseCategory->id,
            'activity'            => 'Shop Rent',
            'recorded_by'         => $this->admin->id,
            'amount'              => 50000,
            'activity_date'       => now()->format('Y-m-d'),
            'description'         => 'Monthly rent',
            'status'              => 'approved',
        ]);

        $sale = Sale::create([
            'shop_id'        => $this->shop->id,
            'seller_id'      => $this->admin->id,
            'customer_name'  => 'Alice',
            'payment_method' => 'cash',
            'sale_date'      => now(),
            'total_amount'   => 200000,
        ]);

        SaleItem::create([
            'sale_id'           => $sale->id,
            'item_id'           => $this->item->id,
            'quantity'          => 1,
            'selling_price'     => 200000,
            'owner_cost_price'  => 100000,
            'owner_realized_sp' => 150000,
            'shop_cost_price'   => 120000,
            'shop_realized_sp'  => 200000,
        ]);

        $this->actingAs($this->owner);

        $response = $this->post(route('reports.sales-vs-expenses.send-email'), [
            'emails' => 'director@example.com',
            'period' => 'monthly',
            'note'   => 'P&L review',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        Mail::assertSent(\App\Mail\FilteredSalesVsExpensesReportMail::class, function ($mail) {
            return $mail->hasTo('director@example.com')
                && (float)$mail->reportData['total_sales'] === 150000.0 // Owner revenue
                && (float)$mail->reportData['total_expenses'] === 50000.0
                && (float)$mail->reportData['net_profit'] === 100000.0;
        });
    }

    public function test_user_can_send_defect_report_email()
    {
        Mail::fake();

        Defect::create([
            'shop_id'     => $this->shop->id,
            'item_id'     => $this->item->id,
            'reported_by' => $this->admin->id,
            'quantity'    => 2,
            'reason'      => 'Damaged screen upon arrival',
            'status'      => 'reported',
            'date'        => now()->format('Y-m-d'),
        ]);

        $this->actingAs($this->owner);

        $response = $this->post(route('reports.defect.send-email'), [
            'emails' => 'qa@example.com',
            'status' => 'reported',
            'note'   => 'Review defective items ASAP.',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        Mail::assertSent(\App\Mail\FilteredDefectReportMail::class, function ($mail) {
            return $mail->hasTo('qa@example.com')
                && $mail->reportData['total_defective'] === 2
                && $mail->reportData['incidents_count'] === 1;
        });
    }

    public function test_user_can_send_transfer_report_email()
    {
        Mail::fake();

        $stockRequest = StockRequest::create([
            'shop_id'      => $this->shop->id,
            'requested_by' => $this->admin->id,
            'status'       => 'approved',
            'request_date' => now(),
        ]);

        StockRequestItem::create([
            'request_id' => $stockRequest->id,
            'item_id'    => $this->item->id,
            'quantity'   => 5,
        ]);

        $this->actingAs($this->owner);

        $response = $this->post(route('reports.transfer.send-email'), [
            'emails' => 'logistics@example.com',
            'status' => 'approved',
            'note'   => 'Approved transfer batches.',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        Mail::assertSent(\App\Mail\FilteredTransferReportMail::class, function ($mail) {
            return $mail->hasTo('logistics@example.com')
                && $mail->reportData['status_filter'] === 'approved'
                && $mail->reportData['stats']['approved'] === 1;
        });
    }
}
