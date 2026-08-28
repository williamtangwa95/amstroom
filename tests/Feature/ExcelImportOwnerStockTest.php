<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ExcelImportOwnerStockTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'Legacy Shop',
            'location'  => 'Mbezi Beach',
        ]);

        $this->admin = User::create([
            'name'     => 'Shop Admin',
            'email'    => 'admin@example.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shop->id,
            'allow_stock_addition' => true,
        ]);
    }

    public function test_shop_admin_can_upload_excel_for_owner_stock()
    {
        $this->actingAs($this->admin);

        // 1. Create a dummy spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = [
            'Item Name', 'Category Name', 'Brand', 'Model', 'Specification',
            'Buying Price', 'Selling Price', 'Quantity', 'Date Received'
        ];
        
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }
        
        $row = [
            'Wireless Keyboard K400', 'Computer Accessories', 'Logitech', 'K400', 'Touchpad keyboard',
            '45000', '65000', '5', '2026-08-21'
        ];
        foreach ($row as $colIndex => $val) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '2', $val);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'excel_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        $uploadedFile = new UploadedFile(
            $tempFile,
            'test_import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        // 2. Perform the upload request as 'owner' stock
        $response = $this->post(route('shop-stock.import'), [
            'excel_file' => $uploadedFile,
            'stock_type' => 'owner',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        // Clean up temp file
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }

        // 3. Verify standard (Owner) stock was created
        $this->assertDatabaseHas('shop_stocks', [
            'shop_id' => $this->shop->id,
            'buying_price' => 45000,
            'selling_price' => 65000,
            'quantity' => 5,
            'is_admin_stock' => false, // Owner stock!
        ]);

        // Verify MainStock reference record was created
        $this->assertDatabaseHas('main_stocks', [
            'buying_price' => 22500,
            'selling_price' => 45000,
            'stocked_quantity' => 5,
        ]);

        // Verify StockTransfer record was created
        $this->assertDatabaseHas('stock_transfers', [
            'to_shop' => $this->shop->id,
            'status' => 'received',
        ]);
    }

    public function test_user_can_export_available_stock()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('shop-stock.export-available'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
