<?php

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\MainStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleBatchAllocation;
use App\Models\SaleReturnBatchAllocation;
use App\Services\StockAllocationService;
use App\Exceptions\InsufficientStockException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->shop = Shop::create([
        'shop_name' => 'Main Downtown Shop',
        'location'  => 'Downtown',
    ]);

    $this->user = User::create([
        'name'     => 'Shop Seller',
        'email'    => 'seller@amstroom.com',
        'password' => bcrypt('password'),
        'role'     => 'seller',
        'shop_id'  => $this->shop->id,
    ]);

    $this->category = Category::create(['category_name' => 'Computers & Parts']);

    $this->item = Item::create([
        'item_name'   => 'HP EliteBook 840 G5',
        'category_id' => $this->category->id,
    ]);
});

test('Test 1 - Simple FIFO deduction from oldest batch', function () {
    $batchA = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->item->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 500000,
        'selling_price'      => 650000,
        'date_received'      => '2026-01-01',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $batchB = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->item->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 520000,
        'selling_price'      => 650000,
        'date_received'      => '2026-01-10',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $sale = Sale::create([
        'shop_id'        => $this->shop->id,
        'seller_id'      => $this->user->id,
        'customer_name'  => 'John Doe',
        'total_amount'   => 3900000,
        'payment_method' => 'cash',
        'sale_date'      => '2026-01-15',
        'status'         => 'completed',
    ]);

    $saleItem = SaleItem::create([
        'sale_id'       => $sale->id,
        'item_id'       => $this->item->id,
        'quantity'      => 6,
        'selling_price' => 650000,
    ]);

    StockAllocationService::allocateAndDeductShopStock(
        $this->shop->id,
        $this->item->id,
        6,
        $sale->id,
        $saleItem->id,
        $this->user->id,
        false,
        'John Doe'
    );

    expect($batchA->fresh()->remaining_quantity)->toBe(4);
    expect($batchB->fresh()->remaining_quantity)->toBe(10);

    $allocations = SaleBatchAllocation::where('sale_item_id', $saleItem->id)->get();
    expect($allocations)->toHaveCount(1);
    expect($allocations[0]->stock_id)->toBe($batchA->id);
    expect($allocations[0]->quantity)->toBe(6);
});

test('Test 2 & Test 3 - FIFO across multiple batches and exact allocation records', function () {
    $batchA = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->item->id,
        'quantity'           => 5,
        'remaining_quantity' => 5,
        'buying_price'       => 500000,
        'selling_price'      => 650000,
        'date_received'      => '2026-01-01',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $batchB = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->item->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 520000,
        'selling_price'      => 650000,
        'date_received'      => '2026-01-10',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $sale = Sale::create([
        'shop_id'        => $this->shop->id,
        'seller_id'      => $this->user->id,
        'customer_name'  => 'Sarah Connor',
        'total_amount'   => 5200000,
        'payment_method' => 'cash',
        'sale_date'      => '2026-01-15',
        'status'         => 'completed',
    ]);

    $saleItem = SaleItem::create([
        'sale_id'       => $sale->id,
        'item_id'       => $this->item->id,
        'quantity'      => 8,
        'selling_price' => 650000,
    ]);

    StockAllocationService::allocateAndDeductShopStock(
        $this->shop->id,
        $this->item->id,
        8,
        $sale->id,
        $saleItem->id,
        $this->user->id,
        false,
        'Sarah Connor'
    );

    expect($batchA->fresh()->remaining_quantity)->toBe(0);
    expect($batchB->fresh()->remaining_quantity)->toBe(7);

    $allocations = SaleBatchAllocation::where('sale_item_id', $saleItem->id)->orderBy('id')->get();
    expect($allocations)->toHaveCount(2);

    expect($allocations[0]->stock_id)->toBe($batchA->id);
    expect($allocations[0]->quantity)->toBe(5);

    expect($allocations[1]->stock_id)->toBe($batchB->id);
    expect($allocations[1]->quantity)->toBe(3);
});

