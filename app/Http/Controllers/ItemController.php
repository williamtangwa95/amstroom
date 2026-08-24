<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with('category')->where('is_admin_item', false)->latest()->get();
        return view('items.index', compact('items'));
    }

    public function create()
    {
        $categories = Category::where('is_admin_category', false)->orderBy('category_name')->get();
        return view('items.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_name'      => 'required|string|max:150',
            'category_id'    => 'required|exists:categories,id',
            'specification'  => 'nullable|string',
            'brand'          => 'nullable|string|max:100',
            'model'          => 'nullable|string|max:100',
            'warranty_period'=> 'nullable|string|max:50',
            'image'          => 'nullable|image|max:1024',
        ]);

        $data = $request->only(
            'item_name', 'category_id', 'specification', 'brand', 'model', 'warranty_period'
        );

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('items', 'public');
        }

        Item::create($data);

        return redirect()->route('items.index')
            ->with('success', 'Item registered successfully.');
    }

    public function show(Item $item)
    {
        $item->load('category', 'mainStocks', 'shopStocks.shop', 'components.childItem');
        
        $currentComponentsIds = $item->components->pluck('component_item_id')->toArray();
        $allItems = Item::where('is_admin_item', false)
            ->where('id', '!=', $item->id)
            ->whereNotIn('id', $currentComponentsIds)
            ->orderBy('item_name')
            ->get();

        return view('items.show', compact('item', 'allItems'));
    }

    public function edit(Item $item)
    {
        $categories = Category::where('is_admin_category', false)->orderBy('category_name')->get();
        return view('items.edit', compact('item', 'categories'));
    }

    public function update(Request $request, Item $item)
    {
        $request->validate([
            'item_name'      => 'required|string|max:150',
            'category_id'    => 'required|exists:categories,id',
            'specification'  => 'nullable|string',
            'brand'          => 'nullable|string|max:100',
            'model'          => 'nullable|string|max:100',
            'warranty_period'=> 'nullable|string|max:50',
            'image'          => 'nullable|image|max:1024',
        ]);

        $data = $request->only(
            'item_name', 'category_id', 'specification', 'brand', 'model', 'warranty_period'
        );

        if ($request->hasFile('image')) {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }
            $data['image_path'] = $request->file('image')->store('items', 'public');
        }

        $item->update($data);

        return redirect()->route('items.index')
            ->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }
        $item->delete();
        return redirect()->route('items.index')
            ->with('success', 'Item deleted successfully.');
    }

    public function uploadImage(Request $request, Item $item)
    {
        $request->validate([
            'image' => 'required|image|max:1024', // max 1MB (1024KB)
        ]);

        if ($request->hasFile('image')) {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }
            $path = $request->file('image')->store('items', 'public');
            $item->update(['image_path' => $path]);

            return back()->with('success', 'Product image uploaded successfully.');
        }

        return back()->with('error', 'Failed to upload image.');
    }

    public function addComponent(Request $request, Item $item)
    {
        $request->validate([
            'component_item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $componentId = $request->component_item_id;
        if ($componentId == $item->id) {
            return back()->with('error', 'An item cannot be a component of itself.');
        }

        $exists = \App\Models\ItemComponent::where('parent_item_id', $item->id)
            ->where('component_item_id', $componentId)
            ->exists();

        if ($exists) {
            return back()->with('error', 'This item is already a component.');
        }

        \App\Models\ItemComponent::create([
            'parent_item_id' => $item->id,
            'component_item_id' => $componentId,
            'quantity' => $request->quantity,
        ]);

        return back()->with('success', 'Component added successfully.');
    }

    public function removeComponent(Item $item, \App\Models\ItemComponent $component)
    {
        if ($component->parent_item_id !== $item->id) {
            abort(403, 'Unauthorized component deletion.');
        }

        $component->delete();

        return back()->with('success', 'Component removed successfully.');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = [
            'Product Name',
            'Category Name',
            'Brand',
            'Model',
            'Specification',
            'Warranty Period'
        ];
        
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }
        
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        
        $sample = [
            'Wireless Mouse M170',
            'Computer Accessories',
            'Logitech',
            'M170',
            '2.4GHz wireless, 10m range, USB nano receiver',
            '1 Year'
        ];
        foreach ($sample as $colIndex => $val) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '2', $val);
        }
        
        foreach (range(1, 6) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
        
        $writer = new Xlsx($spreadsheet);
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'product_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0'
        ]);
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
            'item_name' => ['product name', 'item name', 'product_name', 'item_name', 'name', 'product', 'item'],
            'category_name' => ['category name', 'category', 'category_name', 'category_id'],
            'brand' => ['brand'],
            'model' => ['model'],
            'specification' => ['specification', 'specifications', 'specification details', 'specs'],
            'warranty_period' => ['warranty period', 'warranty_period', 'warranty'],
        ];

        $indices = [];
        $missingRequired = [];
        $requiredKeys = ['item_name', 'category_name'];

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

            $rowNum = $i + 1;
            
            $itemName = isset($row[$indices['item_name']]) ? trim($row[$indices['item_name']]) : '';
            if (empty($itemName)) {
                $errors[] = "Row {$rowNum}: Product Name is required.";
                continue;
            }

            $categoryName = isset($row[$indices['category_name']]) ? trim($row[$indices['category_name']]) : '';
            if (empty($categoryName)) {
                $errors[] = "Row {$rowNum}: Category Name is required.";
                continue;
            }

            $importData[] = [
                'rowNum' => $rowNum,
                'item_name' => $itemName,
                'category_name' => $categoryName,
                'brand' => $indices['brand'] !== -1 && isset($row[$indices['brand']]) ? trim($row[$indices['brand']]) : null,
                'model' => $indices['model'] !== -1 && isset($row[$indices['model']]) ? trim($row[$indices['model']]) : null,
                'specification' => $indices['specification'] !== -1 && isset($row[$indices['specification']]) ? trim($row[$indices['specification']]) : null,
                'warranty_period' => $indices['warranty_period'] !== -1 && isset($row[$indices['warranty_period']]) ? trim($row[$indices['warranty_period']]) : null,
            ];
        }

        if (!empty($errors)) {
            return back()->with('error', 'Validation failed: ' . implode(' | ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? '... and ' . (count($errors) - 5) . ' more errors.' : ''));
        }

        if (empty($importData)) {
            return back()->with('error', 'No valid rows found to import.');
        }

        try {
            DB::transaction(function() use ($importData) {
                foreach ($importData as $data) {
                    // Check if category exists, otherwise create it
                    $category = Category::where('is_admin_category', false)
                        ->where('category_name', $data['category_name'])
                        ->first();
                    if (!$category) {
                        $category = Category::create([
                            'category_name' => $data['category_name'],
                            'is_admin_category' => false
                        ]);
                    }

                    // Check if item exists, otherwise create it
                    $item = Item::where('is_admin_item', false)
                        ->where('item_name', $data['item_name'])
                        ->first();
                    if (!$item) {
                        Item::create([
                            'item_name' => $data['item_name'],
                            'category_id' => $category->id,
                            'brand' => $data['brand'],
                            'model' => $data['model'],
                            'specification' => $data['specification'],
                            'warranty_period' => $data['warranty_period'],
                            'is_admin_item' => false
                        ]);
                    } else {
                        // Update existing item attributes
                        $item->update([
                            'category_id' => $category->id,
                            'brand' => $data['brand'] ?? $item->brand,
                            'model' => $data['model'] ?? $item->model,
                            'specification' => $data['specification'] ?? $item->specification,
                            'warranty_period' => $data['warranty_period'] ?? $item->warranty_period,
                        ]);
                    }
                }
            });

            return redirect()->route('items.index')->with('success', 'Products imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to import products: ' . $e->getMessage());
        }
    }
}
