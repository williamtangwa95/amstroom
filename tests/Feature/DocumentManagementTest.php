<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\Item;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected Shop $shop;
    protected Sale $completedSale;
    protected Sale $draftSale;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'Amstroom Branch',
            'location'  => 'Dar es Salaam',
        ]);

        $this->owner = User::create([
            'name'     => 'System Owner',
            'email'    => 'owner_doc@test.com',
            'password' => bcrypt('password'),
            'role'     => 'owner',
        ]);

        $this->admin = User::create([
            'name'     => 'Shop Admin',
            'email'    => 'admin_doc@test.com',
            'password' => bcrypt('password'),
            'role'     => 'shop_admin',
            'shop_id'  => $this->shop->id,
        ]);

        $category = Category::create(['category_name' => 'Laptops']);
        $item = Item::create([
            'item_name'   => 'Dell Latitude 5420',
            'category_id' => $category->id,
        ]);

        $this->completedSale = Sale::create([
            'shop_id'        => $this->shop->id,
            'seller_id'      => $this->admin->id,
            'customer_name'  => 'Acme Corp',
            'payment_method' => 'bank_transfer',
            'total_amount'   => 1450000,
            'sale_date'      => today(),
            'status'         => 'completed',
            'is_admin_stock' => false,
        ]);

        SaleItem::create([
            'sale_id'       => $this->completedSale->id,
            'item_id'       => $item->id,
            'quantity'      => 1,
            'selling_price' => 1450000,
        ]);

        $this->draftSale = Sale::create([
            'shop_id'        => $this->shop->id,
            'seller_id'      => $this->admin->id,
            'customer_name'  => 'Pending Client',
            'payment_method' => 'cash',
            'total_amount'   => 900000,
            'sale_date'      => today(),
            'status'         => 'draft_proforma',
            'is_admin_stock' => false,
        ]);
    }

    public function test_documents_index_and_sale_id_lookup_work_independently()
    {
        $this->actingAs($this->admin);

        $this->get(route('documents.index'))
            ->assertStatus(200)
            ->assertSee('Documents Center');

        $lookup = $this->getJson(route('documents.lookup-sale', ['sale_id' => '#SL-' . $this->completedSale->id]));
        $lookup->assertStatus(200)
            ->assertJsonPath('found', true)
            ->assertJsonPath('sale.id', $this->completedSale->id)
            ->assertJsonPath('sale.customer_name', 'Acme Corp');
    }

    public function test_can_create_invoice_proforma_and_delivery_note_by_sale_id()
    {
        $this->actingAs($this->admin);

        // 1. Create Invoice
        $invRes = $this->postJson(route('documents.store'), [
            'type'             => 'invoice',
            'sale_id'          => (string) $this->completedSale->id,
            'customer_name'    => 'Acme Corp Ltd',
            'terms_of_payment' => 'Net 14 Days',
        ]);
        $invRes->assertStatus(200)->assertJson(['success' => true]);
        $this->assertStringStartsWith('INV-', $invRes->json('document_number'));

        // 2. Create Delivery Note
        $dnRes = $this->postJson(route('documents.store'), [
            'type'       => 'delivery',
            'sale_id'    => '#SL-' . $this->completedSale->id,
            'deliver_to' => 'Posta HQ, Dar es Salaam',
        ]);
        $dnRes->assertStatus(200)->assertJson(['success' => true]);
        $this->assertStringStartsWith('DN-', $dnRes->json('document_number'));

        // 3. Create Proforma for draft sale
        $pfRes = $this->postJson(route('documents.store'), [
            'type'    => 'proforma',
            'sale_id' => (string) $this->draftSale->id,
        ]);
        $pfRes->assertStatus(200)->assertJson(['success' => true]);
        $this->assertStringStartsWith('PF-', $pfRes->json('document_number'));

        // 4. Reject Invoice for uncompleted draft_proforma sale
        $badInv = $this->postJson(route('documents.store'), [
            'type'    => 'invoice',
            'sale_id' => (string) $this->draftSale->id,
        ]);
        $badInv->assertStatus(422);

        // 5. Printable document page loads cleanly
        $doc = Document::where('type', 'invoice')->firstOrFail();
        $this->get(route('documents.print', $doc))
            ->assertStatus(200)
            ->assertSee($doc->document_number)
            ->assertSee('Acme Corp Ltd');

        // 6. Deleting document does NOT delete underlying Sale
        $this->delete(route('documents.destroy', $doc))
            ->assertRedirect(route('documents.index'));
        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
        $this->assertDatabaseHas('sales', ['id' => $this->completedSale->id, 'deleted_at' => null]);
    }
}
