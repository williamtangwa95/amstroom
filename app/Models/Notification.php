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

        // 2. Specific Stock Request (e.g. Request #12)
        if (preg_match('/[Rr]equest\s*#(\d+)/', $text, $matches)) {
            $id = $matches[1];
            if (auth()->check() && (auth()->user()->isOwner() || auth()->user()->isShopAdmin())) {
                return route('stock-requests.show', $id);
            }
        }

        // 3. Specific Stock Transfer (e.g. Transfer #12)
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

        // 6. Stock Requests (General)
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