test('Test 4 - Insufficient stock rejects sale atomically without partial deductions', function () {
    $batchA = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->item->id,
        'quantity'           => 5,
        'remaining_quantity' => 5,
        'buying_price'       => 500000,
        'selling_price'      => 650000,
        'date_received'      => '2026-01-01',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $sale = Sale::create([
        'shop_id'        => $this->shop->id,
        'seller_id'      => $this->user->id,
        'customer_name'  => 'Test Customer',
        'total_amount'   => 3900000,
        'payment_method' => 'cash',
        'sale_date'      => '2026-01-15',
        'status'         => 'completed',
    ]);

    $saleItem = SaleItem::create([
        'sale_id'       => $sale->id,
        'item_id'       => $this->item->id,
        'quantity'      => 6,
        'selling_price' => 650000,
    ]);

    $exceptionThrown = false;
    try {
        DB::transaction(function () use ($sale, $saleItem) {
            StockAllocationService::allocateAndDeductShopStock(
                $this->shop->id,
                $this->item->id,
                6,
                $sale->id,
                $saleItem->id,
                $this->user->id,
                false,
                'Test Customer'
            );
        });
    } catch (InsufficientStockException $e) {
        $exceptionThrown = true;
        expect($e->requestedQty)->toBe(6);
        expect($e->availableQty)->toBe(5);
        expect($e->shortageQty)->toBe(1);
    }

    expect($exceptionThrown)->toBeTrue();
    // Batch A must remain untouched
    expect($batchA->fresh()->remaining_quantity)->toBe(5);
    // Allocations must not be created
    expect(SaleBatchAllocation::where('sale_item_id', $saleItem->id)->count())->toBe(0);
});

test('Test 5 & Test 6 - Exact-batch return restoration for partial and full returns', function () {
    $batchA = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->item->id,
        'quantity'           => 5,
        'remaining_quantity' => 5,
        'buying_price'       => 500000,
        'selling_price'      => 650000,
        'date_received'      => '2026-01-01',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $batchB = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->item->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 520000,
        'selling_price'      => 650000,
        'date_received'      => '2026-01-10',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $sale = Sale::create([
        'shop_id'        => $this->shop->id,
        'seller_id'      => $this->user->id,
        'customer_name'  => 'Alice Smith',
        'total_amount'   => 5200000,
        'payment_method' => 'cash',
        'sale_date'      => '2026-01-15',
        'status'         => 'completed',
    ]);

    $saleItem = SaleItem::create([
        'sale_id'       => $sale->id,
        'item_id'       => $this->item->id,
        'quantity'      => 8,
        'selling_price' => 650000,
    ]);

    StockAllocationService::allocateAndDeductShopStock(
        $this->shop->id,
        $this->item->id,
        8,
        $sale->id,
        $saleItem->id,
        $this->user->id,
        false,
        'Alice Smith'
    );

    // Initial state after sale of 8: Batch A remaining = 0, Batch B remaining = 7
    expect($batchA->fresh()->remaining_quantity)->toBe(0);
    expect($batchB->fresh()->remaining_quantity)->toBe(7);

    // Partial return: 2 units returned
    $saleReturn1 = SaleReturn::create([
        'sale_id'      => $sale->id,
        'requested_by' => $this->user->id,
        'approved_by'  => $this->user->id,
        'status'       => 'approved',
        'reason'       => 'Partial return 2 units',
        'return_date'  => '2026-01-16',
    ]);

    $returnItem1 = SaleReturnItem::create([
        'sale_return_id' => $saleReturn1->id,
        'item_id'        => $this->item->id,
        'quantity'       => 2,
    ]);

    StockAllocationService::restoreReturnAllocations(
        $saleReturn1,
        $returnItem1,
        $saleItem,
        2,
        $this->user->id
    );

    // Batch B (the most recently deducted batch of this sale) restored 2 units: 7 + 2 = 9
    expect($batchB->fresh()->remaining_quantity)->toBe(9);
    expect($batchA->fresh()->remaining_quantity)->toBe(0);

    // Return remaining 6 units (full return of remainder)
    $saleReturn2 = SaleReturn::create([
        'sale_id'      => $sale->id,
        'requested_by' => $this->user->id,
        'approved_by'  => $this->user->id,
        'status'       => 'approved',
        'reason'       => 'Return remaining 6 units',
        'return_date'  => '2026-01-17',
    ]);

    $returnItem2 = SaleReturnItem::create([
        'sale_return_id' => $saleReturn2->id,
        'item_id'        => $this->item->id,
        'quantity'       => 6,
    ]);

    StockAllocationService::restoreReturnAllocations(
        $saleReturn2,
        $returnItem2,
        $saleItem,
        6,
        $this->user->id
    );

    // Both batches must be completely restored back to exact original quantities
    expect($batchA->fresh()->remaining_quantity)->toBe(5);
    expect($batchB->fresh()->remaining_quantity)->toBe(10);

    // Confirm return allocations total exactly 8
    $totalReturnedAlloc = SaleReturnBatchAllocation::where('stock_type', 'shop')->sum('quantity');
    expect($totalReturnedAlloc)->toBe(8);
});

