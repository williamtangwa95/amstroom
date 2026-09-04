<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MainStock;
use App\Models\StockLog;
use App\Models\Category;
use App\Services\MainStoreStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MainStockController extends Controller
{
    public function index()
    {
        $stats = [
            'totalInitialCost'   => MainStock::selectRaw('SUM(stocked_quantity * buying_price) as val')->value('val') ?? 0,
            'totalInitialSell'   => MainStock::selectRaw('SUM(stocked_quantity * selling_price) as val')->value('val') ?? 0,
            'totalRemainingCost' => MainStock::selectRaw('SUM(remaining_quantity * buying_price) as val')->value('val') ?? 0,
            'totalRemainingSell' => MainStock::selectRaw('SUM(remaining_quantity * selling_price) as val')->value('val') ?? 0,
            'totalInitialQty'    => MainStock::sum('stocked_quantity') ?? 0,
            'totalRemainingQty'  => MainStock::sum('remaining_quantity') ?? 0,
            'stockBatchesCount'  => MainStock::count(),
        ];

        return view('main-stock.index', compact('stats'));
    }

    public function data(Request $request)
    {
        $query = MainStock::with(['item.category']);

        $recordsTotal = MainStock::count();

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('item', function ($sq) use ($searchValue) {
                    $sq->where('item_name', 'like', "%{$searchValue}%")
                       ->orWhere('brand', 'like', "%{$searchValue}%")
                       ->orWhere('model', 'like', "%{$searchValue}%")
                       ->orWhereHas('category', function ($cq) use ($searchValue) {
                           $cq->where('category_name', 'like', "%{$searchValue}%");
                       });
                })
                ->orWhere('buying_price', 'like', "%{$searchValue}%")
                ->orWhere('selling_price', 'like', "%{$searchValue}%")
                ->orWhere('stocked_quantity', 'like', "%{$searchValue}%")
                ->orWhere('remaining_quantity', 'like', "%{$searchValue}%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderColumnIndex = $request->input('order.0.column', 8);
        $orderDirection = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        switch ((int) $orderColumnIndex) {
            case 2:
                $query->join('items', 'items.id', '=', 'main_stocks.item_id')
                      ->orderBy('items.item_name', $orderDirection)
                      ->select('main_stocks.*');
                break;
            case 3:
                $query->join('items', 'items.id', '=', 'main_stocks.item_id')
                      ->join('categories', 'categories.id', '=', 'items.category_id')
                      ->orderBy('categories.category_name', $orderDirection)
                      ->select('main_stocks.*');
                break;
            case 4:
                $query->orderBy('buying_price', $orderDirection);
                break;
            case 5:
                $query->orderBy('selling_price', $orderDirection);
                break;
            case 6:
                $query->orderBy('stocked_quantity', $orderDirection);
                break;
            case 7:
                $query->orderBy('remaining_quantity', $orderDirection);
                break;
            case 8:
                $query->orderBy('date_received', $orderDirection);
                break;
            default:
                $query->orderBy('id', 'desc');
                break;
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->skip($start)->take($length);
        }

        $stocks = $query->get();

        $data = [];
        foreach ($stocks as $index => $stock) {
            $rowNumber = $start + $index + 1;

            $checkbox = '<input type="checkbox" class="stock-checkbox" data-id="' . $stock->id . '" style="cursor:pointer;">';

            $item = $stock->item;
            $itemName = e($item?->item_name ?? 'N/A');
            $itemBrand = e($item?->brand ?? '');
            $escapedTitle = addslashes($itemName);

            if ($item && $item->image_path) {
                $imgUrl = asset('media/' . $item->image_path);
                $productHtml = '
                    <div class="d-flex align-items-center gap-2">
                        <div class="product-img-wrapper overflow-hidden rounded position-relative" style="width: 36px; height: 36px; flex-shrink: 0; cursor: pointer; border: 1px solid var(--card-border);" onclick="zoomProductImage(\'' . $imgUrl . '\', \'' . $escapedTitle . '\')" title="Click to zoom image">
                            <img src="' . $imgUrl . '" alt="' . $itemName . '" class="product-img-thumb w-100 h-100" style="object-fit: cover; transition: transform 0.25s ease;">
                            <div class="img-zoom-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0, 0, 0, 0.45); opacity: 0; transition: opacity 0.2s ease;">
                                <i class="bi bi-zoom-in text-white fs-6"></i>
                            </div>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:.83rem;">' . $itemName . '</div>
                            <div style="font-size:.7rem;color:var(--text-secondary);">' . $itemBrand . '</div>
                        </div>
                    </div>';
            } else {
                $productHtml = '
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded d-flex align-items-center justify-content-center bg-light text-muted" style="width: 36px; height: 36px; border: 1px solid var(--card-border); flex-shrink: 0;">
                            <i class="bi bi-image" style="font-size: 0.8rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:.83rem;">' . $itemName . '</div>
                            <div style="font-size:.7rem;color:var(--text-secondary);">' . $itemBrand . '</div>
                        </div>
                    </div>';
            }

            $categoryName = e($item?->category?->category_name ?? 'Uncategorized');
            $categoryHtml = '<span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;">' . $categoryName . '</span>';

            $buyPriceHtml = '<span style="font-size:.82rem;">TZS ' . number_format($stock->buying_price, 0) . '</span>';
            $sellPriceHtml = '<span style="font-size:.82rem;">TZS ' . number_format($stock->selling_price, 0) . '</span>';
            $stockedQtyHtml = '<span style="font-size:.82rem;">' . $stock->stocked_quantity . '</span>';

            $remColor = $stock->remaining_quantity > 0 ? '#3fb950' : '#e94560';
            $remainingQtyHtml = '<strong style="color:' . $remColor . ';">' . $stock->remaining_quantity . '</strong>';

            $dateHtml = '<span style="font-size:.75rem;color:var(--text-secondary);">' . ($stock->date_received ? $stock->date_received->format('M d, Y') : 'N/A') . '</span>';

            $showRoute = route('main-stock.show', $stock->id);
            $editRoute = route('main-stock.edit', $stock->id);
            $destroyRoute = route('main-stock.destroy', $stock->id);

            $actionsHtml = '<div class="d-flex align-items-center gap-2">
                <a href="' . $showRoute . '" class="btn btn-xs btn-outline-custom" title="View details"><i class="bi bi-eye"></i></a>
                <a href="' . $editRoute . '" class="btn btn-xs btn-outline-custom" title="Edit batch"><i class="bi bi-pencil"></i></a>';

            if ($stock->stocked_quantity == $stock->remaining_quantity) {
                $actionsHtml .= '
                <form action="' . $destroyRoute . '" method="POST" class="d-inline delete-stock-form">
                    ' . csrf_field() . '
                    ' . method_field('DELETE') . '
                    <button type="button" class="btn btn-xs btn-outline-danger confirm-delete-btn" title="Delete stock batch">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>';
            }

            $checked = $stock->allow_components ? 'checked' : '';
            $actionsHtml .= '
                <div class="form-check form-switch ms-1 mb-0 d-flex align-items-center">
                    <input class="form-check-input toggle-components-btn" type="checkbox" data-id="' . $stock->id . '" style="cursor:pointer; width: 30px; height: 16px;" ' . $checked . ' title="Toggle custom components capability">
                </div>
            </div>';

            $data[] = [
                'checkbox'          => $checkbox,
                'no'                => $rowNumber,
                'product'           => $productHtml,
                'category'          => $categoryHtml,
                'buying_price'      => $buyPriceHtml,
                'selling_price'     => $sellPriceHtml,
                'stocked_quantity'  => $stockedQtyHtml,
                'remaining_quantity'=> $remainingQtyHtml,
                'date_received'     => $dateHtml,
                'actions'           => $actionsHtml,
            ];
        }

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    public function create()
    {
        $items = Item::with(['category', 'mainStock'])->where('is_admin_item', false)->orderBy('item_name')->get();
        return view('main-stock.create', compact('items'));
    }

    public function store(Request $request, MainStoreStockService $stockService)
    {
        $request->validate([
            'item_id'          => 'required|exists:items,id',
            'buying_price'     => 'required|numeric|min:0',
            'selling_price'    => 'required|numeric|min:0|gte:buying_price',
            'stocked_quantity' => 'required|integer|min:1',
            'date_received'    => 'required|date',
        ], [
            'selling_price.gte' => 'The selling price must be greater than or equal to the buying price.',
        ]);

        $result = $stockService->processStockAddition(
            (int) $request->item_id,
            (int) $request->stocked_quantity,
            (float) $request->buying_price,
            (float) $request->selling_price,
            $request->date_received,
            Auth::id(),
            'Manual Main Store addition'
        );

        return redirect()->route('main-stock.index')
            ->with('success', $result['flash_message']);
    }

    public function show(MainStock $mainStock)
    {
        $mainStock->load('item.category');
        return view('main-stock.show', compact('mainStock'));
    }

    public function history()
    {
        $logs = StockLog::with('item.category', 'performer')
            ->whereIn('transaction_type', ['STOCK_RECEIVED', 'STOCK_TRANSFER', 'ADJUSTMENT'])
            ->latest()
            ->get();
        return view('main-stock.history', compact('logs'));
    }

    public function edit(MainStock $mainStock)
    {
        $items = Item::with('category')->where('is_admin_item', false)->orderBy('item_name')->get();
        return view('main-stock.edit', compact('mainStock', 'items'));
    }

    public function update(Request $request, MainStock $mainStock)
    {
        $oldQty = intval($mainStock->remaining_quantity);
        $oldInitialQty = intval($mainStock->stocked_quantity);
        $newQty = $request->has('remaining_quantity') ? intval($request->remaining_quantity) : $oldQty;

        $rules = [
            'buying_price'       => 'required|numeric|min:0',
            'selling_price'      => 'required|numeric|min:0|gte:buying_price',
            'date_received'      => 'required|date',
        ];

        if ($request->has('remaining_quantity')) {
            if ($oldQty === $oldInitialQty) {
                $rules['remaining_quantity'] = 'required|integer|min:0';
            } else {
                $rules['remaining_quantity'] = 'required|integer|min:0|max:' . $oldInitialQty;
            }
        }

        $request->validate($rules, [
            'selling_price.gte'      => 'The selling price must be greater than or equal to the buying price.',
            'remaining_quantity.max' => 'The remaining quantity cannot exceed the stocked quantity (' . $oldInitialQty . ').',
        ]);

        $newSellingPrice = floatval($request->selling_price);
        $oldSellingPrice = floatval($mainStock->selling_price);

        $updateData = [
            'buying_price'       => $request->buying_price,
            'selling_price'      => $request->selling_price,
            'remaining_quantity' => $newQty,
            'date_received'      => $request->date_received,
        ];

        if ($request->has('remaining_quantity') && $oldQty === $oldInitialQty) {
            $updateData['stocked_quantity'] = $newQty;
        }

        $mainStock->update($updateData);

        if ($oldQty !== $newQty) {
            StockLog::create([
                'item_id'          => $mainStock->item_id,
                'from_location'    => 'Main Warehouse',
                'to_location'      => 'Main Warehouse',
                'quantity'         => abs($newQty - $oldQty),
                'transaction_type' => 'ADJUSTMENT',
                'performed_by'     => Auth::id(),
                'date'             => now(),
                'notes'            => "Stock batch remaining quantity adjusted from {$oldQty} to {$newQty}",
            ]);
        }

        if ($newSellingPrice != $oldSellingPrice) {
            $itemName = $mainStock->item?->item_name ?? 'Item';
            $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'INDEPENDENT') === 'INDEPENDENT';

            // Find all shop stocks for this item
            $shopStocks = \App\Models\ShopStock::where('item_id', $mainStock->item_id)->get();
            foreach ($shopStocks as $shopStock) {
                if ($isIndependent) {
                    $shopStock->update([
                        'buying_price' => $newSellingPrice,
                        'is_sellable' => false,
                        'is_price_pending' => true,
                        'pending_selling_price' => null,
                    ]);

                    // Notify both shop admins and sellers
                    $usersToNotify = \App\Models\User::where('shop_id', $shopStock->shop_id)
                        ->whereIn('role', ['shop_admin', 'seller'])
                        ->get();
                    foreach ($usersToNotify as $user) {
                        \App\Models\Notification::create([
                            'user_id' => $user->id,
                            'title'   => 'Main Store Price Updated',
                            'message' => "Main Store updated transfer price for {$itemName}. Please review and update your Selling Price to restore sales eligibility.",
                        ]);
                    }
                } else {
                    $shopStock->update([
                        'is_price_pending' => true,
                        'pending_selling_price' => $newSellingPrice,
                    ]);

                    // Notify all admins of this shop
                    $admins = \App\Models\User::where('shop_id', $shopStock->shop_id)
                        ->where('role', 'shop_admin')
                        ->get();
                    foreach ($admins as $admin) {
                        \App\Models\Notification::create([
                            'user_id' => $admin->id,
                            'title'   => 'Main Store Price Updated',
                            'message' => "Owner updated the selling price for \"{$itemName}\" to TZS " . number_format($newSellingPrice, 2) . ". This is pending your approval.",
                        ]);
                    }
                }
            }
        }

        return redirect()->route('main-stock.index')
            ->with('success', 'Stock updated successfully. Associated shop stock prices are now pending admin approval.');
    }

    public function destroy(MainStock $mainStock)
    {
        if ($mainStock->stocked_quantity != $mainStock->remaining_quantity) {
            return redirect()->route('main-stock.index')
                ->with('error', 'Cannot delete stock batch because some items have already been transferred or sold.');
        }

        $itemId = $mainStock->item_id;
        $quantity = $mainStock->stocked_quantity;

        $mainStock->delete();

        StockLog::create([
            'item_id'          => $itemId,
            'from_location'    => 'Main Warehouse',
            'to_location'      => 'Supplier (Deleted)',
            'quantity'         => $quantity,
            'transaction_type' => 'ADJUSTMENT',
            'performed_by'     => Auth::id(),
            'date'             => now(),
            'notes'            => 'Stock batch deleted and removed from central warehouse.',
        ]);

        return redirect()->route('main-stock.index')
            ->with('success', 'Stock batch deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->isOwner()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Only store owner can delete main warehouse stock.'], 403);
            }
            abort(403, 'Only store owner can delete main warehouse stock.');
        }

        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:main_stocks,id',
        ]);

        $errors = [];
        $validStocks = [];

        foreach ($request->ids as $id) {
            $stock = MainStock::with('item')->find($id);
            if (!$stock) continue;

            $itemName = $stock->item?->item_name ?? 'Item';

            if ($stock->stocked_quantity != $stock->remaining_quantity) {
                $transferredOrSold = $stock->stocked_quantity - $stock->remaining_quantity;
                $errors[] = "Batch #{$stock->id} ({$itemName}): Cannot delete because {$transferredOrSold} unit(s) have already been transferred or sold.";
                continue;
            }

            $validStocks[] = $stock;
        }

        if (!empty($errors) && empty($validStocks)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bulk deletion failed:',
                    'errors'  => $errors,
                ], 422);
            }
            return back()->with('error', implode(' ', $errors));
        }

        $deletedCount = 0;
        DB::transaction(function () use ($validStocks, $user, &$deletedCount) {
            foreach ($validStocks as $mainStock) {
                $itemId = $mainStock->item_id;
                $quantity = $mainStock->stocked_quantity;

                $mainStock->delete();

                StockLog::create([
                    'item_id'          => $itemId,
                    'from_location'    => 'Main Warehouse',
                    'to_location'      => 'Supplier (Bulk Deleted)',
                    'quantity'         => $quantity,
                    'transaction_type' => 'ADJUSTMENT',
                    'performed_by'     => $user->id,
                    'date'             => now(),
                    'notes'            => 'Stock batch bulk deleted and removed from central warehouse.',
                ]);

                $deletedCount++;
            }
        });

        $successMsg = "Successfully deleted {$deletedCount} stock batch(es) from central warehouse.";
        if (!empty($errors)) {
            $successMsg .= " Skipped " . count($errors) . " batch(es) that had existing sales/transfers.";
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'warnings'=> $errors,
            ]);
        }

        return redirect()->route('main-stock.index')->with('success', $successMsg);
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
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="main_stock_import_template.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

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
            
            // Check if row is completely empty
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

            // Check if item exists
            $item = Item::where('item_name', $itemName)->where('is_admin_item', false)->first();
            
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
        $stockService = app(MainStoreStockService::class);
        try {
            DB::transaction(function () use ($importData, $stockService) {
                foreach ($importData as $data) {
                    $item = $data['item_object'];

                    if (!$item) {
                        $category = Category::where('category_name', $data['category_name'])
                            ->where('is_admin_category', false)
                            ->first();

                        if (!$category) {
                            $category = Category::create([
                                'category_name' => $data['category_name'],
                                'is_admin_category' => false,
                                'shop_id' => null,
                            ]);
                        }

                        $item = Item::create([
                            'item_name' => $data['item_name'],
                            'category_id' => $category->id,
                            'brand' => $data['brand'],
                            'model' => $data['model'],
                            'specification' => $data['specification'],
                            'is_admin_item' => false,
                            'shop_id' => null,
                        ]);
                    }

                    $stockService->processStockAddition(
                        (int) $item->id,
                        (int) $data['quantity'],
                        (float) $data['buying_price'],
                        (float) $data['selling_price'],
                        $data['date_received'],
                        Auth::id(),
                        'Imported from Excel'
                    );
                }
            });
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred during DB transaction import: ' . $e->getMessage());
        }

        return redirect()->route('main-stock.index')
            ->with('success', 'Successfully imported ' . count($importData) . ' stock items.');
    }
}
