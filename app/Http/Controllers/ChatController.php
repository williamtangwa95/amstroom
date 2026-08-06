<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\Item;
use App\Models\ShopStock;
use App\Models\MainStock;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Display the live chat interface.
     */
    public function index()
    {
        $currentUser = Auth::user();

        // Get all system users grouped by shop with unread counts and oldest unread time
        $users = User::with('shop')
            ->where('id', '!=', $currentUser->id)
            ->get()
            ->map(function ($user) use ($currentUser) {
                $unreadQuery = ChatMessage::where('sender_id', $user->id)
                    ->where('receiver_id', $currentUser->id)
                    ->where('is_read', false)
                    ->whereIn('type', ['individual', 'product_inquiry']);

                $user->unread_count = $unreadQuery->count();
                $oldestUnread = $unreadQuery->oldest()->first();
                $user->oldest_unread_time = $oldestUnread ? $oldestUnread->created_at->timestamp : null;
                return $user;
            })
            ->sort(function ($a, $b) {
                // 1. Unread count > 0 first
                if ($a->unread_count > 0 && $b->unread_count == 0) return -1;
                if ($a->unread_count == 0 && $b->unread_count > 0) return 1;

                // 2. Both unread, oldest first (ascending time)
                if ($a->unread_count > 0 && $b->unread_count > 0) {
                    return $a->oldest_unread_time <=> $b->oldest_unread_time;
                }

                // 3. No unread, alphabetical by shop then user name
                $shopA = $a->shop ? $a->shop->shop_name : 'Owner / Main Store';
                $shopB = $b->shop ? $b->shop->shop_name : 'Owner / Main Store';
                if ($shopA !== $shopB) {
                    return strcmp($shopA, $shopB);
                }
                return strcmp($a->name, $b->name);
            })
            ->values();

        return view('chats.index', compact('users'));
    }

    /**
     * Fetch messages for a specific conversation/channel.
     */
    public function fetchMessages(Request $request)
    {
        $currentUser = Auth::user();
        $receiverId = $request->query('receiver_id'); // Can be null or 'group'
        $lastId = $request->query('last_id', 0);

        $query = ChatMessage::with(['sender.shop', 'product', 'replyTo.sender.shop']);

        if (!$receiverId || $receiverId === 'group') {
            // Group chat (# Refreshment Room) - regular group posts or group inquiries
            $query->where(function ($q) {
                $q->where('type', 'group')
                  ->orWhere(function ($sub) {
                      $sub->where('type', 'product_inquiry')->whereNull('receiver_id');
                  });
            });
        } else {
            // Mark received messages in this conversation as read
            ChatMessage::where('sender_id', $receiverId)
                ->where('receiver_id', $currentUser->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            // Individual chat (regular chats or dedicated product inquiries)
            $query->where(function ($q) use ($currentUser, $receiverId) {
                $q->where(function ($inner) use ($currentUser, $receiverId) {
                    $inner->where('sender_id', $currentUser->id)->where('receiver_id', $receiverId);
                })->orWhere(function ($inner) use ($currentUser, $receiverId) {
                    $inner->where('sender_id', $receiverId)->where('receiver_id', $currentUser->id);
                });
            })->whereIn('type', ['individual', 'product_inquiry']);
        }

        if ($lastId > 0) {
            $query->where('id', '>', $lastId);
        } else {
            $query->latest()->limit(50);
        }

        $messages = $query->get();

        // If loading initial messages, sort them chronologically
        if ($lastId == 0) {
            $messages = $messages->reverse()->values();
        }

        foreach ($messages as $msg) {
            if ($msg->type === 'product_inquiry') {
                if (!$msg->receiver_id) {
                    // Group inquiry: sender shows only if someone else replied, others show always
                    if ($currentUser->id === $msg->sender_id) {
                        $hasReply = ChatMessage::where('type', 'group')
                            ->where('sender_id', '!=', $msg->sender_id)
                            ->where('id', '>', $msg->id)
                            ->exists();
                        $msg->show_stocks = $hasReply;
                    } else {
                        $msg->show_stocks = true;
                    }
                } else {
                    // Direct inquiry: receiver shows always
                    if ($currentUser->id === $msg->receiver_id) {
                        $msg->show_stocks = true;
                    } else {
                        // Sender shows only if the receiver has replied
                        $hasReply = ChatMessage::where('sender_id', $msg->receiver_id)
                            ->where('receiver_id', $msg->sender_id)
                            ->where('id', '>', $msg->id)
                            ->exists();
                        $msg->show_stocks = $hasReply;
                    }
                }

                // Hide Main Store stock entry from sellers
                if ($currentUser->isSeller()) {
                    $metadata = $msg->metadata;
                    if ($metadata && isset($metadata['stocks'])) {
                        $metadata['stocks'] = array_values(array_filter($metadata['stocks'], function ($st) {
                            return $st['shop_name'] !== 'Main Store (Owner)';
                        }));
                        $msg->metadata = $metadata;
                    }
                }

                // Hide prices of other shops from sellers and shop admins
                if ($currentUser->isSeller() || $currentUser->isShopAdmin()) {
                    $metadata = $msg->metadata;
                    if ($metadata && isset($metadata['stocks'])) {
                        $userShopName = $currentUser->shop ? $currentUser->shop->shop_name : null;
                        foreach ($metadata['stocks'] as &$st) {
                            if ($st['shop_name'] !== $userShopName) {
                                $st['price'] = null;
                            }
                        }
                        unset($st);
                        $msg->metadata = $metadata;
                    }
                }

                // Show only own shop stock if show_stocks is false and user is the sender
                $msg->show_own_stock_only = false;
                if ($currentUser->id === $msg->sender_id && !$msg->show_stocks) {
                    $metadata = $msg->metadata;
                    if ($metadata && isset($metadata['stocks'])) {
                        $userShopName = $currentUser->shop ? $currentUser->shop->shop_name : null;
                        $metadata['stocks'] = array_values(array_filter($metadata['stocks'], function ($st) use ($userShopName) {
                            return $userShopName && $st['shop_name'] === $userShopName;
                        }));
                        $msg->show_own_stock_only = true;
                        $msg->metadata = $metadata;
                    }
                }
            }
        }

        return response()->json([
            'messages' => $messages,
            'current_user_id' => $currentUser->id
        ]);
    }

    /**
     * Send a regular chat message.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message'     => 'required|string',
            'receiver_id' => 'nullable',
            'reply_to_id' => 'nullable|exists:chat_messages,id',
        ]);

        $currentUser = Auth::user();
        $receiverId  = $request->input('receiver_id');
        $type        = (!$receiverId || $receiverId === 'group') ? 'group' : 'individual';

        $chat = ChatMessage::create([
            'sender_id'   => $currentUser->id,
            'receiver_id' => $type === 'individual' ? $receiverId : null,
            'message'     => $request->input('message'),
            'type'        => $type,
            'is_read'     => false,
            'reply_to_id' => $request->input('reply_to_id') ?: null,
        ]);

        if ($type === 'individual') {
            \App\Models\Notification::create([
                'user_id' => $receiverId,
                'title' => 'New Chat Message',
                'message' => "New message from {$currentUser->name}: \"{$chat->message}\"",
                'is_read' => false,
                'is_played' => false
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $chat->load(['sender.shop', 'product'])
        ]);
    }

    /**
     * Send a direct message to multiple selected recipients at once.
     */
    public function sendBulkMessage(Request $request)
    {
        $request->validate([
            'message'      => 'required|string|max:2000',
            'receiver_ids' => 'required|array|min:1',
            'receiver_ids.*' => 'required|exists:users,id',
        ]);

        $currentUser = Auth::user();
        $messageText = $request->input('message');
        $receiverIds = array_unique($request->input('receiver_ids'));

        $sent = [];
        foreach ($receiverIds as $receiverId) {
            if ((int)$receiverId === $currentUser->id) continue; // skip self

            $chat = ChatMessage::create([
                'sender_id'   => $currentUser->id,
                'receiver_id' => $receiverId,
                'message'     => $messageText,
                'type'        => 'individual',
                'is_read'     => false,
            ]);

            \App\Models\Notification::create([
                'user_id'   => $receiverId,
                'title'     => 'New Chat Message',
                'message'   => "New message from {$currentUser->name}: \"{$messageText}\"",
                'is_read'   => false,
                'is_played' => false,
            ]);

            $sent[] = $chat->load('sender.shop');
        }

        return response()->json([
            'success' => true,
            'sent'    => count($sent),
            'messages'=> $sent,
        ]);
    }

    /**
     * Search items for product inquiry.
     */
    public function searchItems(Request $request)
    {
        $query = $request->query('query');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $items = Item::where('item_name', 'like', "%{$query}%")
            ->orWhere('brand', 'like', "%{$query}%")
            ->orWhere('model', 'like', "%{$query}%")
            ->limit(10)
            ->get();

        return response()->json($items);
    }

    /**
     * Inquire a product's stock availability across stores.
     */
    public function inquireProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:items,id',
            'receiver_id' => 'nullable', // user ID or empty for group
            'note' => 'nullable|string|max:255'
        ]);

        $currentUser = Auth::user();
        $productId = $request->input('product_id');
        $receiverId = $request->input('receiver_id');
        $type = (!$receiverId || $receiverId === 'group') ? 'group' : 'individual';
        $note = $request->input('note', 'Inquiring stock availability for this product.');

        $product = Item::findOrFail($productId);

        // Retrieve stock details
        $stockDetails = [];

        // Main store stock
        $mainStocks = MainStock::where('item_id', $productId)->get();
        $mainQty = $mainStocks->sum('remaining_quantity');
        $stockDetails[] = [
            'shop_name' => 'Main Store (Owner)',
            'quantity' => $mainQty,
            'price' => $mainStocks->first() ? $mainStocks->first()->selling_price : null
        ];

        // Sub shops stocks
        $shopStocks = ShopStock::with('shop')->where('item_id', $productId)->get();
        foreach ($shopStocks as $shopStock) {
            $stockDetails[] = [
                'shop_name' => $shopStock->shop ? $shopStock->shop->shop_name : 'Unknown Shop',
                'quantity' => $shopStock->remaining_quantity,
                'price' => $shopStock->selling_price
            ];
        }

        // Format metadata JSON
        $metadata = [
            'item_name' => $product->item_name,
            'brand' => $product->brand,
            'model' => $product->model,
            'specification' => $product->specification,
            'image_url' => $product->image_path ? asset('storage/' . $product->image_path) : null,
            'note' => $note,
            'stocks' => $stockDetails
        ];

        // Construct message description
        $messageText = "Product inquiry: {$product->item_name}";

        $chat = ChatMessage::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $type === 'individual' ? $receiverId : null,
            'message' => $messageText,
            'type' => 'product_inquiry',
            'product_id' => $product->id,
            'metadata' => $metadata,
            'is_read' => false
        ]);

        // Notify recipients
        $senderShopLabel = $currentUser->shop ? $currentUser->shop->shop_name : 'Owner Store';
        $notificationTitle   = 'Product Inquiry';
        $notificationMessage = "{$currentUser->name} ({$senderShopLabel}) is inquiring about \"{$product->item_name}\" — do you have it in stock?";

        if ($type === 'individual') {
            // Direct inquiry: notify only the intended recipient
            \App\Models\Notification::create([
                'user_id'   => $receiverId,
                'title'     => $notificationTitle,
                'message'   => $notificationMessage,
                'is_read'   => false,
                'is_played' => false
            ]);
        } else {
            // Group inquiry: notify every other user in the system
            $allOtherUsers = \App\Models\User::where('id', '!=', $currentUser->id)->get();
            foreach ($allOtherUsers as $recipient) {
                \App\Models\Notification::create([
                    'user_id'   => $recipient->id,
                    'title'     => $notificationTitle,
                    'message'   => $notificationMessage,
                    'is_read'   => false,
                    'is_played' => false
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $chat->load(['sender.shop', 'product'])
        ]);
    }

    /**
     * Send SMS to all or a dedicated user.
     */
    public function sendSMS(Request $request)
    {
        $currentUser = Auth::user();

        // Restrict to Owners and Shop Admins
        if (!$currentUser->isOwner() && !$currentUser->isShopAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized operation.'], 403);
        }

        $request->validate([
            'message' => 'required|string|max:480',
            'recipient_type' => 'required|in:all,individual',
            'receiver_id' => 'required_if:recipient_type,individual|nullable|exists:users,id'
        ]);

        $messageText = $request->input('message');
        $recipientType = $request->input('recipient_type');

        // Fetch SMS Gateway Settings
        $smsEnabled = Setting::get('sms_enabled', '0');
        $smsApiUrl = Setting::get('sms_api_url', '');
        $smsApiKey = Setting::get('sms_api_key', '');
        $smsSenderId = Setting::get('sms_sender_id', '');
        $smsPhoneField = Setting::get('sms_phone_field', 'phone_number');
        $smsMessageField = Setting::get('sms_message_field', 'message');
        $smsExtraParams = Setting::get('sms_extra_params', '');

        // Determine target users
        if ($recipientType === 'all') {
            $recipients = User::whereNotNull('phone')->where('phone', '!=', '')->get();
        } else {
            $recipients = User::where('id', $request->input('receiver_id'))->get();
        }

        if ($recipients->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No recipients found with valid phone numbers.']);
        }

        $successCount = 0;
        $failedCount = 0;
        $loggedCount = 0;

        foreach ($recipients as $recipient) {
            $phone = preg_replace('/[^0-9+]/', '', $recipient->phone);

            if (empty($phone)) {
                continue;
            }

            if ($smsEnabled == '1' && !empty($smsApiUrl)) {
                // Setup payload
                $payload = [
                    $smsPhoneField => $phone,
                    $smsMessageField => $messageText,
                ];

                if (!empty($smsSenderId)) {
                    $payload['sender'] = $smsSenderId;
                    $payload['sender_id'] = $smsSenderId;
                }

                if (!empty($smsApiKey)) {
                    $payload['api_key'] = $smsApiKey;
                    $payload['token'] = $smsApiKey;
                    $payload['key'] = $smsApiKey;
                }

                // Add extra parameters if provided
                if (!empty($smsExtraParams)) {
                    $extra = json_decode($smsExtraParams, true);
                    if (is_array($extra)) {
                        $payload = array_merge($payload, $extra);
                    }
                }

                try {
                    // Execute POST request to SMS gateway
                    $response = Http::timeout(10)->post($smsApiUrl, $payload);

                    if ($response->successful()) {
                        $successCount++;
                        SmsLog::create([
                            'sender_id' => $currentUser->id,
                            'receiver_id' => $recipient->id,
                            'phone_number' => $phone,
                            'message' => $messageText,
                            'status' => 'sent',
                            'response' => $response->body()
                        ]);

                        // System notification
                        \App\Models\Notification::create([
                            'user_id' => $recipient->id,
                            'title' => 'SMS Notification Received',
                            'message' => "SMS: {$messageText}",
                            'is_read' => false,
                            'is_played' => false
                        ]);
                    } else {
                        $failedCount++;
                        SmsLog::create([
                            'sender_id' => $currentUser->id,
                            'receiver_id' => $recipient->id,
                            'phone_number' => $phone,
                            'message' => $messageText,
                            'status' => 'failed',
                            'response' => 'HTTP Status Code: ' . $response->status() . ' - ' . $response->body()
                        ]);
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    SmsLog::create([
                        'sender_id' => $currentUser->id,
                        'receiver_id' => $recipient->id,
                        'phone_number' => $phone,
                        'message' => $messageText,
                        'status' => 'failed',
                        'response' => 'Exception: ' . $e->getMessage()
                    ]);
                    Log::error("SMS sending failed to {$phone}: " . $e->getMessage());
                }
            } else {
                // Sandbox Mode
                $loggedCount++;
                SmsLog::create([
                    'sender_id' => $currentUser->id,
                    'receiver_id' => $recipient->id,
                    'phone_number' => $phone,
                    'message' => $messageText,
                    'status' => 'logged_only',
                    'response' => 'Sandbox Mode: SMS logged in DB. Gateway not active/configured.'
                ]);

                // System notification
                \App\Models\Notification::create([
                    'user_id' => $recipient->id,
                    'title' => 'SMS Notification Received',
                    'message' => "SMS: {$messageText}",
                    'is_read' => false,
                    'is_played' => false
                ]);
            }
        }

        // Prepare return message
        if ($smsEnabled == '1' && !empty($smsApiUrl)) {
            $msg = "SMS broadcast completed: {$successCount} sent successfully, {$failedCount} failed.";
        } else {
            $msg = "Sandbox Mode: {$loggedCount} SMS successfully logged in the database.";
        }

        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }

    /**
     * Poll endpoint for active chat unread status globally.
     */
    public function getUnreadBadge()
    {
        $currentUser = Auth::user();
        if (!$currentUser) {
            return response()->json(['unread' => 0, 'unread_by_sender' => []]);
        }

        $unread = ChatMessage::where('receiver_id', $currentUser->id)
            ->where('is_read', false)
            ->whereIn('type', ['individual', 'product_inquiry'])
            ->count();

        // Get unread counts and oldest unread time grouped by sender
        $unreadBySender = ChatMessage::where('receiver_id', $currentUser->id)
            ->where('is_read', false)
            ->whereIn('type', ['individual', 'product_inquiry'])
            ->select('sender_id', \Illuminate\Support\Facades\DB::raw('count(*) as count'), \Illuminate\Support\Facades\DB::raw('min(created_at) as oldest_time'))
            ->groupBy('sender_id')
            ->get()
            ->keyBy('sender_id')
            ->map(function ($item) {
                return [
                    'count' => $item->count,
                    'oldest_time' => $item->oldest_time ? strtotime($item->oldest_time) : null
                ];
            })
            ->toArray();

        return response()->json([
            'unread' => $unread,
            'unread_by_sender' => $unreadBySender
        ]);
    }
}