test('Test 7 - Over-return is strictly rejected', function () {
    $batchA = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->item->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 500000,
        'selling_price'      => 650000,
        'date_received'      => '2026-01-01',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $sale = Sale::create([
        'shop_id'        => $this->shop->id,
        'seller_id'      => $this->user->id,
        'customer_name'  => 'Over Return Test',
        'total_amount'   => 5200000,
        'payment_method' => 'cash',
        'sale_date'      => '2026-01-15',
        'status'         => 'completed',
    ]);

    $saleItem = SaleItem::create([
        'sale_id'       => $sale->id,
        'item_id'       => $this->item->id,
        'quantity'      => 8,
        'selling_price' => 650000,
    ]);

    StockAllocationService::allocateAndDeductShopStock(
        $this->shop->id,
        $this->item->id,
        8,
        $sale->id,
        $saleItem->id,
        $this->user->id,
        false,
        'Over Return Test'
    );

    $saleReturn = SaleReturn::create([
        'sale_id'      => $sale->id,
        'requested_by' => $this->user->id,
        'approved_by'  => $this->user->id,
        'status'       => 'approved',
        'reason'       => 'Attempting over-return',
        'return_date'  => '2026-01-16',
    ]);

    $returnItem = SaleReturnItem::create([
        'sale_return_id' => $saleReturn->id,
        'item_id'        => $this->item->id,
        'quantity'       => 9,
    ]);

    $rejected = false;
    try {
        StockAllocationService::restoreReturnAllocations(
            $saleReturn,
            $returnItem,
            $saleItem,
            9,
            $this->user->id
        );
    } catch (\Exception $e) {
        $rejected = true;
    }

    expect($rejected)->toBeTrue();
    // Stock remains at post-sale state (2 remaining)
    expect($batchA->fresh()->remaining_quantity)->toBe(2);
});

test('Test 8 & Test 10 - Main Warehouse Stock FIFO, allocation, and rollback integrity', function () {
    $mainBatch1 = MainStock::create([
        'item_id'            => $this->item->id,
        'stocked_quantity'   => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 450000,
        'selling_price'      => 600000,
        'date_received'      => '2026-01-01',
    ]);

    $mainBatch2 = MainStock::create([
        'item_id'            => $this->item->id,
        'stocked_quantity'   => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 460000,
        'selling_price'      => 600000,
        'date_received'      => '2026-01-05',
    ]);

    $sale = Sale::create([
        'shop_id'        => null, // Main Store HQ sale
        'seller_id'      => $this->user->id,
        'customer_name'  => 'Warehouse Customer',
        'total_amount'   => 9000000,
        'payment_method' => 'cash',
        'sale_date'      => '2026-01-15',
        'status'         => 'completed',
    ]);

    $saleItem = SaleItem::create([
        'sale_id'       => $sale->id,
        'item_id'       => $this->item->id,
        'quantity'      => 15,
        'selling_price' => 600000,
    ]);

    StockAllocationService::allocateAndDeductMainStock(
        $this->item->id,
        15,
        $sale->id,
        $saleItem->id,
        $this->user->id,
        'Warehouse Customer'
    );

    expect($mainBatch1->fresh()->remaining_quantity)->toBe(0);
    expect($mainBatch2->fresh()->remaining_quantity)->toBe(5);

    // Test transaction rollback on failure
    $rollbackTriggered = false;
    try {
        DB::transaction(function () use ($sale, $saleItem) {
            StockAllocationService::allocateAndDeductMainStock(
                $this->item->id,
                5,
                $sale->id,
                $saleItem->id,
                $this->user->id,
                'Warehouse Customer'
            );

            throw new \RuntimeException('Simulated mid-transaction crash');
        });
    } catch (\RuntimeException $e) {
        $rollbackTriggered = true;
    }

    expect($rollbackTriggered)->toBeTrue();
    // After rollback, batch2 should still have 5 (not 0)
    expect($mainBatch2->fresh()->remaining_quantity)->toBe(5);
});

