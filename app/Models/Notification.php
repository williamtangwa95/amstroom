<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'is_read',
        'is_played',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_played' => 'boolean',
    ];

    protected $appends = [
        'destination_url',
    ];

    /**
     * User relation.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get destination URL for the notification if it has one.
     */
    public function getDestinationUrlAttribute(): ?string
    {
        $text = $this->title . ' ' . $this->message;

        // 1. Sales Returns (check first so "Return request for Sale #1" goes to sales-returns)
        if (stripos($text, 'Return') !== false) {
            if (auth()->check()) {
                return route('sales-returns.index');
            }
        }

        // 1b. Daily Diary Follow-up Reminders
        if (stripos($text, 'Diary') !== false) {
            if (auth()->check()) {
                return route('diary.index');
            }
        }

        // 1c. Handover Edit / Return Unlock Requests
        if (stripos($text, 'Handover Edit') !== false || stripos($text, 'Handover Unlock') !== false) {
            if (auth()->check()) {
                if (preg_match('/#SL-(\d+)/', $text, $m)) {
                    return route('sales.show', $m[1]);
                }
                if (stripos($text, 'Expense') !== false) {
                    return route('expenses.index');
                }
                if (preg_match('/[Hh]andover\s*#(\d+)/', $text, $m) && !auth()->user()->isSeller()) {
                    return route('handovers.show', $m[1]);
                }
            }
        }

        // 2. Specific Stock Request (e.g. Request #12)
        if (preg_match('/[Rr]equest\s*#(\d+)/', $text, $matches)) {
            $id = $matches[1];
            if (auth()->check() && (auth()->user()->isOwner() || auth()->user()->isShopAdmin())) {
                return route('stock-requests.show', $id);
            }
        }

        // 3. Inter-Shop Product Transfer (e.g. PT-00001 or "Product Transfer")
        if (stripos($text, 'Product Transfer') !== false || preg_match('/\b(PT-\d+)\b/', $text)) {
            if (auth()->check() && (auth()->user()->isOwner() || auth()->user()->isShopAdmin())) {
                if (preg_match('/\b(PT-\d+)\b/', $text, $ptMatch) && \Illuminate\Support\Facades\Schema::hasTable('shop_product_transfers')) {
                    $ptId = ShopProductTransfer::where('transfer_number', $ptMatch[1])->value('id');
                    if ($ptId) {
                        return route('product-transfers.show', $ptId);
                    }
                }
                if (stripos($text, 'Incoming Product Transfer') !== false && auth()->user()->isShopAdmin()) {
                    return route('product-transfers.index', ['tab' => 'action_required']);
                }
                return route('product-transfers.index');
            }
        }

        // 4. Specific Stock Transfer (e.g. Transfer #12)
        if (preg_match('/[Tt]ransfer\s*#(\d+)/', $text, $matches)) {
            $id = $matches[1];
            if (auth()->check() && (auth()->user()->isOwner() || auth()->user()->isShopAdmin())) {
                return route('stock-transfers.show', $id);
            }
        }

        // 4. Specific Handover Report (e.g. Handover #12)
        if (preg_match('/[Hh]andover\s*#(\d+)/', $text, $matches)) {
            $id = $matches[1];
            if (auth()->check()) {
                return route('handovers.show', $id);
            }
        }

        // 5. Specific Sale (e.g. Sale #12)
        if (preg_match('/[Ss]ale\s*#(\d+)/', $text, $matches)) {
            $id = $matches[1];
            if (auth()->check()) {
                return route('sales.show', $id);
            }
        }

        // 6. Price Update Notifications (check before general Transfer/Main Store keywords
        //    since messages like "Main Store updated transfer price..." belong on Shop Stock)
        if (stripos($text, 'Price Updated') !== false || stripos($text, 'Price Change') !== false) {
            if (auth()->check()) {
                return route('shop-stock.index');
            }
        }

        // 7. Stock Requests (General)
        if (stripos($text, 'Stock Request') !== false || stripos($text, 'Request') !== false && stripos($text, 'Stock') !== false) {
            if (auth()->check() && (auth()->user()->isOwner() || auth()->user()->isShopAdmin())) {
                return route('stock-requests.index');
            }
        }

        // 7. Stock Transfers (General)
        if (stripos($text, 'Stock Transfer') !== false || stripos($text, 'Transfer') !== false) {
            if (auth()->check() && (auth()->user()->isOwner() || auth()->user()->isShopAdmin())) {
                return route('stock-transfers.index');
            }
        }

        // 8. Main Store / Warehouse Stock
        if (stripos($text, 'Main Store') !== false || stripos($text, 'Main Stock') !== false || stripos($text, 'Warehouse') !== false) {
            if (auth()->check() && auth()->user()->isOwner()) {
                return route('main-stock.index');
            }
        }

        // 9. Shop Stock / Price Changes / Approvals / Quantity Edits / Low Stock
        if (
            stripos($text, 'Shop Stock') !== false ||
            stripos($text, 'Price') !== false ||
            stripos($text, 'Stock') !== false ||
            stripos($text, 'Quantity') !== false ||
            stripos($text, 'Batch') !== false ||
            stripos($text, 'Restock') !== false ||
            stripos($text, 'Low Stock') !== false
        ) {
            if (auth()->check()) {
                return route('shop-stock.index');
            }
        }

        // 10. Expenses
        if (stripos($text, 'Expense') !== false) {
            if (auth()->check()) {
                return route('expenses.index');
            }
        }

        // 11. Chat, SMS, Messages
        if (stripos($text, 'Chat') !== false || stripos($text, 'Inquiry') !== false || stripos($text, 'SMS') !== false || stripos($text, 'Message') !== false) {
            if (auth()->check()) {
                return route('chats.index');
            }
        }

        // 12. Handover Reports (General)
        if (stripos($text, 'Handover') !== false) {
            if (auth()->check()) {
                return route('handovers.index');
            }
        }

        // 13. Sales / Invoices
        if (stripos($text, 'Sale') !== false || stripos($text, 'Invoice') !== false) {
            if (auth()->check()) {
                return route('sales.index');
            }
        }

        return null;
    }
}
