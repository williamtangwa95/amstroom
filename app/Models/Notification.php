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
        // 1. Stock Request
        if (preg_match('/[Rr]equest\s*#(\d+)/', $this->message, $matches)) {
            $id = $matches[1];
            if (auth()->check() && (auth()->user()->isOwner() || auth()->user()->isShopAdmin())) {
                return route('stock-requests.show', $id);
            }
        }
        
        // 2. Stock Transfer
        if (preg_match('/[Tt]ransfer\s*#(\d+)/', $this->message, $matches)) {
            $id = $matches[1];
            if (auth()->check() && (auth()->user()->isOwner() || auth()->user()->isShopAdmin())) {
                return route('stock-transfers.show', $id);
            }
        }

        // 3. Shop Stock
        if (stripos($this->title, 'Shop Stock') !== false || stripos($this->message, 'shop stock') !== false) {
            if (auth()->check()) {
                return route('shop-stock.index');
            }
        }

        // 4. Expenses
        if (stripos($this->title, 'Expense') !== false || stripos($this->message, 'expense') !== false) {
            if (auth()->check()) {
                return route('expenses.index');
            }
        }

        // 5. Sales Return
        if (stripos($this->title, 'Return') !== false || stripos($this->message, 'return') !== false) {
            if (auth()->check()) {
                return route('sales-returns.index');
            }
        }

        return null;
    }
}