test('Test 9 - Component removal restores to original allocated batch', function () {
    $compBatch = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->item->id,
        'quantity'           => 10,
        'remaining_quantity' => 10,
        'buying_price'       => 500000,
        'selling_price'      => 650000,
        'date_received'      => '2026-01-01',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $sale = Sale::create([
        'shop_id'        => $this->shop->id,
        'seller_id'      => $this->user->id,
        'customer_name'  => 'Customized Rig Customer',
        'total_amount'   => 1500000,
        'payment_method' => 'cash',
        'sale_date'      => '2026-01-15',
        'status'         => 'completed',
    ]);

    $parentSaleItem = SaleItem::create([
        'sale_id'       => $sale->id,
        'item_id'       => $this->item->id,
        'quantity'      => 1,
        'selling_price' => 1500000,
    ]);

    $compSaleItem = SaleItem::create([
        'sale_id'       => $sale->id,
        'parent_id'     => $parentSaleItem->id,
        'item_id'       => $this->item->id,
        'quantity'      => 3,
        'selling_price' => 0,
    ]);

    StockAllocationService::allocateAndDeductShopStock(
        $this->shop->id,
        $this->item->id,
        3,
        $sale->id,
        $compSaleItem->id,
        $this->user->id,
        false,
        'Customized Rig Customer'
    );

    expect($compBatch->fresh()->remaining_quantity)->toBe(7);
    expect(SaleBatchAllocation::where('sale_item_id', $compSaleItem->id)->count())->toBe(1);

    // Now remove component
    StockAllocationService::restoreComponentAllocations($sale, $compSaleItem, $this->user->id);
    $compSaleItem->delete();

    expect($compBatch->fresh()->remaining_quantity)->toBe(10);
    expect(SaleBatchAllocation::where('sale_item_id', $compSaleItem->id)->count())->toBe(0);
});

test('Test 11 & Test 12 - stock:audit-batches detects anomalies and performs safe bounded repair', function () {
    // Create an invalid negative batch and an overflow batch
    $negativeBatch = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->item->id,
        'quantity'           => 10,
        'remaining_quantity' => -3,
        'buying_price'       => 500000,
        'selling_price'      => 650000,
        'date_received'      => '2026-01-01',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    $overflowBatch = ShopStock::create([
        'shop_id'            => $this->shop->id,
        'item_id'            => $this->item->id,
        'quantity'           => 10,
        'remaining_quantity' => 15,
        'buying_price'       => 500000,
        'selling_price'      => 650000,
        'date_received'      => '2026-01-01',
        'is_sellable'        => true,
        'is_admin_stock'     => false,
    ]);

    // 1. Audit report mode should fail exit code and not alter quantities
    $this->artisan('stock:audit-batches')
        ->assertExitCode(1);

    expect($negativeBatch->fresh()->remaining_quantity)->toBe(-3);
    expect($overflowBatch->fresh()->remaining_quantity)->toBe(15);
    // Initial quantity must NEVER change
    expect($negativeBatch->fresh()->quantity)->toBe(10);
    expect($overflowBatch->fresh()->quantity)->toBe(10);

    // 2. Audit repair mode should safely bound remaining_quantity to [0, quantity]
    $this->artisan('stock:audit-batches', ['--repair' => true])
        ->assertExitCode(1); // returned failures that were repaired

    expect($negativeBatch->fresh()->remaining_quantity)->toBe(0);
    expect($overflowBatch->fresh()->remaining_quantity)->toBe(10);
    // Initial quantity preserved
    expect($negativeBatch->fresh()->quantity)->toBe(10);
    expect($overflowBatch->fresh()->quantity)->toBe(10);

    // 3. Second audit run should pass completely
    $this->artisan('stock:audit-batches')
        ->assertExitCode(0);
});
