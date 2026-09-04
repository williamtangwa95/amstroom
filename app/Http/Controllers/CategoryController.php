<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index');
    }

    public function data(Request $request)
    {
        $user = auth()->user();
        $categoriesQuery = Category::query();

        if ($user && $user->isShopAdmin()) {
            $categoriesQuery->where(function ($q) use ($user) {
                $q->where('is_admin_category', false)
                  ->orWhere(function ($sq) use ($user) {
                      $sq->where('is_admin_category', true)
                         ->where('shop_id', $user->shop_id);
                  });
            });
        } else {
            $categoriesQuery->where('is_admin_category', false);
        }

        $recordsTotal = (clone $categoriesQuery)->count();

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $categoriesQuery->where(function ($q) use ($searchValue) {
                $q->orWhere('category_name', 'like', "%{$searchValue}%")
                  ->orWhere('description', 'like', "%{$searchValue}%");
            });
        }

        $recordsFiltered = (clone $categoriesQuery)->count();

        if ($user && $user->isShopAdmin()) {
            $categoriesQuery->withCount(['items' => function ($q) use ($user) {
                $q->where(function ($sq) use ($user) {
                    $sq->where('is_admin_item', false)
                       ->orWhere(function ($ssq) use ($user) {
                           $ssq->where('is_admin_item', true)
                               ->where('shop_id', $user->shop_id);
                       });
                });
            }]);

            $categoriesQuery->withCount(['items as available_items_count' => function ($q) use ($user) {
                $q->where(function ($sq) use ($user) {
                    $sq->where('is_admin_item', false)
                       ->orWhere(function ($ssq) use ($user) {
                           $ssq->where('is_admin_item', true)
                               ->where('shop_id', $user->shop_id);
                       });
                })->whereHas('shopStocks', function ($sq) use ($user) {
                    $sq->where('shop_id', $user->shop_id)
                       ->where('remaining_quantity', '>', 0);
                });
            }]);
        } else {
            $categoriesQuery->withCount(['items' => function ($q) {
                $q->where('is_admin_item', false);
            }]);

            $categoriesQuery->withCount(['items as available_items_count' => function ($q) {
                $q->where('is_admin_item', false)
                  ->where(function ($sq) {
                      $sq->whereHas('mainStocks', function ($msq) {
                          $msq->where('remaining_quantity', '>', 0);
                      })->orWhereHas('shopStocks', function ($ssq) {
                          $ssq->where('remaining_quantity', '>', 0);
                      });
                  });
            }]);
        }

        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [10, 25, 50, 100];
        $requestedLength = (int) $request->input('length', 10);
        $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

        $categories = $categoriesQuery->orderBy('id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($categories as $index => $cat) {
            $iteration = $start + $index + 1;
            $nameHtml = '<strong style="font-size:.85rem;">' . e($cat->category_name) . '</strong>';
            $descHtml = '<span style="font-size:.8rem;color:var(--text-secondary);">' . (e(\Illuminate\Support\Str::limit($cat->description, 60)) ?: '—') . '</span>';
            $badgeTitle = e($cat->available_items_count . ' in stock / ' . $cat->items_count . ' total');
            $productsHtml = '<span style="background:rgba(88,166,255,.12);color:#58a6ff;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;" title="' . $badgeTitle . '">'
                . e($cat->available_items_count . ' available / ' . $cat->items_count . ' total') . '</span>';
            $createdHtml = '<span style="font-size:.75rem;color:var(--text-secondary);">' . ($cat->created_at ? $cat->created_at->format('M d, Y') : '—') . '</span>';

            $actions = '<div class="d-flex gap-1">
                <a href="' . route('categories.edit', $cat) . '" class="btn btn-xs btn-outline-custom" title="Edit"><i class="bi bi-pencil"></i></a>
                <form method="POST" action="' . route('categories.destroy', $cat) . '" id="del-cat-' . $cat->id . '" class="d-inline">
                    ' . csrf_field() . method_field('DELETE') . '
                    <button type="button" class="btn btn-xs btn-outline-custom"
                        data-confirm="Delete category?"
                        data-text="This will fail if the category has items."
                        data-form="del-cat-' . $cat->id . '">
                        <i class="bi bi-trash" style="color:#e94560;"></i>
                    </button>
                </form>
            </div>';

            $data[] = [
                'no' => $iteration,
                'category_name' => $nameHtml,
                'description' => $descHtml,
                'products' => $productsHtml,
                'created_at' => $createdHtml,
                'actions' => $actions,
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:100|unique:categories,category_name',
            'description'   => 'nullable|string|max:500',
        ]);

        Category::create($request->only('category_name', 'description'));

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'category_name' => 'required|string|max:100|unique:categories,category_name,' . $category->id,
            'description'   => 'nullable|string|max:500',
        ]);

        $category->update($request->only('category_name', 'description'));

        return redirect()->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->items()->count() > 0) {
            return back()->with('error', 'Cannot delete category with existing items.');
        }
        $category->delete();
        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = [
            'Category Name',
            'Description'
        ];
        
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }
        
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        
        $sample = [
            'Computer Accessories',
            'Keyboards, mice, webcams, and other peripheral devices'
        ];
        foreach ($sample as $colIndex => $val) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '2', $val);
        }
        
        foreach (range(1, 2) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
        
        $writer = new Xlsx($spreadsheet);
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'category_import_template.xlsx', [
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
            'category_name' => ['category name', 'category_name', 'category', 'name'],
            'description' => ['description', 'desc', 'details', 'description details'],
        ];

        $indices = [];
        $missingRequired = [];
        $requiredKeys = ['category_name'];

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
            
            $catName = isset($row[$indices['category_name']]) ? trim($row[$indices['category_name']]) : '';
            if (empty($catName)) {
                $errors[] = "Row {$rowNum}: Category Name is required.";
                continue;
            }

            $importData[] = [
                'rowNum' => $rowNum,
                'category_name' => $catName,
                'description' => $indices['description'] !== -1 && isset($row[$indices['description']]) ? trim($row[$indices['description']]) : null,
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
                        Category::create([
                            'category_name' => $data['category_name'],
                            'description' => $data['description'],
                            'is_admin_category' => false
                        ]);
                    } else {
                        // Update existing category description
                        $category->update([
                            'description' => $data['description'] ?? $category->description,
                        ]);
                    }
                }
            });

            return redirect()->route('categories.index')->with('success', 'Categories imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to import categories: ' . $e->getMessage());
        }
    }
}
