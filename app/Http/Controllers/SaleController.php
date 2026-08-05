<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ShopStock;
use App\Models\StockLog;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Sale::with('shop', 'seller', 'items.item');

        if ($user->isOwner()) {
            $query->where('is_admin_stock', false);
        } else {
            $query->where('shop_id', $user->shop_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sales        = $query->latest()->get();
        $totalRevenue = $sales->sum(fn($s) => $s->report_revenue);
        $statusFilter = $request->input('status', '');

        return view('sales.index', compact('sales', 'totalRevenue', 'statusFilter'));
    }

    public function create()
    {
        $user = Auth::user();
        $isOwner = $user->isOwner();

        // Retrieve all items in the system with their categories based on role and shop
        if ($isOwner) {
            $items = Item::with('category')->where('is_admin_item', false)->orderBy('item_name')->get();
        } else {
            $items = Item::with('category')
                ->where(function ($q) use ($user) {
                    $q->where('is_admin_item', false)
                      ->orWhere(function ($sq) use ($user) {
                          $sq->where('is_admin_item', true)
                             ->where('shop_id', $user->shop_id);
                      });
                })
                ->orderBy('item_name')
                ->get();
        }

        // Fetch all active stock records (both in stock and out of stock)
        if ($isOwner) {
            $activeStocks = \App\Models\MainStock::all()->groupBy('item_id');
        } else {
            $activeStocks = ShopStock::where('shop_id', $user->shop_id)->get()->groupBy('item_id');
        }

        $shopStocks = collect();

        foreach ($items as $item) {
            $itemStocks = $activeStocks->get($item->id);

            if ($itemStocks && $itemStocks->isNotEmpty()) {
                // If stock records exist for this item, push them (allows 0 remaining quantity)
                foreach ($itemStocks as $stock) {
                    $stock->setRelation('item', $item);
                    $shopStocks->push($stock);
                }
            } else {
                // Item has never been stocked. Create a temporary mock stock object.
                if ($isOwner) {
                    $stock = new \App\Models\MainStock([
                        'id' => 'item_' . $item->id,
                        'item_id' => $item->id,
                        'buying_price' => 0,
                        'selling_price' => 0,
                        'remaining_quantity' => 0,
                        'is_price_pending' => false,
                    ]);
                } else {
                    $stock = new ShopStock([
                        'id' => 'item_' . $item->id,
                        'item_id' => $item->id,
                        'buying_price' => 0,
                        'selling_price' => 0,
                        'remaining_quantity' => 0,
                        'is_price_pending' => false,
                        'is_sellable' => true,
                    ]);
                }
                $stock->setRelation('item', $item);
                $shopStocks->push($stock);
            }
        }

        return view('sales.create', compact('shopStocks'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isOwner = $user->isOwner();
        $isDraftProforma = $request->input('sale_status') === 'draft_proforma';

        $request->validate([
            'customer_name'      => 'nullable|string|max:150',
            'payment_method'     => 'required|in:cash,card,mobile_money,bank_transfer',
            'items'              => 'required|array|min:1',
            'items.*.shop_stock_id' => [
                'required',
                function ($attribute, $value, $fail) use ($isDraftProforma, $isOwner) {
                    if (str_starts_with($value, 'custom_')) {
                        // Off-catalog custom item — only allowed in proforma
                        if (!$isDraftProforma) {
                            $fail('Custom off-catalog items can only be saved in proforma quotes.');
                        }
                    } elseif (str_starts_with($value, 'item_')) {
                        if (!$isDraftProforma) {
                            $fail('Items not in stock can only be saved in proforma quotes.');
                        }
                        $itemId = substr($value, 5);
                        if (!\App\Models\Item::where('id', $itemId)->exists()) {
                            $fail('Selected item does not exist.');
                        }
                    } else {
                        $table = $isOwner ? 'main_stocks' : 'shop_stocks';
                        if (!\Illuminate\Support\Facades\DB::table($table)->where('id', $value)->exists()) {
                            $fail('Selected stock record is invalid.');
                        }
                    }
                }
            ],
            'items.*.custom_name' => 'nullable|string|max:255',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.price'      => 'required|numeric|min:0',
            'customer_id'        => 'nullable|string|max:50',
            'customer_po_box'    => 'nullable|string|max:150',
            'deliver_to'         => 'nullable|string|max:255',
            'delivery_date'      => 'nullable|date',
            'delivery_time'      => 'nullable|date_format:H:i',
            'validity_date'      => 'nullable|date',
            'terms_of_payment'   => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($request, $user, $isOwner, $isDraftProforma) {
            $hasAdminStock = false;
            $hasNormalStock = false;

            if (!$isOwner) {
                foreach ($request->items as $cartItem) {
                    $stockId = $cartItem['shop_stock_id'];
                    $isCustom = str_starts_with($stockId, 'custom_');
                    $isMock = str_starts_with($stockId, 'item_');

                    if (!$isCustom && !$isMock) {
                        $stockRow = ShopStock::find($stockId);
                        if ($stockRow && $stockRow->is_admin_stock) {
                            $hasAdminStock = true;
                        } else {
                            $hasNormalStock = true;
                        }
                    } else {
                        $hasNormalStock = true;
                    }
                }
            }

            $isSaleAdminStock = $hasAdminStock && !$hasNormalStock;

            $totalAmount = 0;
            $saleItemsData = [];

            foreach ($request->items as $cartItem) {
                $stockId = $cartItem['shop_stock_id'];
                $isCustom = str_starts_with($stockId, 'custom_');

                if ($isCustom) {
                    // Off-catalog custom line item (proforma only)
                    $submittedPrice  = floatval($cartItem['price']);
                    $subtotal        = $cartItem['quantity'] * $submittedPrice;
                    $totalAmount    += $subtotal;

                    $saleItemsData[] = [
                        'is_custom'         => true,
                        'custom_name'       => $cartItem['custom_name'] ?? 'Custom Item',
                        'item_id'           => null,
                        'stock'             => null,
                        'quantity'          => $cartItem['quantity'],
                        'price'             => $submittedPrice,
                        'owner_cost_price'  => 0.0,
                        'owner_realized_sp' => 0.0,
                        'shop_cost_price'   => 0.0,
                        'shop_realized_sp'  => $submittedPrice,
                        'is_admin_stock'    => false,
                    ];
                    continue;
                }

                if (str_starts_with($stockId, 'item_')) {
                    $itemId = substr($stockId, 5);
                    $item = \App\Models\Item::findOrFail($itemId);

                    if ($isOwner) {
                        $stock = new \App\Models\MainStock([
                            'id' => $stockId, 'item_id' => $item->id,
                            'buying_price' => 0, 'selling_price' => 0, 'remaining_quantity' => 0,
                        ]);
                    } else {
                        $stock = new ShopStock([
                            'id' => $stockId, 'item_id' => $item->id,
                            'buying_price' => 0, 'selling_price' => 0, 'remaining_quantity' => 0,
                            'is_sellable' => true,
                        ]);
                    }
                    $stock->setRelation('item', $item);
                } else {
                    if ($isOwner) {
                        $stock = \App\Models\MainStock::findOrFail($stockId);
                    } else {
                        $stock = ShopStock::findOrFail($stockId);
                        if (!$stock->is_sellable) {
                            throw new \Exception("Cannot sell {$stock->item->item_name} as it is locked pending price update.");
                        }
                    }
                }

                if (!$isDraftProforma && $cartItem['quantity'] > $stock->remaining_quantity) {
                    throw new \Exception("Insufficient stock for: {$stock->item->item_name}. Available: {$stock->remaining_quantity}");
                }

                $submittedPrice = floatval($cartItem['price']);
                if (!$isDraftProforma && $submittedPrice < floatval($stock->selling_price)) {
                    throw new \Exception("Price for {$stock->item->item_name} cannot be less than dedicated selling price TZS " . number_format($stock->selling_price, 0));
                }

                $ownerCostPrice = 0.0; $ownerRealizedSp = 0.0;
                $shopCostPrice  = 0.0; $shopRealizedSp  = $submittedPrice;
                $itemIsAdminStock = false;

                $latestMainStock = \App\Models\MainStock::where('item_id', $stock->item_id)
                    ->orderByDesc('date_received')->first();

                if (!str_starts_with($stockId, 'item_')) {
                    if ($isOwner) {
                        $ownerCostPrice = floatval($stock->buying_price);
                        $ownerRealizedSp = $submittedPrice;
                        $shopCostPrice   = floatval($stock->buying_price);
                    } else {
                        $itemIsAdminStock = (bool) ($stock->is_admin_stock ?? false);
                        if ($itemIsAdminStock) {
                            $ownerCostPrice  = floatval($stock->buying_price);
                            $ownerRealizedSp = floatval($stock->selling_price);
                        } else {
                            $ownerCostPrice  = floatval($latestMainStock?->buying_price ?? 0);
                            $ownerRealizedSp = floatval($latestMainStock?->selling_price ?? 0);
                        }
                        $shopCostPrice   = floatval($stock->buying_price);
                    }
                } else {
                    $ownerCostPrice  = floatval($latestMainStock?->buying_price ?? 0);
                    $ownerRealizedSp = floatval($latestMainStock?->selling_price ?? 0);
                    $shopCostPrice   = $isOwner ? $ownerCostPrice
                        : floatval(ShopStock::where('item_id', $stock->item_id)->where('shop_id', $user->shop_id)->first()?->buying_price ?? $ownerRealizedSp);
                }

                $totalAmount += $cartItem['quantity'] * $submittedPrice;

                $saleItemsData[] = [
                    'is_custom'         => false,
                    'custom_name'       => null,
                    'item_id'           => $stock->item_id,
                    'stock'             => $stock,
                    'quantity'          => $cartItem['quantity'],
                    'price'             => $submittedPrice,
                    'owner_cost_price'  => $ownerCostPrice,
                    'owner_realized_sp' => $ownerRealizedSp,
                    'shop_cost_price'   => $shopCostPrice,
                    'shop_realized_sp'  => $shopRealizedSp,
                    'is_admin_stock'    => $itemIsAdminStock,
                ];
            }

            $sale = Sale::create([
                'shop_id'           => $isOwner ? null : $user->shop_id,
                'seller_id'         => $user->id,
                'customer_name'     => $request->customer_name,
                'total_amount'      => $totalAmount,
                'payment_method'    => $request->payment_method,
                'sale_date'         => now()->toDateString(),
                'status'            => $isDraftProforma ? 'draft_proforma' : 'completed',
                'customer_id'       => $request->customer_id,
                'customer_po_box'   => $request->customer_po_box,
                'deliver_to'        => $request->deliver_to,
                'delivery_date'     => $request->delivery_date,
                'delivery_time'     => $request->delivery_time,
                'validity_date'     => $request->validity_date,
                'terms_of_payment'  => $request->terms_of_payment,
                'is_admin_stock'    => $isSaleAdminStock,
            ]);

            foreach ($saleItemsData as $data) {
                SaleItem::create([
                    'sale_id'           => $sale->id,
                    'item_id'           => $data['item_id'],
                    'custom_name'       => $data['custom_name'],
                    'quantity'          => $data['quantity'],
                    'selling_price'     => $data['price'],
                    'owner_cost_price'  => $data['owner_cost_price'],
                    'owner_realized_sp' => $data['owner_realized_sp'],
                    'shop_cost_price'   => $data['shop_cost_price'],
                    'shop_realized_sp'  => $data['shop_realized_sp'],
                    'is_admin_stock'    => $data['is_admin_stock'],
                ]);

                // Custom items have no stock to decrement; real items do
                if (!$isDraftProforma && !$data['is_custom']) {
                    $data['stock']->decrement('remaining_quantity', $data['quantity']);

                    StockLog::create([
                        'item_id'          => $data['item_id'],
                        'from_location'    => $isOwner ? 'Main Store' : $data['stock']->shop->shop_name,
                        'to_location'      => $request->customer_name ?? 'Walk-in Customer',
                        'quantity'         => $data['quantity'],
                        'transaction_type' => 'SALE',
                        'performed_by'     => $user->id,
                        'date'             => now()->toDateString(),
                        'notes'            => "Sale #{$sale->id}" . ($isOwner ? " (Direct Sale from Main Store)" : ""),
                        'is_admin_stock'   => $data['is_admin_stock'],
                    ]);
                }
            }

            session(['last_sale_id' => $sale->id]);
        });

        $saleId = session('last_sale_id');

        if ($isDraftProforma) {
            return redirect()->route('sales.show', $saleId)
                ->with('success', 'Proforma quote saved. You can print the Proforma Invoice or Delivery Note from this page.');
        }

        $printerEnabled = \App\Models\Setting::get('printer_enabled_user_' . $user->id, '1');
        if ($printerEnabled === '0') {
            return redirect()->route('sales.index')
                ->with('success', 'Sale completed successfully.');
        }

        return redirect()->route('sales.receipt', $saleId)
            ->with('success', 'Sale completed successfully.');
    }

    public function show(Sale $sale)
    {
        $sale->load('shop', 'seller', 'items.item.category');
        return view('sales.show', compact('sale'));
    }

    public function receipt(Sale $sale)
    {
        $sale->load('shop', 'seller', 'items.item');
        return view('sales.receipt', compact('sale'));
    }

    public function invoice(Sale $sale)
    {
        if ($sale->status !== 'completed') {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'An Invoice can only be printed for a completed sale. Convert this proforma to a sale first.');
        }

        $sale->load('shop', 'seller', 'items.item.category');
        $shop = $sale->shop;
        $company = [
            'name'         => $shop ? ($shop->shop_name ?: \App\Models\Setting::get('system_name', 'AMSTROOM')) : \App\Models\Setting::get('system_name', 'AMSTROOM'),
            'slogan'       => $shop ? ($shop->slogan ?: \App\Models\Setting::get('slogan', '')) : \App\Models\Setting::get('slogan', ''),
            'tin'          => $shop ? ($shop->tin_number ?: \App\Models\Setting::get('company_tin', '')) : \App\Models\Setting::get('company_tin', ''),
            'address'      => $shop ? ($shop->address ?: \App\Models\Setting::get('company_address', '')) : \App\Models\Setting::get('company_address', ''),
            'bank_name'    => $shop ? ($shop->bank_name ?: \App\Models\Setting::get('company_bank_name', '')) : \App\Models\Setting::get('company_bank_name', ''),
            'bank_account' => $shop ? ($shop->bank_account ?: \App\Models\Setting::get('company_bank_account', '')) : \App\Models\Setting::get('company_bank_account', ''),
            'logo'         => $shop ? ($shop->logo ?: \App\Models\Setting::get('logo')) : \App\Models\Setting::get('logo'),
        ];
        return view('sales.invoice', compact('sale', 'company'));
    }

    public function proforma(Sale $sale)
    {
        $sale->load('shop', 'seller', 'items.item.category');
        $shop = $sale->shop;
        $company = [
            'name'         => $shop ? ($shop->shop_name ?: \App\Models\Setting::get('system_name', 'AMSTROOM')) : \App\Models\Setting::get('system_name', 'AMSTROOM'),
            'slogan'       => $shop ? ($shop->slogan ?: \App\Models\Setting::get('slogan', '')) : \App\Models\Setting::get('slogan', ''),
            'tin'          => $shop ? ($shop->tin_number ?: \App\Models\Setting::get('company_tin', '')) : \App\Models\Setting::get('company_tin', ''),
            'address'      => $shop ? ($shop->address ?: \App\Models\Setting::get('company_address', '')) : \App\Models\Setting::get('company_address', ''),
            'bank_name'    => $shop ? ($shop->bank_name ?: \App\Models\Setting::get('company_bank_name', '')) : \App\Models\Setting::get('company_bank_name', ''),
            'bank_account' => $shop ? ($shop->bank_account ?: \App\Models\Setting::get('company_bank_account', '')) : \App\Models\Setting::get('company_bank_account', ''),
            'logo'         => $shop ? ($shop->logo ?: \App\Models\Setting::get('logo')) : \App\Models\Setting::get('logo'),
        ];
        return view('sales.proforma', compact('sale', 'company'));
    }

    public function deliveryNote(Sale $sale)
    {
        if ($sale->status !== 'completed') {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'A Delivery Note can only be printed for a completed sale. Stock must be committed before goods can be dispatched.');
        }

        $sale->load('shop', 'seller', 'items.item.category');
        $shop = $sale->shop;
        $company = [
            'name'    => $shop ? ($shop->shop_name ?: \App\Models\Setting::get('system_name', 'AMSTROOM')) : \App\Models\Setting::get('system_name', 'AMSTROOM'),
            'slogan'  => $shop ? ($shop->slogan ?: \App\Models\Setting::get('slogan', '')) : \App\Models\Setting::get('slogan', ''),
            'tin'     => $shop ? ($shop->tin_number ?: \App\Models\Setting::get('company_tin', '')) : \App\Models\Setting::get('company_tin', ''),
            'address' => $shop ? ($shop->address ?: \App\Models\Setting::get('company_address', '')) : \App\Models\Setting::get('company_address', ''),
            'logo'    => $shop ? ($shop->logo ?: \App\Models\Setting::get('logo')) : \App\Models\Setting::get('logo'),
        ];
        return view('sales.delivery_note', compact('sale', 'company'));
    }

    public function convertToSale(Sale $sale)
    {
        if ($sale->status !== 'draft_proforma') {
            return back()->with('error', 'This sale is already completed.');
        }

        $user = Auth::user();
        $isOwner = $user->isOwner();

        try {
            DB::transaction(function () use ($sale, $user, $isOwner) {
                foreach ($sale->items as $saleItem) {
                    // 1. If it's a custom off-catalog item, register it first
                    if (!$saleItem->item_id) {
                        $category = \App\Models\Category::firstOrCreate(['category_name' => 'General']);
                        $item = \App\Models\Item::create([
                            'item_name' => $saleItem->custom_name ?? 'Custom Item',
                            'category_id' => $category->id,
                        ]);
                        $saleItem->update(['item_id' => $item->id]);
                    }

                    // 2. Resolve and auto-receive/stock any missing quantities
                    if ($isOwner) {
                        $stock = \App\Models\MainStock::where('item_id', $saleItem->item_id)->first();

                        if (!$stock) {
                            $stock = \App\Models\MainStock::create([
                                'item_id' => $saleItem->item_id,
                                'buying_price' => 0,
                                'selling_price' => $saleItem->selling_price,
                                'stocked_quantity' => $saleItem->quantity,
                                'remaining_quantity' => $saleItem->quantity,
                                'date_received' => now()->toDateString(),
                            ]);
                            StockLog::create([
                                'item_id' => $saleItem->item_id,
                                'from_location' => 'Supplier (Auto-Stock for Proforma)',
                                'to_location' => 'Main Warehouse',
                                'quantity' => $saleItem->quantity,
                                'transaction_type' => 'STOCK_RECEIVED',
                                'performed_by' => $user->id,
                                'date' => now()->toDateString(),
                                'notes' => "Auto-stocked for Proforma #{$sale->id} conversion",
                            ]);
                        } elseif ($stock->remaining_quantity < $saleItem->quantity) {
                            $diff = $saleItem->quantity - $stock->remaining_quantity;
                            $stock->increment('stocked_quantity', $diff);
                            $stock->increment('remaining_quantity', $diff);
                            StockLog::create([
                                'item_id' => $saleItem->item_id,
                                'from_location' => 'Supplier (Auto-Stock for Proforma)',
                                'to_location' => 'Main Warehouse',
                                'quantity' => $diff,
                                'transaction_type' => 'STOCK_RECEIVED',
                                'performed_by' => $user->id,
                                'date' => now()->toDateString(),
                                'notes' => "Auto-stocked for Proforma #{$sale->id} conversion",
                            ]);
                        }
                    } else {
                        $stock = \App\Models\ShopStock::where('item_id', $saleItem->item_id)
                            ->where('shop_id', $user->shop_id)->first();

                        if (!$stock) {
                            $shopName = $user->shop->shop_name ?? 'Shop';
                            $stock = \App\Models\ShopStock::create([
                                'shop_id' => $user->shop_id,
                                'item_id' => $saleItem->item_id,
                                'buying_price' => 0,
                                'selling_price' => $saleItem->selling_price,
                                'quantity' => $saleItem->quantity,
                                'remaining_quantity' => $saleItem->quantity,
                                'is_sellable' => true,
                                'date_received' => now()->toDateString(),
                            ]);
                            StockLog::create([
                                'item_id' => $saleItem->item_id,
                                'from_location' => 'Supplier (Auto-Stock for Proforma)',
                                'to_location' => $shopName,
                                'quantity' => $saleItem->quantity,
                                'transaction_type' => 'STOCK_RECEIVED',
                                'performed_by' => $user->id,
                                'date' => now()->toDateString(),
                                'notes' => "Auto-stocked for Proforma #{$sale->id} conversion",
                            ]);
                        } elseif ($stock->remaining_quantity < $saleItem->quantity) {
                            $diff = $saleItem->quantity - $stock->remaining_quantity;
                            $shopName = $stock->shop->shop_name ?? $user->shop->shop_name ?? 'Shop';
                            $stock->increment('quantity', $diff);
                            $stock->increment('remaining_quantity', $diff);
                            StockLog::create([
                                'item_id' => $saleItem->item_id,
                                'from_location' => 'Supplier (Auto-Stock for Proforma)',
                                'to_location' => $shopName,
                                'quantity' => $diff,
                                'transaction_type' => 'STOCK_RECEIVED',
                                'performed_by' => $user->id,
                                'date' => now()->toDateString(),
                                'notes' => "Auto-stocked for Proforma #{$sale->id} conversion",
                            ]);
                        }
                    }

                    // 3. Deduct stock for completed sale
                    $stock->decrement('remaining_quantity', $saleItem->quantity);

                    StockLog::create([
                        'item_id'          => $saleItem->item_id,
                        'from_location'    => $isOwner ? 'Main Store' : ($stock->shop->shop_name ?? 'Shop'),
                        'to_location'      => $sale->customer_name ?? 'Walk-in Customer',
                        'quantity'         => $saleItem->quantity,
                        'transaction_type' => 'SALE',
                        'performed_by'     => $user->id,
                        'date'             => now()->toDateString(),
                        'notes'            => "Sale #{$sale->id} (Converted from Proforma)",
                    ]);
                }

                $sale->update(['status' => 'completed', 'sale_date' => now()->toDateString()]);
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to convert proforma to sale: ' . $e->getMessage());
        }

        return redirect()->route('sales.show', $sale)->with('success', 'Proforma converted to a completed sale. Stock has been auto-received and committed.');
    }
}
