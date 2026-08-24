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
        $categories = Category::where('is_admin_category', false)->withCount('items')->latest()->get();
        return view('categories.index', compact('categories'));
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
