<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelImportProductsAndCategoriesTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->owner = User::create([
            'name'     => 'Stock Owner',
            'email'    => 'owner@example.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);
    }

    public function test_categories_import_template_downloads_successfully()
    {
        $this->actingAs($this->owner);

        $response = $this->get(route('categories.import-template'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_categories_imported_successfully()
    {
        $this->actingAs($this->owner);

        // Generate temporary excel spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Category Name');
        $sheet->setCellValue('B1', 'Description');
        $sheet->setCellValue('A2', 'Smart Home Gadgets');
        $sheet->setCellValue('B2', 'All smart automation accessories');

        $filePath = tempnam(sys_get_temp_dir(), 'cat_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $uploadedFile = new UploadedFile($filePath, 'categories.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->post(route('categories.import'), [
            'excel_file' => $uploadedFile
        ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'category_name' => 'Smart Home Gadgets',
            'description' => 'All smart automation accessories',
            'is_admin_category' => false
        ]);

        unlink($filePath);
    }

    public function test_items_import_template_downloads_successfully()
    {
        $this->actingAs($this->owner);

        $response = $this->get(route('items.import-template'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_items_imported_successfully_creating_categories_on_the_fly()
    {
        $this->actingAs($this->owner);

        // Generate temporary excel spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Product Name');
        $sheet->setCellValue('B1', 'Category Name');
        $sheet->setCellValue('C1', 'Brand');
        $sheet->setCellValue('D1', 'Model');
        $sheet->setCellValue('E1', 'Specification');
        $sheet->setCellValue('F1', 'Warranty Period');

        $sheet->setCellValue('A2', 'Samsung Galaxy S26 Ultra');
        $sheet->setCellValue('B2', 'Mobile Devices');
        $sheet->setCellValue('C2', 'Samsung');
        $sheet->setCellValue('D2', 'S26 Ultra');
        $sheet->setCellValue('E2', '12GB RAM, 512GB Storage, Snapdragon Gen 5');
        $sheet->setCellValue('F2', '2 Years');

        $filePath = tempnam(sys_get_temp_dir(), 'item_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $uploadedFile = new UploadedFile($filePath, 'products.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->post(route('items.import'), [
            'excel_file' => $uploadedFile
        ]);

        $response->assertRedirect(route('items.index'));
        $response->assertSessionHas('success');

        // Verify category was created on the fly
        $category = Category::where('category_name', 'Mobile Devices')->first();
        $this->assertNotNull($category);

        // Verify item was created
        $this->assertDatabaseHas('items', [
            'item_name' => 'Samsung Galaxy S26 Ultra',
            'category_id' => $category->id,
            'brand' => 'Samsung',
            'model' => 'S26 Ultra',
            'specification' => '12GB RAM, 512GB Storage, Snapdragon Gen 5',
            'warranty_period' => '2 Years',
            'is_admin_item' => false
        ]);

        unlink($filePath);
    }
}
