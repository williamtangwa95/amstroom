<?php

namespace App\Http\Controllers;

use App\Models\ShopStock;
use App\Models\Shop;
use App\Models\Item;
use App\Models\Category;
use App\Models\MainStock;
use App\Models\StockLog;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ShopStockController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $shopId = $user->isOwner() ? $request->get('shop_id', null) : $user->shop_id;

        $query = ShopStock::with('item.category', 'shop');

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        if ($user->isOwner()) {
            $query->where('is_admin_stock', false);
        }

        $stocks = $query->latest()->get();
        $shops  = $user->isOwner() ? Shop::active()->get() : collect();

        $items = collect();
        $categories = collect();
        if ($user->isShopAdmin()) {
            $items = \App\Models\Item::with(['shopStocks' => function ($q) use ($user) {
                $q->where('shop_id', $user->shop_id);
            }, 'mainStocks'])->where(function ($q) use ($user) {
                $q->where('is_admin_item', false)
                  ->orWhere(function ($sq) use ($user) {
                      $sq->where('is_admin_item', true)
                         ->where('shop_id', $user->shop_id);
                  });
            })->orderBy('item_name')->get();

            $categories = \App\Models\Category::where(function ($q) use ($user) {
                $q->where('is_admin_category', false)
                  ->orWhere(function ($sq) use ($user) {
                      $sq->where('is_admin_category', true)
                         ->where('shop_id', $user->shop_id);
                  });
            })->orderBy('category_name')->get();
        }

        $lowStockItems = ShopStock::with('item', 'shop')
            ->whereColumn('remaining_quantity', '<=', 'low_stock_alert')
            ->when(!$user->isOwner(), fn($q) => $q->where('shop_id', $user->shop_id))
            ->when($user->isOwner(), fn($q) => $q->where('is_admin_stock', false))
            ->count();

        return view('shop-stock.index', compact('stocks', 'shops', 'shopId', 'lowStockItems', 'items', 'categories'));
    }

    public function storeAdminStock(Request $request)
    {
        $user = Auth::user();
        if (!$user->isShopAdmin()) {
            abort(403, 'Only shop admins can add admin stock.');
        }

        $products = $request->input('products', []);

        if (empty($products)) {
            // Fallback for single product inputs (backward compatibility)
            $products = [[
                'create_new_product' => $request->boolean('create_new_product'),
                'create_new_category' => $request->boolean('create_new_category'),
                'item_id' => $request->item_id,
                'new_item_name' => $request->new_item_name,
                'category_id' => $request->category_id,
                'new_category_name' => $request->new_category_name,
                'brand' => $request->brand,
                'model' => $request->model,
                'specification' => $request->specification,
                'quantity' => $request->quantity,
                'buying_price' => $request->buying_price,
                'selling_price' => $request->selling_price,
            ]];
        }

        $request->validate([
            'date_received' => 'required|date',
        ]);

        $dateReceived = $request->date_received;

        // Validate each product row
        foreach ($products as $index => $pData) {
            $createNew = filter_var($pData['create_new_product'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $createNewCategory = filter_var($pData['create_new_category'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $rules = [];
            if ($createNew) {
                if ($createNewCategory) {
                    $rules = [
                        'new_item_name'     => 'required|string|max:150',
                        'new_category_name' => 'required|string|max:100',
                        'brand'             => 'nullable|string|max:100',
                        'model'             => 'nullable|string|max:100',
                        'specification'     => 'nullable|string',
                        'buying_price'      => 'required|numeric|min:0',
                        'selling_price'     => 'required|numeric|min:' . ($pData['buying_price'] ?? 0),
                        'quantity'          => 'required|integer|min:1',
                    ];
                } else {
                    $rules = [
                        'new_item_name' => 'required|string|max:150',
                        'category_id'   => 'required|exists:categories,id',
                        'brand'         => 'nullable|string|max:100',
                        'model'         => 'nullable|string|max:100',
                        'specification' => 'nullable|string',
                        'buying_price'  => 'required|numeric|min:0',
                        'selling_price' => 'required|numeric|min:' . ($pData['buying_price'] ?? 0),
                        'quantity'      => 'required|integer|min:1',
                    ];
                }
            } else {
                $rules = [
                    'item_id'       => 'required|exists:items,id',
                    'buying_price'  => 'required|numeric|min:0',
                    'selling_price' => 'required|numeric|min:' . ($pData['buying_price'] ?? 0),
                    'quantity'      => 'required|integer|min:1',
                ];
            }

            $validator = \Illuminate\Support\Facades\Validator::make($pData, $rules, [
                'selling_price.min' => 'Product #' . ($index + 1) . ': The selling price must be greater than or equal to the buying price.',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($products, $user, $dateReceived) {
            foreach ($products as $pData) {
                $createNew = filter_var($pData['create_new_product'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $createNewCategory = filter_var($pData['create_new_category'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if ($createNew) {
                    if ($createNewCategory) {
                        $categoryName = trim($pData['new_category_name']);
                        $category = \App\Models\Category::where(function($q) use ($user) {
                            $q->where('is_admin_category', false)
                              ->orWhere(function($sq) use ($user) {
                                  $sq->where('is_admin_category', true)
                                     ->where('shop_id', $user->shop_id);
                              });
                        })->where('category_name', $categoryName)->first();

                        if (!$category) {
                            $category = \App\Models\Category::create([
                                'category_name'     => $categoryName,
                                'is_admin_category' => true,
                                'shop_id'           => $user->shop_id,
                            ]);
                        }
                        $categoryId = $category->id;
                    } else {
                        $categoryId = $pData['category_id'];
                    }

                    $item = \App\Models\Item::create([
                        'item_name'     => $pData['new_item_name'],
                        'category_id'   => $categoryId,
                        'brand'         => $pData['brand'] ?? null,
                        'model'         => $pData['model'] ?? null,
                        'specification' => $pData['specification'] ?? null,
                        'is_admin_item' => true,
                        'shop_id'       => $user->shop_id,
                    ]);
                    $itemId = $item->id;
                } else {
                    $itemId = $pData['item_id'];
                }

                $stock = ShopStock::create([
                    'shop_id'            => $user->shop_id,
                    'item_id'            => $itemId,
                    'buying_price'       => $pData['buying_price'],
                    'selling_price'      => $pData['selling_price'],
                    'quantity'           => $pData['quantity'],
                    'remaining_quantity' => $pData['quantity'],
                    'low_stock_alert'    => 1,
                    'date_received'      => $dateReceived,
                    'is_price_pending'   => false,
                    'is_sellable'        => true,
                    'is_admin_stock'     => true,
                ]);

                \App\Models\StockLog::create([
                    'item_id'          => $stock->item_id,
                    'from_location'    => 'Supplier (Admin)',
                    'to_location'      => $user->shop->shop_name,
                    'quantity'         => $stock->quantity,
                    'transaction_type' => 'STOCK_RECEIVED',
                    'performed_by'     => $user->id,
                    'date'             => $stock->date_received,
                    'notes'            => 'Admin stock added directly to shop',
                    'is_admin_stock'   => true,
                ]);

                $item = \App\Models\Item::findOrFail($itemId);
                $sellers = \App\Models\User::where('shop_id', $user->shop_id)
                    ->where('role', 'seller')
                    ->get();
                foreach ($sellers as $seller) {
                    \App\Models\Notification::create([
                        'user_id' => $seller->id,
                        'title'   => 'New Admin Stock Added',
                        'message' => "Admin has added new stock for \"{$item->item_name}\" (Qty: {$stock->quantity}) to the shop stock.",
                    ]);
                }
            }
        });

        return redirect()->route('shop-stock.index')
            ->with('success', 'Admin stock added to shop successfully.');
    }

    public function storeOwnerStock(Request $request)
    {
        $user = Auth::user();
        if (!$user->isShopAdmin() || !$user->allow_stock_addition) {
            abort(403, 'Unauthorized action.');
        }

        // Check if products array is present
        if ($request->has('products')) {
            $products = $request->input('products');
            if (!is_array($products)) {
                return redirect()->back()->withErrors(['products' => 'Invalid products data.']);
            }

            // Loop and perform manual validation
            foreach ($products as $idx => $prod) {
                $pNum = $idx + 1;
                $createNew = filter_var($prod['create_new_product'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $createNewCategory = filter_var($prod['create_new_category'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if ($createNew) {
                    if (empty($prod['new_item_name'])) {
                        return redirect()->back()->withErrors(["products" => "Product #{$pNum}: Product Name is required."])->withInput();
                    }
                    if ($createNewCategory) {
                        if (empty($prod['new_category_name'])) {
                            return redirect()->back()->withErrors(["products" => "Product #{$pNum}: Category Name is required."])->withInput();
                        }
                    } else {
                        if (empty($prod['category_id'])) {
                            return redirect()->back()->withErrors(["products" => "Product #{$pNum}: Category select is required."])->withInput();
                        }
                    }
                } else {
                    if (empty($prod['item_id'])) {
                        return redirect()->back()->withErrors(["products" => "Product #{$pNum}: Product select is required."])->withInput();
                    }
                }

                $quantity = intval($prod['quantity'] ?? 0);
                if ($quantity < 1) {
                    return redirect()->back()->withErrors(["products" => "Product #{$pNum}: Quantity must be at least 1."])->withInput();
                }

                $buyingPrice = floatval($prod['buying_price'] ?? 0);
                if ($buyingPrice < 0) {
                    return redirect()->back()->withErrors(["products" => "Product #{$pNum}: Buying price must be a positive number."])->withInput();
                }

                $sellingPrice = floatval($prod['selling_price'] ?? 0);
                if ($sellingPrice < $buyingPrice) {
                    return redirect()->back()->withErrors(["products" => "Product #{$pNum}: The selling price must be greater than or equal to the buying price."])->withInput();
                }
            }

            $dateReceived = $request->input('date_received', date('Y-m-d'));

            DB::transaction(function () use ($products, $user, $dateReceived) {
                foreach ($products as $prod) {
                    $createNew = filter_var($prod['create_new_product'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $createNewCategory = filter_var($prod['create_new_category'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $buyingPrice = floatval($prod['buying_price'] ?? 0);
                    $sellingPrice = floatval($prod['selling_price'] ?? 0);
                    $quantity = intval($prod['quantity'] ?? 0);

                    if ($createNew) {
                        if ($createNewCategory) {
                            $categoryName = trim($prod['new_category_name']);
                            $category = \App\Models\Category::where('is_admin_category', false)
                                ->where('category_name', $categoryName)->first();

                            if (!$category) {
                                $category = \App\Models\Category::create([
                                    'category_name'     => $categoryName,
                                    'is_admin_category' => false,
                                    'shop_id'           => null,
                                ]);
                            }
                            $categoryId = $category->id;
                        } else {
                            $categoryId = $prod['category_id'];
                        }

                        $item = \App\Models\Item::create([
                            'item_name'     => $prod['new_item_name'],
                            'category_id'   => $categoryId,
                            'brand'         => $prod['brand'] ?? null,
                            'model'         => $prod['model'] ?? null,
                            'specification' => $prod['specification'] ?? null,
                            'is_admin_item' => false,
                            'shop_id'       => null,
                        ]);
                        $itemId = $item->id;
                    } else {
                        $itemId = $prod['item_id'];
                    }

                    // 1. Create Main Stock reference record with remaining_quantity = 0
                    $mainStock = \App\Models\MainStock::create([
                        'item_id'            => $itemId,
                        'buying_price'       => $buyingPrice,
                        'selling_price'      => 2 * $buyingPrice,
                        'stocked_quantity'   => $quantity,
                        'remaining_quantity' => 0,
                        'date_received'      => $dateReceived,
                        'is_price_pending'   => false,
                    ]);

                    // 2. Create Stock Transfer record (status = received)
                    $transfer = \App\Models\StockTransfer::create([
                        'from_store'    => 'Main Warehouse',
                        'to_shop'       => $user->shop_id,
                        'approved_by'   => $user->id,
                        'transfer_date' => $dateReceived,
                        'status'        => 'received',
                    ]);

                    // 3. Create Stock Transfer Item
                    \App\Models\StockTransferItem::create([
                        'transfer_id'   => $transfer->id,
                        'item_id'       => $itemId,
                        'quantity'      => $quantity,
                        'buying_price'  => $buyingPrice,
                        'selling_price' => 2 * $buyingPrice,
                        'status'        => 'received',
                        'received_by'   => $user->id,
                        'received_at'   => now(),
                    ]);

                    // 4. Create Shop Stock entry (is_admin_stock = false)
                    $stock = ShopStock::create([
                        'shop_id'            => $user->shop_id,
                        'item_id'            => $itemId,
                        'buying_price'       => $buyingPrice,
                        'selling_price'      => $sellingPrice,
                        'quantity'           => $quantity,
                        'remaining_quantity' => $quantity,
                        'low_stock_alert'    => 1,
                        'date_received'      => $dateReceived,
                        'is_price_pending'   => false,
                        'is_sellable'        => true,
                        'is_admin_stock'     => false,
                    ]);

                    // 5. Create Stock Log entry
                    \App\Models\StockLog::create([
                        'item_id'          => $stock->item_id,
                        'from_location'    => 'Supplier (Owner)',
                        'to_location'      => $user->shop->shop_name,
                        'quantity'         => $stock->quantity,
                        'transaction_type' => 'STOCK_RECEIVED',
                        'performed_by'     => $user->id,
                        'date'             => $stock->date_received,
                        'notes'            => 'Owner stock added directly to shop by authorized admin',
                        'is_admin_stock'   => false,
                    ]);

                    // 6. Notify sellers of this shop
                    $itemObj = \App\Models\Item::findOrFail($itemId);
                    $sellers = \App\Models\User::where('shop_id', $user->shop_id)
                        ->where('role', 'seller')
                        ->get();
                    foreach ($sellers as $seller) {
                        \App\Models\Notification::create([
                            'user_id' => $seller->id,
                            'title'   => 'New Shop Stock Added',
                            'message' => "Stock added directly for \"{$itemObj->item_name}\" (Qty: {$stock->quantity}) as Owner Stock.",
                        ]);
                    }

                    // Notify owners of this stock addition
                    $owners = \App\Models\User::where('role', 'owner')->get();
                    foreach ($owners as $owner) {
                        \App\Models\Notification::create([
                            'user_id' => $owner->id,
                            'title'   => 'Owner Stock Added by Admin',
                            'message' => "Shop Admin \"{$user->name}\" added new owner stock for \"{$itemObj->item_name}\" (Qty: {$stock->quantity}) directly to shop \"{$user->shop->shop_name}\".",
                        ]);
                    }
                }
            });

            return redirect()->route('shop-stock.index')
                ->with('success', 'Owner stocks added and recorded to main store reference successfully.');
        }

        // Backward compatible flat version
        $createNew = $request->boolean('create_new_product');
        $createNewCategory = $request->boolean('create_new_category');

        if ($createNew) {
            if ($createNewCategory) {
                $rules = [
                    'new_item_name'     => 'required|string|max:150',
                    'new_category_name' => 'required|string|max:100',
                    'brand'             => 'nullable|string|max:100',
                    'model'             => 'nullable|string|max:100',
                    'specification'     => 'nullable|string',
                    'buying_price'      => 'required|numeric|min:0',
                    'selling_price'     => 'required|numeric|min:' . $request->input('buying_price', 0),
                    'quantity'          => 'required|integer|min:1',
                    'date_received'     => 'required|date',
                ];
            } else {
                $rules = [
                    'new_item_name' => 'required|string|max:150',
                    'category_id'   => 'required|exists:categories,id',
                    'brand'         => 'nullable|string|max:100',
                    'model'         => 'nullable|string|max:100',
                    'specification' => 'nullable|string',
                    'buying_price'  => 'required|numeric|min:0',
                    'selling_price' => 'required|numeric|min:' . $request->input('buying_price', 0),
                    'quantity'      => 'required|integer|min:1',
                    'date_received' => 'required|date',
                ];
            }
        } else {
            $rules = [
                'item_id'       => 'required|exists:items,id',
                'buying_price'  => 'required|numeric|min:0',
                'selling_price' => 'required|numeric|min:' . $request->input('buying_price', 0),
                'quantity'      => 'required|integer|min:1',
                'date_received' => 'required|date',
            ];
        }

        $request->validate($rules, [
            'selling_price.min' => 'The selling price must be greater than or equal to the buying price.',
        ]);

        return DB::transaction(function () use ($request, $user, $createNew, $createNewCategory) {
            if ($createNew) {
                if ($createNewCategory) {
                    $categoryName = trim($request->new_category_name);
                    $category = \App\Models\Category::where('is_admin_category', false)
                        ->where('category_name', $categoryName)->first();

                    if (!$category) {
                        $category = \App\Models\Category::create([
                            'category_name'     => $categoryName,
                            'is_admin_category' => false,
                            'shop_id'           => null,
                        ]);
                    }
                    $categoryId = $category->id;
                } else {
                    $categoryId = $request->category_id;
                }

                $item = \App\Models\Item::create([
                    'item_name'     => $request->new_item_name,
                    'category_id'   => $categoryId,
                    'brand'         => $request->brand,
                    'model'         => $request->model,
                    'specification' => $request->specification,
                    'is_admin_item' => false,
                    'shop_id'       => null,
                ]);
                $itemId = $item->id;
            } else {
                $itemId = $request->item_id;
            }

            // 1. Create Main Stock reference record with remaining_quantity = 0
            $mainStock = \App\Models\MainStock::create([
                'item_id'            => $itemId,
                'buying_price'       => $request->buying_price,
                'selling_price'      => 2 * $request->buying_price,
                'stocked_quantity'   => $request->quantity,
                'remaining_quantity' => 0,
                'date_received'      => $request->date_received,
                'is_price_pending'   => false,
            ]);

            // 2. Create Stock Transfer record (status = received)
            $transfer = \App\Models\StockTransfer::create([
                'from_store'    => 'Main Warehouse',
                'to_shop'       => $user->shop_id,
                'approved_by'   => $user->id,
                'transfer_date' => $request->date_received,
                'status'        => 'received',
            ]);

            // 3. Create Stock Transfer Item
            \App\Models\StockTransferItem::create([
                'transfer_id'   => $transfer->id,
                'item_id'       => $itemId,
                'quantity'      => $request->quantity,
                'buying_price'  => $request->buying_price,
                'selling_price' => 2 * $request->buying_price,
                'status'        => 'received',
                'received_by'   => $user->id,
                'received_at'   => now(),
            ]);

            // 4. Create Shop Stock entry (is_admin_stock = false)
            $stock = ShopStock::create([
                'shop_id'            => $user->shop_id,
                'item_id'            => $itemId,
                'buying_price'       => $request->buying_price,
                'selling_price'      => $request->selling_price,
                'quantity'           => $request->quantity,
                'remaining_quantity' => $request->quantity,
                'low_stock_alert'    => 1,
                'date_received'      => $request->date_received,
                'is_price_pending'   => false,
                'is_sellable'        => true,
                'is_admin_stock'     => false,
            ]);

            // 5. Create Stock Log entry
            \App\Models\StockLog::create([
                'item_id'          => $stock->item_id,
                'from_location'    => 'Supplier (Owner)',
                'to_location'      => $user->shop->shop_name,
                'quantity'         => $stock->quantity,
                'transaction_type' => 'STOCK_RECEIVED',
                'performed_by'     => $user->id,
                'date'             => $stock->date_received,
                'notes'            => 'Owner stock added directly to shop by authorized admin',
                'is_admin_stock'   => false,
            ]);

            // 6. Notify sellers of this shop
            $itemObj = \App\Models\Item::findOrFail($itemId);
            $sellers = \App\Models\User::where('shop_id', $user->shop_id)
                ->where('role', 'seller')
                ->get();
            foreach ($sellers as $seller) {
                \App\Models\Notification::create([
                    'user_id' => $seller->id,
                    'title'   => 'New Shop Stock Added',
                    'message' => "Stock added directly for \"{$itemObj->item_name}\" (Qty: {$stock->quantity}) as Owner Stock.",
                ]);
            }

            // Notify owners of this stock addition
            $owners = \App\Models\User::where('role', 'owner')->get();
            foreach ($owners as $owner) {
                \App\Models\Notification::create([
                    'user_id' => $owner->id,
                    'title'   => 'Owner Stock Added by Admin',
                    'message' => "Shop Admin \"{$user->name}\" added new owner stock for \"{$itemObj->item_name}\" (Qty: {$stock->quantity}) directly to shop \"{$user->shop->shop_name}\".",
                ]);
            }

            return redirect()->route('shop-stock.index')
                ->with('success', 'Owner stock added and recorded to main store reference successfully.');
        });
    }

    public function show(ShopStock $shopStock)
    {
        $shopStock->load('item.category', 'shop');
        return view('shop-stock.show', compact('shopStock'));
    }

    public function updateAlert(Request $request, ShopStock $shopStock)
    {
        $request->validate([
            'low_stock_alert' => 'required|integer|min:1',
        ]);
        $shopStock->update(['low_stock_alert' => $request->low_stock_alert]);
        return back()->with('success', 'Low stock alert threshold updated.');
    }

    public function updatePrice(Request $request, ShopStock $shopStock)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isShopAdmin()) {
            abort(403);
        }

        $request->validate([
            'selling_price' => 'required|numeric|min:' . $shopStock->buying_price,
        ], [
            'selling_price.min' => 'The selling price must be greater than or equal to the buying price (TZS ' . number_format($shopStock->buying_price) . ').',
        ]);

        $itemName = $shopStock->item?->item_name ?? 'Item';
        $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT';

        if ($user->isShopAdmin()) {
            if ($isIndependent) {
                // Admin price update is direct in INDEPENDENT mode, bypassing owner approval
                $shopStock->update([
                    'selling_price' => $request->selling_price,
                    'is_price_pending' => false,
                    'pending_selling_price' => null,
                    'is_sellable' => true,
                ]);

                // Notify all sellers of this shop
                $sellers = \App\Models\User::where('shop_id', $shopStock->shop_id)
                    ->where('role', 'seller')
                    ->get();
                foreach ($sellers as $seller) {
                    \App\Models\Notification::create([
                        'user_id' => $seller->id,
                        'title'   => 'Shop Stock Price Updated',
                        'message' => "Admin has updated the selling price for \"{$itemName}\" to: TZS " . number_format($request->selling_price, 2),
                    ]);
                }

                return back()->with('success', 'Selling price updated and item unlocked successfully.');
            }

            // Admin update is pending Owner approval in DEPENDENT mode
            $shopStock->update([
                'is_price_pending' => true,
                'pending_selling_price' => $request->selling_price,
            ]);

            // Notify all owners
            $owners = \App\Models\User::where('role', 'owner')->get();
            foreach ($owners as $owner) {
                \App\Models\Notification::create([
                    'user_id' => $owner->id,
                    'title'   => 'Shop Price Change Pending',
                    'message' => "Admin {$user->name} updated the selling price for \"{$itemName}\" in {$shopStock->shop->shop_name} to: TZS " . number_format($request->selling_price, 2) . ". Pending owner approval.",
                ]);
            }

            return back()->with('success', 'Selling price update is pending owner approval.');
        }

        // Owner update is direct
        $shopStock->update([
            'selling_price' => $request->selling_price,
            'is_price_pending' => false,
            'pending_selling_price' => null,
            'is_sellable' => true,
        ]);

        // Notify all sellers of this shop
        $sellers = \App\Models\User::where('shop_id', $shopStock->shop_id)
            ->where('role', 'seller')
            ->get();
        foreach ($sellers as $seller) {
            \App\Models\Notification::create([
                'user_id' => $seller->id,
                'title'   => 'Shop Stock Price Updated',
                'message' => "Owner has updated the selling price for \"{$itemName}\" to: TZS " . number_format($request->selling_price, 2),
            ]);
        }

        return back()->with('success', 'Selling price updated successfully.');
    }

    public function approvePrice(Request $request, ShopStock $shopStock)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isShopAdmin()) {
            abort(403, 'Unauthorized.');
        }

        if ($user->isShopAdmin() && $shopStock->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized shop.');
        }

        if ($shopStock->is_price_pending) {
            $request->validate([
                'selling_price' => 'nullable|numeric|min:' . $shopStock->buying_price,
            ], [
                'selling_price.min' => 'The selling price must be greater than or equal to the buying price (TZS ' . number_format($shopStock->buying_price) . ').',
            ]);

            $newPrice = $request->filled('selling_price') ? floatval($request->selling_price) : $shopStock->pending_selling_price;

            if ($newPrice < $shopStock->buying_price) {
                return back()->withInput()->withErrors([
                    'selling_price' => 'The selling price must be greater than or equal to the buying price (TZS ' . number_format($shopStock->buying_price) . ').'
                ]);
            }

            $shopStock->update([
                'selling_price' => $newPrice,
                'is_price_pending' => false,
                'pending_selling_price' => null,
                'is_sellable' => true,
            ]);

            // Notify all sellers of this shop
            $sellers = \App\Models\User::where('shop_id', $shopStock->shop_id)
                ->where('role', 'seller')
                ->get();
            $itemName = $shopStock->item?->item_name ?? 'Item';
            foreach ($sellers as $seller) {
                \App\Models\Notification::create([
                    'user_id' => $seller->id,
                    'title'   => 'Shop Stock Price Approved',
                    'message' => "The selling price update for \"{$itemName}\" has been approved to: TZS " . number_format($newPrice, 2),
                ]);
            }

            // Notify other roles of approval
            if ($user->isShopAdmin()) {
                $owners = \App\Models\User::where('role', 'owner')->get();
                foreach ($owners as $owner) {
                    \App\Models\Notification::create([
                        'user_id' => $owner->id,
                        'title'   => 'Shop Price Change Approved by Admin',
                        'message' => "Admin {$user->name} approved the selling price change for \"{$itemName}\" to TZS " . number_format($newPrice, 2) . " in {$shopStock->shop->shop_name}.",
                    ]);
                }
            } else {
                $admins = \App\Models\User::where('shop_id', $shopStock->shop_id)->where('role', 'shop_admin')->get();
                foreach ($admins as $admin) {
                    \App\Models\Notification::create([
                        'user_id' => $admin->id,
                        'title'   => 'Shop Price Change Approved by Owner',
                        'message' => "Owner approved the selling price change for \"{$itemName}\" to TZS " . number_format($newPrice, 2) . " in {$shopStock->shop->shop_name}.",
                    ]);
                }
            }

            return back()->with('success', 'Shop stock price approved successfully.');
        }

        return back()->with('error', 'No pending price change found.');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = [
            'Item Name',
            'Category Name',
            'Brand',
            'Model',
            'Specification',
            'Buying Price',
            'Selling Price',
            'Quantity',
            'Date Received'
        ];
        
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }
        
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        
        $sample = [
            'Wireless Mouse M170',
            'Computer Accessories',
            'Logitech',
            'M170',
            '2.4GHz wireless, 10m range, USB nano receiver',
            '15000',
            '25000',
            '10',
            date('Y-m-d')
        ];
        foreach ($sample as $colIndex => $val) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '2', $val);
        }
        
        foreach (range(1, 9) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
        
        $writer = new Xlsx($spreadsheet);
        
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'shop_stock_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0'
        ]);
    }

    public function exportAvailableStock(Request $request)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isShopAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $shopId = $user->isOwner() ? $request->get('shop_id', null) : $user->shop_id;

        $query = ShopStock::with('item.category', 'shop');

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        if ($user->isOwner()) {
            $query->where('is_admin_stock', false);
        }

        $stocks = $query->latest()->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = [
            'Item Name',
            'Category Name',
            'Brand',
            'Model',
            'Specification',
            'Buying Price',
            'Selling Price',
            'Quantity',
            'Date Received'
        ];
        
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }
        
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        
        $rowNumber = 2;
        foreach ($stocks as $st) {
            $qty = $st->remaining_quantity;
            if ($qty <= 0) {
                continue;
            }

            $item = $st->item;
            $sheet->setCellValue('A' . $rowNumber, $item ? $item->item_name : '');
            $sheet->setCellValue('B' . $rowNumber, ($item && $item->category) ? $item->category->category_name : '');
            $sheet->setCellValue('C' . $rowNumber, $item ? $item->brand : '');
            $sheet->setCellValue('D' . $rowNumber, $item ? $item->model : '');
            $sheet->setCellValue('E' . $rowNumber, $item ? $item->specification : '');
            $sheet->setCellValue('F' . $rowNumber, $st->buying_price);
            $sheet->setCellValue('G' . $rowNumber, $st->selling_price);
            $sheet->setCellValue('H' . $rowNumber, $qty);
            $sheet->setCellValue('I' . $rowNumber, $st->date_received ? $st->date_received->format('Y-m-d') : date('Y-m-d'));
            $rowNumber++;
        }
        
        foreach (range(1, 9) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
        
        $writer = new Xlsx($spreadsheet);
        
        $shop = $shopId ? Shop::find($shopId) : null;
        $shopSlug = $shop ? \Illuminate\Support\Str::slug($shop->shop_name, '_') : 'all_shops';
        $filename = "available_shop_stock_{$shopSlug}_" . date('Y-m-d') . ".xlsx";
        
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0'
        ]);
    }

    public function import(Request $request)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isShopAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $rules = [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ];

        if ($user->isOwner()) {
            $rules['shop_id'] = 'required|exists:shops,id';
        }

        if ($user->isShopAdmin() && $user->allow_stock_addition) {
            $rules['stock_type'] = 'required|in:admin,owner';
        }

        $request->validate($rules);

        $shopId = $user->isOwner() ? $request->shop_id : $user->shop_id;
        $shop = Shop::findOrFail($shopId);
        
        $isShopAdminOwnerStock = false;
        if ($user->isShopAdmin() && $user->allow_stock_addition && $request->get('stock_type') === 'owner') {
            $isShopAdminOwnerStock = true;
        }

        $isAdminStock = $user->isShopAdmin() && !$isShopAdminOwnerStock;

        $file = $request->file('excel_file');
        
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to read spreadsheet file. Please make sure the format is valid.');
        }

        if (count($rows) <= 1) {
            return back()->with('error', 'The spreadsheet does not contain any data rows.');
        }

        $headers = array_map(function($h) {
            return strtolower(trim($h));
        }, $rows[0]);

        $headerMap = [
            'item_name' => ['item name', 'product name', 'item', 'product', 'name'],
            'category_name' => ['category name', 'category', 'category_name'],
            'brand' => ['brand'],
            'model' => ['model'],
            'specification' => ['specification', 'specifications', 'specification details', 'specs'],
            'buying_price' => ['buying price', 'buying_price', 'buy price', 'cost', 'cost price', 'buying'],
            'selling_price' => ['selling price', 'selling_price', 'sell price', 'retail price', 'selling'],
            'quantity' => ['quantity', 'qty', 'stocked_quantity', 'amount', 'count'],
            'date_received' => ['date received', 'date_received', 'date', 'received date'],
        ];

        $indices = [];
        $missingRequired = [];
        $requiredKeys = ['item_name', 'buying_price', 'selling_price', 'quantity'];

        foreach ($headerMap as $key => $aliases) {
            $indices[$key] = -1;
            foreach ($aliases as $alias) {
                $idx = array_search($alias, $headers);
                if ($idx !== false) {
                    $indices[$key] = $idx;
                    break;
                }
            }
            if ($indices[$key] === -1 && in_array($key, $requiredKeys)) {
                $missingRequired[] = ucwords(str_replace('_', ' ', $key));
            }
        }

        if (!empty($missingRequired)) {
            return back()->with('error', 'Missing required columns in spreadsheet: ' . implode(', ', $missingRequired));
        }

        $errors = [];
        $importData = [];

        // First pass: validation
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            $isEmptyRow = true;
            foreach ($row as $cell) {
                if ($cell !== null && trim($cell) !== '') {
                    $isEmptyRow = false;
                    break;
                }
            }
            if ($isEmptyRow) {
                continue;
            }

            $rowNum = $i + 1; // Excel row number (1-based)
            
            $itemName = isset($row[$indices['item_name']]) ? trim($row[$indices['item_name']]) : '';
            if (empty($itemName)) {
                $errors[] = "Row {$rowNum}: Item Name is required.";
                continue;
            }

            // Check if item exists (either standard or admin item for this shop)
            $item = Item::where('item_name', $itemName)
                ->where(function($q) use ($shopId) {
                    $q->where('is_admin_item', false)
                      ->orWhere(function($sq) use ($shopId) {
                          $sq->where('is_admin_item', true)
                             ->where('shop_id', $shopId);
                      });
                })
                ->first();
            
            $categoryName = $indices['category_name'] !== -1 && isset($row[$indices['category_name']]) ? trim($row[$indices['category_name']]) : '';
            if (!$item && empty($categoryName)) {
                $errors[] = "Row {$rowNum}: Product \"{$itemName}\" is new, but Category Name is missing. A category is required to create a new product.";
                continue;
            }

            $buyingPriceStr = isset($row[$indices['buying_price']]) ? trim($row[$indices['buying_price']]) : '';
            $buyingPrice = floatval(str_replace([',', 'TZS', 'tzs', ' '], '', $buyingPriceStr));
            if (!is_numeric(str_replace([',', ' '], '', $buyingPriceStr)) || $buyingPrice < 0) {
                $errors[] = "Row {$rowNum}: Buying Price must be a positive number.";
            }

            $sellingPriceStr = isset($row[$indices['selling_price']]) ? trim($row[$indices['selling_price']]) : '';
            $sellingPrice = floatval(str_replace([',', 'TZS', 'tzs', ' '], '', $sellingPriceStr));
            if (!is_numeric(str_replace([',', ' '], '', $sellingPriceStr)) || $sellingPrice < 0) {
                $errors[] = "Row {$rowNum}: Selling Price must be a positive number.";
            } elseif ($sellingPrice < $buyingPrice) {
                $errors[] = "Row {$rowNum}: Selling Price (TZS " . number_format($sellingPrice) . ") must be greater than or equal to Buying Price (TZS " . number_format($buyingPrice) . ").";
            }

            $quantityStr = isset($row[$indices['quantity']]) ? trim($row[$indices['quantity']]) : '';
            $quantity = intval(str_replace([',', ' '], '', $quantityStr));
            if (!is_numeric(str_replace([',', ' '], '', $quantityStr)) || $quantity < 1) {
                $errors[] = "Row {$rowNum}: Quantity must be a positive integer (minimum 1).";
            }

            // Date parsing
            $dateReceived = now()->toDateString();
            if ($indices['date_received'] !== -1 && isset($row[$indices['date_received']])) {
                $dateVal = trim($row[$indices['date_received']]);
                if (!empty($dateVal)) {
                    if (is_numeric($dateVal) && $dateVal > 40000 && $dateVal < 60000) {
                        try {
                            $dateReceived = Date::excelToDateTimeObject($dateVal)->format('Y-m-d');
                        } catch (\Exception $e) {
                            $dateReceived = now()->toDateString();
                        }
                    } else {
                        $parsedDate = strtotime(str_replace('/', '-', $dateVal));
                        if ($parsedDate !== false) {
                            $dateReceived = date('Y-m-d', $parsedDate);
                        } else {
                            $errors[] = "Row {$rowNum}: Date Received \"{$dateVal}\" is not a valid date format.";
                        }
                    }
                }
            }

            if (empty($errors)) {
                $importData[] = [
                    'item_name' => $itemName,
                    'category_name' => $categoryName,
                    'brand' => $indices['brand'] !== -1 && isset($row[$indices['brand']]) ? trim($row[$indices['brand']]) : null,
                    'model' => $indices['model'] !== -1 && isset($row[$indices['model']]) ? trim($row[$indices['model']]) : null,
                    'specification' => $indices['specification'] !== -1 && isset($row[$indices['specification']]) ? trim($row[$indices['specification']]) : null,
                    'buying_price' => $buyingPrice,
                    'selling_price' => $sellingPrice,
                    'quantity' => $quantity,
                    'date_received' => $dateReceived,
                    'item_object' => $item,
                ];
            }
        }

        if (!empty($errors)) {
            return back()->with('import_errors', $errors);
        }

        // Second pass: database insertion in transaction
        try {
            DB::transaction(function () use ($importData, $shopId, $shop, $isAdminStock, $isShopAdminOwnerStock, $user) {
                foreach ($importData as $data) {
                    $item = $data['item_object'];

                    if (!$item) {
                        // Find category (either standard or admin category for this shop)
                        $category = Category::where('category_name', $data['category_name'])
                            ->where(function($q) use ($shopId) {
                                $q->where('is_admin_category', false)
                                  ->orWhere(function($sq) use ($shopId) {
                                      $sq->where('is_admin_category', true)
                                         ->where('shop_id', $shopId);
                                  });
                            })
                            ->first();

                        if (!$category) {
                            $category = Category::create([
                                'category_name' => $data['category_name'],
                                'is_admin_category' => $isAdminStock,
                                'shop_id' => $isAdminStock ? $shopId : null,
                            ]);
                        }

                        $item = Item::create([
                            'item_name' => $data['item_name'],
                            'category_id' => $category->id,
                            'brand' => $data['brand'],
                            'model' => $data['model'],
                            'specification' => $data['specification'],
                            'is_admin_item' => $isAdminStock,
                            'shop_id' => $isAdminStock ? $shopId : null,
                        ]);
                    }

                    if ($isShopAdminOwnerStock) {
                        // 1. Create Main Stock reference record with remaining_quantity = 0
                        \App\Models\MainStock::create([
                            'item_id'            => $item->id,
                            'buying_price'       => $data['buying_price'],
                            'selling_price'      => 2 * $data['buying_price'],
                            'stocked_quantity'   => $data['quantity'],
                            'remaining_quantity' => 0,
                            'date_received'      => $data['date_received'],
                            'is_price_pending'   => false,
                        ]);

                        // 2. Create Stock Transfer record (status = received)
                        $transfer = \App\Models\StockTransfer::create([
                            'from_store'    => 'Main Warehouse',
                            'to_shop'       => $shopId,
                            'approved_by'   => $user->id,
                            'transfer_date' => $data['date_received'],
                            'status'        => 'received',
                        ]);

                        // 3. Create Stock Transfer Item
                        \App\Models\StockTransferItem::create([
                            'transfer_id'   => $transfer->id,
                            'item_id'       => $item->id,
                            'quantity'      => $data['quantity'],
                            'buying_price'  => $data['buying_price'],
                            'selling_price' => 2 * $data['buying_price'],
                            'status'        => 'received',
                            'received_by'   => $user->id,
                            'received_at'   => now(),
                        ]);
                    }

                    $stock = ShopStock::create([
                        'shop_id' => $shopId,
                        'item_id' => $item->id,
                        'buying_price' => $data['buying_price'],
                        'selling_price' => $data['selling_price'],
                        'quantity' => $data['quantity'],
                        'remaining_quantity' => $data['quantity'],
                        'low_stock_alert' => 1,
                        'date_received' => $data['date_received'],
                        'is_price_pending' => false,
                        'is_sellable' => true,
                        'is_admin_stock' => $isAdminStock,
                    ]);

                    StockLog::create([
                        'item_id' => $item->id,
                        'from_location' => $isAdminStock ? 'Supplier (Admin)' : ($isShopAdminOwnerStock ? 'Supplier (Owner)' : 'Supplier'),
                        'to_location' => $shop->shop_name,
                        'quantity' => $data['quantity'],
                        'transaction_type' => 'STOCK_RECEIVED',
                        'performed_by' => $user->id,
                        'date' => $data['date_received'],
                        'notes' => $isShopAdminOwnerStock ? 'Owner stock added directly to shop by authorized admin (Excel)' : 'Imported from Excel',
                        'is_admin_stock' => $isAdminStock,
                    ]);
                }
            });

            // Send consolidated notification to shop staff and owners
            $roleLabel = $user->isOwner() ? 'Owner' : 'Admin';
            $sellers = User::where('shop_id', $shopId)->where('role', 'seller')->get();
            foreach ($sellers as $seller) {
                Notification::create([
                    'user_id' => $seller->id,
                    'title'   => $isShopAdminOwnerStock ? 'New Shop Stock Added' : 'New Stock Uploaded',
                    'message' => $isShopAdminOwnerStock 
                        ? "Stock added directly from Excel as Owner Stock." 
                        : "{$roleLabel} has imported " . count($importData) . " new stock batches from an Excel sheet to the shop.",
                ]);
            }
            
            if ($isShopAdminOwnerStock) {
                $owners = User::where('role', 'owner')->get();
                foreach ($owners as $owner) {
                    Notification::create([
                        'user_id' => $owner->id,
                        'title'   => 'Owner Stock Added by Admin (Excel)',
                        'message' => "Shop Admin \"{$user->name}\" added " . count($importData) . " owner stock batches directly to shop \"{$shop->shop_name}\" via Excel import.",
                    ]);
                }
            } elseif ($user->isOwner()) {
                $admins = User::where('shop_id', $shopId)->where('role', 'shop_admin')->get();
                foreach ($admins as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'title'   => 'New Stock Uploaded by Owner',
                        'message' => "Owner has imported " . count($importData) . " new stock batches from an Excel sheet to your shop.",
                    ]);
                }
            }

        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred during DB transaction import: ' . $e->getMessage());
        }

        return redirect()->route('shop-stock.index', ['shop_id' => $shopId])
            ->with('success', 'Successfully imported ' . count($importData) . ' stock items.');
    }

    public function edit(ShopStock $shopStock)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !($user->isShopAdmin() && $user->shop_id == $shopStock->shop_id)) {
            abort(403, 'Unauthorized.');
        }

        $shopStock->load('item.category');
        return view('shop-stock.edit', compact('shopStock'));
    }

    public function update(Request $request, ShopStock $shopStock)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !($user->isShopAdmin() && $user->shop_id == $shopStock->shop_id)) {
            abort(403, 'Unauthorized.');
        }

        // If Shop Admin edits stock posted by the Owner (transferred from Main Store)
        if ($user->isShopAdmin() && !$shopStock->is_admin_stock) {
            $request->validate([
                'selling_price' => 'required|numeric|min:' . $shopStock->buying_price,
            ], [
                'selling_price.min' => 'The selling price must be greater than or equal to the buying price (TZS ' . number_format($shopStock->buying_price) . ').',
            ]);

            $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT';
            $itemName = $shopStock->item?->item_name ?? 'Item';

            if ($isIndependent) {
                $shopStock->update([
                    'selling_price'         => $request->selling_price,
                    'is_price_pending'      => false,
                    'pending_selling_price' => null,
                    'is_sellable'           => true,
                ]);

                // Notify sellers of this shop
                $sellers = \App\Models\User::where('shop_id', $shopStock->shop_id)
                    ->where('role', 'seller')
                    ->get();
                foreach ($sellers as $seller) {
                    \App\Models\Notification::create([
                        'user_id' => $seller->id,
                        'title'   => 'Shop Stock Price Updated',
                        'message' => "Admin has updated the selling price for \"{$itemName}\" to: TZS " . number_format($request->selling_price, 2),
                    ]);
                }

                return redirect()->route('shop-stock.index', ['shop_id' => $shopStock->shop_id])
                    ->with('success', 'Selling price updated successfully.');
            } else {
                $shopStock->update([
                    'is_price_pending'      => true,
                    'pending_selling_price' => $request->selling_price,
                ]);

                // Notify all owners
                $owners = \App\Models\User::where('role', 'owner')->get();
                foreach ($owners as $owner) {
                    \App\Models\Notification::create([
                        'user_id' => $owner->id,
                        'title'   => 'Shop Price Change Pending',
                        'message' => "Admin {$user->name} updated the selling price for \"{$itemName}\" in {$shopStock->shop->shop_name} to: TZS " . number_format($request->selling_price, 2) . ". Pending owner approval.",
                    ]);
                }

                return redirect()->route('shop-stock.index', ['shop_id' => $shopStock->shop_id])
                    ->with('success', 'Selling price update is pending owner approval.');
            }
        }

        $oldQty = intval($shopStock->remaining_quantity);
        $newQty = intval($request->remaining_quantity);
        $oldInitialQty = intval($shopStock->quantity);

        $rules = [
            'buying_price'       => 'required|numeric|min:0',
            'selling_price'      => 'required|numeric|min:0|gte:buying_price',
            'date_received'      => 'required|date',
            'remaining_quantity' => 'required|integer|min:0',
        ];

        $request->validate($rules, [
            'selling_price.gte'  => 'The selling price must be greater than or equal to the buying price.',
        ]);

        $diff = $newQty - $oldQty;
        $newInitialQty = $oldInitialQty + $diff;

        $updateData = [
            'buying_price'       => $request->buying_price,
            'selling_price'      => $request->selling_price,
            'remaining_quantity' => $newQty,
            'quantity'           => $newInitialQty,
            'date_received'      => $request->date_received,
        ];

        $shopStock->update($updateData);

        if ($oldQty !== $newQty) {
            StockLog::create([
                'item_id'          => $shopStock->item_id,
                'from_location'    => $shopStock->shop->shop_name,
                'to_location'      => $shopStock->shop->shop_name,
                'quantity'         => abs($newQty - $oldQty),
                'transaction_type' => 'ADJUSTMENT',
                'performed_by'     => Auth::id(),
                'date'             => now(),
                'notes'            => "Shop stock remaining quantity adjusted from {$oldQty} to {$newQty}",
                'is_admin_stock'   => $shopStock->is_admin_stock,
            ]);
        }

        return redirect()->route('shop-stock.index', ['shop_id' => $shopStock->shop_id])
            ->with('success', 'Shop stock updated successfully.');
    }

    public function destroy(ShopStock $shopStock)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !($user->isShopAdmin() && $user->shop_id == $shopStock->shop_id)) {
            abort(403, 'Unauthorized.');
        }

        // Restrict Shop Admin from deleting stock posted by the Owner
        if ($user->isShopAdmin() && !$shopStock->is_admin_stock) {
            return redirect()->route('shop-stock.index')
                ->with('error', 'You cannot delete stock batches posted by the owner.');
        }

        if ($shopStock->quantity != $shopStock->remaining_quantity) {
            return redirect()->route('shop-stock.index', ['shop_id' => $shopStock->shop_id])
                ->with('error', 'Cannot delete stock batch because some items have already been sold or modified.');
        }

        $itemId = $shopStock->item_id;
        $quantity = $shopStock->quantity;
        $shopName = $shopStock->shop->shop_name;
        $shopId = $shopStock->shop_id;
        $isAdminStock = $shopStock->is_admin_stock;

        $shopStock->delete();

        StockLog::create([
            'item_id'          => $itemId,
            'from_location'    => $shopName,
            'to_location'      => 'Supplier (Deleted)',
            'quantity'         => $quantity,
            'transaction_type' => 'ADJUSTMENT',
            'performed_by'     => Auth::id(),
            'date'             => now(),
            'notes'            => 'Shop stock batch deleted and removed from inventory.',
            'is_admin_stock'   => $isAdminStock,
        ]);

        return redirect()->route('shop-stock.index', ['shop_id' => $shopId])
            ->with('success', 'Shop stock batch deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:shop_stocks,id',
        ]);

        $errors = [];
        $validStocks = [];

        foreach ($request->ids as $id) {
            $shopStock = ShopStock::with('item', 'shop')->find($id);
            if (!$shopStock) {
                continue;
            }

            $itemName = $shopStock->item?->item_name ?? 'Product';

            // Authorization check
            if (!$user->isOwner() && !($user->isShopAdmin() && $user->shop_id == $shopStock->shop_id)) {
                $errors[] = "Item '{$itemName}' (Batch #{$shopStock->id}): You are not authorized to delete stock from another shop.";
                continue;
            }

            // Restrict Shop Admin from deleting stock posted by the Owner
            if ($user->isShopAdmin() && !$shopStock->is_admin_stock) {
                $errors[] = "Item '{$itemName}' (Batch #{$shopStock->id}): Shop Admins cannot delete stock posted by the Owner.";
                continue;
            }

            // Restrict deletion if stock quantity has been modified or sold
            if ($shopStock->quantity != $shopStock->remaining_quantity) {
                $soldQty = max(0, $shopStock->quantity - $shopStock->remaining_quantity);
                $errors[] = "Item '{$itemName}' (Batch #{$shopStock->id}): {$soldQty} unit(s) have already been sold or modified.";
                continue;
            }

            // Check pending requests
            if ($shopStock->is_price_pending || !is_null($shopStock->pending_quantity_request)) {
                $errors[] = "Item '{$itemName}' (Batch #{$shopStock->id}): Has an active pending price or quantity approval request.";
                continue;
            }

            $validStocks[] = $shopStock;
        }

        // If any error exists, block the entire bulk deletion operation
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk deletion blocked! The selected items contain stock that cannot be deleted:',
                'errors'  => $errors,
            ], 422);
        }

        if (empty($validStocks)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid stock items were selected for deletion.',
            ], 422);
        }

        $deletedCount = 0;

        \DB::transaction(function () use ($validStocks, $user, &$deletedCount) {
            foreach ($validStocks as $shopStock) {
                $itemId       = $shopStock->item_id;
                $quantity     = $shopStock->quantity;
                $shopName     = $shopStock->shop?->shop_name ?? 'Shop';
                $isAdminStock = $shopStock->is_admin_stock;

                $shopStock->delete();
                $deletedCount++;

                StockLog::create([
                    'item_id'          => $itemId,
                    'from_location'    => $shopName,
                    'to_location'      => 'Supplier (Deleted)',
                    'quantity'         => $quantity,
                    'transaction_type' => 'ADJUSTMENT',
                    'performed_by'     => $user->id,
                    'date'             => now(),
                    'notes'            => 'Shop stock batch deleted via bulk delete.',
                    'is_admin_stock'   => $isAdminStock,
                ]);
            }
        });

        return response()->json([
            'success'       => true,
            'message'       => "Successfully deleted {$deletedCount} stock batch(es).",
            'deleted_count' => $deletedCount,
        ]);
    }

    public function requestEdit(Request $request, ShopStock $shopStock)
    {
        $user = Auth::user();
        if (!$user->isShopAdmin() || $shopStock->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'requested_quantity' => 'required|integer|min:0',
            'reason'             => 'required|string|max:255',
        ]);

        $shopStock->update([
            'pending_quantity_request' => $request->requested_quantity,
            'pending_quantity_reason'  => $request->reason,
        ]);

        $itemName = $shopStock->item?->item_name ?? 'Item';
        $shopName = $user->shop?->shop_name ?? 'Shop';

        $owners = User::where('role', 'owner')->get();
        foreach ($owners as $owner) {
            Notification::create([
                'user_id' => $owner->id,
                'title'   => 'Stock Edit Request',
                'message' => "Admin {$user->name} ({$shopName}) requested to update remaining quantity for \"{$itemName}\" (Batch #{$shopStock->id}) from {$shopStock->remaining_quantity} to {$request->requested_quantity}. Reason: {$request->reason}",
            ]);
        }

        return back()->with('success', 'Your edit request has been sent to the owner.');
    }

    public function approveQuantity(Request $request, ShopStock $shopStock)
    {
        $user = Auth::user();
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized.');
        }

        if (is_null($shopStock->pending_quantity_request)) {
            return back()->with('error', 'No pending quantity request found.');
        }

        $oldQty = intval($shopStock->remaining_quantity);
        $newQty = intval($shopStock->pending_quantity_request);
        $oldInitialQty = intval($shopStock->quantity);
        $reason = $shopStock->pending_quantity_reason;

        $diff = $newQty - $oldQty;
        $newInitialQty = $oldInitialQty + $diff;

        $shopStock->update([
            'remaining_quantity'       => $newQty,
            'quantity'                 => $newInitialQty,
            'pending_quantity_request' => null,
            'pending_quantity_reason'  => null,
        ]);

        if ($oldQty !== $newQty) {
            StockLog::create([
                'item_id'          => $shopStock->item_id,
                'from_location'    => $shopStock->shop->shop_name,
                'to_location'      => $shopStock->shop->shop_name,
                'quantity'         => abs($newQty - $oldQty),
                'transaction_type' => 'ADJUSTMENT',
                'performed_by'     => Auth::id(),
                'date'             => now(),
                'notes'            => "Approved shop stock edit request: adjusted remaining qty from {$oldQty} to {$newQty} (Reason: {$reason})",
                'is_admin_stock'   => $shopStock->is_admin_stock,
            ]);
        }

        // Notify admins of this shop
        $admins = User::where('shop_id', $shopStock->shop_id)->where('role', 'shop_admin')->get();
        $itemName = $shopStock->item?->item_name ?? 'Item';
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title'   => 'Stock Edit Approved',
                'message' => "The owner has approved your request to adjust remaining quantity for \"{$itemName}\" (Batch #{$shopStock->id}) to {$newQty}.",
            ]);
        }

        return back()->with('success', 'Quantity edit request approved successfully.');
    }

    public function rejectQuantity(Request $request, ShopStock $shopStock)
    {
        $user = Auth::user();
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized.');
        }

        if (is_null($shopStock->pending_quantity_request)) {
            return back()->with('error', 'No pending quantity request found.');
        }

        $shopStock->update([
            'pending_quantity_request' => null,
            'pending_quantity_reason'  => null,
        ]);

        // Notify admins of this shop
        $admins = User::where('shop_id', $shopStock->shop_id)->where('role', 'shop_admin')->get();
        $itemName = $shopStock->item?->item_name ?? 'Item';
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title'   => 'Stock Edit Rejected',
                'message' => "The owner has rejected your request to adjust remaining quantity for \"{$itemName}\" (Batch #{$shopStock->id}).",
            ]);
        }

        return back()->with('success', 'Quantity edit request rejected.');
    }

    public function quickRestock(Request $request)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !($user->isShopAdmin() && $user->shop_id == $request->shop_id)) {
            abort(403, 'Unauthorized.');
        }

        if ($user->isOwner()) {
            // ── OWNER: create a proper stock transfer from warehouse ──────────
            $request->validate([
                'shop_id'  => 'required|exists:shops,id',
                'item_id'  => 'required|exists:items,id',
                'quantity' => 'required|integer|min:1',
            ]);

            $itemId  = $request->item_id;
            $shopId  = $request->shop_id;
            $qty     = $request->quantity;
            $item    = \App\Models\Item::findOrFail($itemId);
            $shop    = \App\Models\Shop::findOrFail($shopId);

            // Check warehouse availability
            $available = \App\Models\MainStock::where('item_id', $itemId)
                ->where('remaining_quantity', '>', 0)
                ->sum('remaining_quantity');

            if ($available < $qty) {
                return back()->withInput()->withErrors([
                    'quantity' => "Insufficient warehouse stock for \"{$item->item_name}\". Available: {$available}, Requested: {$qty}.",
                ]);
            }

            // Get pricing from latest available batch
            $mainStock = \App\Models\MainStock::where('item_id', $itemId)
                ->where('remaining_quantity', '>', 0)
                ->orderByDesc('date_received')
                ->first();

            DB::transaction(function () use ($request, $shop, $shopId, $itemId, $qty, $item, $mainStock) {
                // Create transfer record
                $transfer = \App\Models\StockTransfer::create([
                    'from_store'    => 'Main Warehouse',
                    'to_shop'       => $shopId,
                    'approved_by'   => Auth::id(),
                    'request_id'    => null,
                    'transfer_date' => now()->toDateString(),
                    'status'        => 'pending_receipt',
                ]);

                // Deduct from warehouse (FIFO)
                $remaining = $qty;
                $batches   = \App\Models\MainStock::where('item_id', $itemId)
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('date_received')
                    ->get();
                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    $deduct = min($batch->remaining_quantity, $remaining);
                    $batch->decrement('remaining_quantity', $deduct);
                    $remaining -= $deduct;
                }

                // Create transfer item
                \App\Models\StockTransferItem::create([
                    'transfer_id'   => $transfer->id,
                    'item_id'       => $itemId,
                    'quantity'      => $qty,
                    'buying_price'  => $mainStock?->buying_price ?? 0,
                    'selling_price' => $mainStock?->selling_price ?? 0,
                    'status'        => 'pending',
                ]);

                // Audit log
                StockLog::create([
                    'item_id'          => $itemId,
                    'from_location'    => 'Main Warehouse',
                    'to_location'      => $shop->shop_name,
                    'quantity'         => $qty,
                    'transaction_type' => 'STOCK_TRANSFER',
                    'performed_by'     => Auth::id(),
                    'date'             => now()->toDateString(),
                    'notes'            => "Quick restock: dispatched {$qty} units of \"{$item->item_name}\" to {$shop->shop_name} (Transfer #{$transfer->id})",
                ]);

                // Notify shop admins
                $shopAdmins = User::where('shop_id', $shopId)->where('role', 'shop_admin')->get();
                foreach ($shopAdmins as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'title'   => 'Quick Restock Dispatched',
                        'message' => "Owner dispatched {$qty} unit(s) of \"{$item->item_name}\" to your shop via quick restock (Transfer #{$transfer->id}). Please confirm receipt.",
                    ]);
                }
            });

            return redirect()->route('stock-transfers.index')
                ->with('success', "Quick restock dispatched: {$qty} unit(s) of \"{$item->item_name}\" sent to {$shop->shop_name}. Awaiting shop admin receipt confirmation.");
        }

        // ── SHOP ADMIN: direct admin stock restock ───────────────────────
        $request->validate([
            'shop_id'            => 'required|exists:shops,id',
            'item_id'            => 'required|exists:items,id',
            'buying_price'       => 'required|numeric|min:0',
            'selling_price'      => 'required|numeric|min:0',
            'quantity'           => 'required|integer|min:1',
            'date_received'      => 'required|date',
            'low_stock_alert'    => 'required|integer|min:0',
        ]);

        $shopStock = ShopStock::create([
            'shop_id'            => $request->shop_id,
            'item_id'            => $request->item_id,
            'buying_price'       => $request->buying_price,
            'selling_price'      => $request->selling_price,
            'quantity'           => $request->quantity,
            'remaining_quantity' => $request->quantity,
            'date_received'      => $request->date_received,
            'low_stock_alert'    => $request->low_stock_alert,
            'is_admin_stock'     => true,
            'is_sellable'        => true,
        ]);

        StockLog::create([
            'item_id'          => $shopStock->item_id,
            'from_location'    => 'Admin Restock',
            'to_location'      => $shopStock->shop->shop_name,
            'quantity'         => $shopStock->quantity,
            'transaction_type' => 'STOCK_RECEIVED',
            'performed_by'     => Auth::id(),
            'date'             => $shopStock->date_received,
            'notes'            => "Quick restocked {$shopStock->quantity} units for product \"{$shopStock->item->item_name}\" (Admin Stock)",
            'is_admin_stock'   => true,
        ]);

        return redirect()->route('shop-stock.index', ['shop_id' => $shopStock->shop_id])
            ->with('success', 'Admin stock quick restocked successfully.');
    }

    /**
     * Return available warehouse quantity for a given item (AJAX).
     */
    public function warehouseAvailable(Request $request)
    {
        $user = Auth::user();
        if (!$user->isOwner()) {
            abort(403, 'Unauthorized.');
        }

        $itemId    = $request->input('item_id');
        $available = \App\Models\MainStock::where('item_id', $itemId)
            ->where('remaining_quantity', '>', 0)
            ->sum('remaining_quantity');

        return response()->json(['available' => (int) $available]);
    }
}

