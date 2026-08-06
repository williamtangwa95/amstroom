<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop;
use App\Models\Item;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveChatTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;
    protected $shop;
    protected $seller;
    protected $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        // Create a shop
        $this->shop = Shop::create([
            'shop_name' => 'Dar es Salaam Shop',
            'location' => 'Dar es Salaam',
            'status' => 'active'
        ]);

        // Create owner and seller
        $this->owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'phone' => '255700000001',
            'password' => bcrypt('password'),
            'role' => 'owner'
        ]);

        $this->seller = User::create([
            'name' => 'Seller User',
            'email' => 'seller@example.com',
            'phone' => '255700000002',
            'password' => bcrypt('password'),
            'role' => 'seller',
            'shop_id' => $this->shop->id
        ]);

        // Create product category and item
        $category = Category::create([
            'category_name' => 'Laptops',
            'description' => 'Notebook computers'
        ]);

        $this->item = Item::create([
            'item_name' => 'HP EliteBook 840 G8',
            'category_id' => $category->id,
            'brand' => 'HP',
            'model' => '840 G8'
        ]);
    }

    public function test_authenticated_user_can_access_chat_page()
    {
        $this->actingAs($this->seller);

        $response = $this->get(route('chats.index'));
        $response->assertStatus(200);
        $response->assertViewHas('users');
    }

    public function test_user_can_send_chat_message()
    {
        $this->actingAs($this->seller);

        $response = $this->postJson(route('chats.send'), [
            'message' => 'Hello Owner!',
            'receiver_id' => $this->owner->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $this->seller->id,
            'receiver_id' => $this->owner->id,
            'message' => 'Hello Owner!',
            'type' => 'individual'
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->owner->id,
            'title' => 'New Chat Message',
            'message' => 'New message from Seller User: "Hello Owner!"'
        ]);
    }

    public function test_user_can_fetch_messages()
    {
        $this->actingAs($this->seller);

        // Create seed message
        ChatMessage::create([
            'sender_id' => $this->owner->id,
            'receiver_id' => $this->seller->id,
            'message' => 'Welcome message',
            'type' => 'individual'
        ]);

        $response = $this->getJson(route('chats.messages', [
            'receiver_id' => $this->owner->id
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['messages', 'current_user_id']);
        $this->assertCount(1, $response->json('messages'));
    }

    public function test_user_can_inquire_product()
    {
        $this->actingAs($this->seller);

        $response = $this->postJson(route('chats.inquire'), [
            'product_id' => $this->item->id,
            'receiver_id' => $this->owner->id,
            'note' => 'Is this item available?'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $this->seller->id,
            'receiver_id' => $this->owner->id,
            'type' => 'product_inquiry',
            'product_id' => $this->item->id
        ]);

        // Notification should reach the intended owner with new wording
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->owner->id,
            'title'   => 'Product Inquiry',
            'message' => 'Seller User (Dar es Salaam Shop) is inquiring about "HP EliteBook 840 G8" — do you have it in stock?'
        ]);

        // Assert that the owner gets an unread badge count for this inquiry
        $badgeResponse = $this->actingAs($this->owner)
            ->getJson(route('chats.unread-badge'));
        
        $badgeResponse->assertStatus(200);
        $this->assertEquals(1, $badgeResponse->json('unread'));
        $this->assertEquals(1, $badgeResponse->json('unread_by_sender.' . $this->seller->id . '.count'));
    }

    public function test_group_product_inquiry_notifies_all_users()
    {
        // Seller sends a group inquiry (no receiver_id)
        $response = $this->actingAs($this->seller)
            ->postJson(route('chats.inquire'), [
                'product_id' => $this->item->id,
                'receiver_id' => null,
                'note' => 'Anyone have this in stock?'
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // A group inquiry message should have receiver_id = null
        $this->assertDatabaseHas('chat_messages', [
            'sender_id'   => $this->seller->id,
            'receiver_id' => null,
            'type'        => 'product_inquiry',
            'product_id'  => $this->item->id
        ]);

        // The owner (another system user) should receive a notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->owner->id,
            'title'   => 'Product Inquiry',
        ]);

        // The sender (seller) should NOT receive their own notification
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->seller->id,
            'title'   => 'Product Inquiry',
        ]);
    }

    public function test_owner_can_send_sms_broadcast_sandbox()
    {
        $this->actingAs($this->owner);

        // Make sure settings has SMS disabled (sandbox)
        \App\Models\Setting::set('sms_enabled', '0');

        $response = $this->postJson(route('chats.send-sms'), [
            'message' => 'Emergency update!',
            'recipient_type' => 'all'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Checked that SMS is logged in database
        $this->assertDatabaseHas('sms_logs', [
            'sender_id' => $this->owner->id,
            'phone_number' => '255700000002', // Seller phone
            'message' => 'Emergency update!',
            'status' => 'logged_only'
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->seller->id,
            'title' => 'SMS Notification Received',
            'message' => 'SMS: Emergency update!'
        ]);
    }

    public function test_seller_cannot_send_sms()
    {
        $this->actingAs($this->seller);

        $response = $this->postJson(route('chats.send-sms'), [
            'message' => 'Seller trying to broadcast',
            'recipient_type' => 'all'
        ]);

        $response->assertStatus(403);
    }

    public function test_product_inquiry_stock_visibility_rules()
    {
        // 1. Seller creates a product inquiry to the Owner
        $inquiry = ChatMessage::create([
            'sender_id' => $this->seller->id,
            'receiver_id' => $this->owner->id,
            'type' => 'product_inquiry',
            'product_id' => $this->item->id,
            'message' => 'HP EliteBook 840 G8 inquiry',
            'metadata' => [
                'item_name' => 'HP EliteBook 840 G8',
                'stocks' => [
                    ['shop_name' => 'Main Store (Owner)', 'quantity' => 10, 'price' => 1200000],
                    ['shop_name' => 'Kariakoo Branch',   'quantity' => 5,  'price' => 1250000],
                    ['shop_name' => 'Dar es Salaam Shop','quantity' => 2,  'price' => 1300000]
                ]
            ],
        ]);

        // 2. Seller fetches the conversation - should see show_stocks as false (no reply yet)
        // and show_own_stock_only as true, with ONLY their own shop (Dar es Salaam Shop) visible.
        $response = $this->actingAs($this->seller)
            ->getJson(route('chats.messages', ['receiver_id' => $this->owner->id]));
        
        $response->assertStatus(200);
        $fetchedInquiry = collect($response->json('messages'))->firstWhere('id', $inquiry->id);
        $this->assertFalse($fetchedInquiry['show_stocks']);
        $this->assertTrue($fetchedInquiry['show_own_stock_only']);
        
        $sellerStocks = $fetchedInquiry['metadata']['stocks'];
        $this->assertCount(1, $sellerStocks);
        $this->assertEquals('Dar es Salaam Shop', $sellerStocks[0]['shop_name']);
        $this->assertEquals(2, $sellerStocks[0]['quantity']);
        // Own shop price should be visible
        $this->assertEquals(1300000, $sellerStocks[0]['price']);

        // 3. Owner fetches the conversation - should see show_stocks as true (is the receiver)
        // and all stock entries should be visible
        $response = $this->actingAs($this->owner)
            ->getJson(route('chats.messages', ['receiver_id' => $this->seller->id]));
        
        $response->assertStatus(200);
        $fetchedInquiry = collect($response->json('messages'))->firstWhere('id', $inquiry->id);
        $this->assertTrue($fetchedInquiry['show_stocks']);
        
        $ownerStocks = $fetchedInquiry['metadata']['stocks'];
        $this->assertCount(3, $ownerStocks);
        $this->assertEquals('Main Store (Owner)', $ownerStocks[0]['shop_name']);

        // 4. Owner replies to the seller
        ChatMessage::create([
            'sender_id' => $this->owner->id,
            'receiver_id' => $this->seller->id,
            'type' => 'individual',
            'message' => 'Yes, it is available!',
        ]);

        // 5. Seller fetches the conversation again - should now see show_stocks as true (reply sent!)
        // and should see other sub-shops (Kariakoo Branch) in addition to their own shop.
        $response = $this->actingAs($this->seller)
            ->getJson(route('chats.messages', ['receiver_id' => $this->owner->id]));
        
        $response->assertStatus(200);
        $fetchedInquiry = collect($response->json('messages'))->firstWhere('id', $inquiry->id);
        $this->assertTrue($fetchedInquiry['show_stocks']);
        
        $sellerStocksAfterReply = $fetchedInquiry['metadata']['stocks'];
        $this->assertCount(2, $sellerStocksAfterReply); // Kariakoo Branch & Dar es Salaam Shop

        // Kariakoo Branch (not own shop) - price should be null, quantity visible
        $kariakoo = collect($sellerStocksAfterReply)->firstWhere('shop_name', 'Kariakoo Branch');
        $this->assertNull($kariakoo['price']);
        $this->assertEquals(5, $kariakoo['quantity']);

        // Dar es Salaam Shop (own shop) - price should remain visible
        $ownShop = collect($sellerStocksAfterReply)->firstWhere('shop_name', 'Dar es Salaam Shop');
        $this->assertEquals(1300000, $ownShop['price']);
    }
}
