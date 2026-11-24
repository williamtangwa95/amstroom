<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEditViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $employee;
    protected Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $this->shop = Shop::create([
            'shop_name' => 'City Store',
            'location'  => 'Downtown',
        ]);

        $this->owner = User::create([
            'name'     => 'Owner User',
            'email'    => 'owner@editview-test.com',
            'password' => bcrypt('password123'),
            'role'     => 'owner',
        ]);

        $this->employee = User::create([
            'name'     => 'John Doe',
            'email'    => 'johndoe@editview-test.com',
            'password' => bcrypt('secretpassword'),
            'role'     => 'seller',
            'shop_id'  => $this->shop->id,
            'status'   => 'active',
        ]);
    }

    public function test_user_edit_page_does_not_show_password_fields(): void
    {
        $response = $this->actingAs($this->owner)->get(route('users.edit', $this->employee));

        $response->assertStatus(200);
        $response->assertSee('Edit: John Doe');
        $response->assertSee('Full Name');
        $response->assertSee('Account Status');

        // Verify password fields are removed
        $response->assertDontSee('New Password');
        $response->assertDontSee('Confirm New Password');
        $response->assertDontSee('name="password"', false);
        $response->assertDontSee('name="password_confirmation"', false);
    }

    public function test_user_can_be_updated_without_password(): void
    {
        $oldPasswordHash = $this->employee->password;

        $response = $this->actingAs($this->owner)->put(route('users.update', $this->employee), [
            'name'    => 'Johnathan Doe',
            'email'   => 'johndoe@editview-test.com',
            'phone'   => '0712345678',
            'role'    => 'seller',
            'shop_id' => $this->shop->id,
            'status'  => 'active',
        ]);

        $response->assertRedirect(route('users.index'));

        $this->employee->refresh();
        $this->assertEquals('Johnathan Doe', $this->employee->name);
        $this->assertEquals('0712345678', $this->employee->phone);
        // Password hash remains unchanged
        $this->assertEquals($oldPasswordHash, $this->employee->password);
    }
}
