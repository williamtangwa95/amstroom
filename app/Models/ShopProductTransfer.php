<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopProductTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'from_shop_id',
        'to_shop_id',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'reverted_by',
        'reverted_at',
        'revert_reason',
        'notes',
    ];

    protected $casts = [
        'approved_at'  => 'datetime',
        'rejected_at'  => 'datetime',
        'cancelled_at' => 'datetime',
        'reverted_at'  => 'datetime',
    ];

    public function fromShop()
    {
        return $this->belongsTo(Shop::class, 'from_shop_id');
    }

    public function toShop()
    {
        return $this->belongsTo(Shop::class, 'to_shop_id');
    }

    public function items()
    {
        return $this->hasMany(ShopProductTransferItem::class, 'shop_product_transfer_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function reverter()
    {
        return $this->belongsTo(User::class, 'reverted_by');
    }

    public static function generateNumber(): string
    {
        $last = self::orderByDesc('id')->value('transfer_number');
        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }
        $candidate = 'PT-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
        while (self::where('transfer_number', $candidate)->exists()) {
            $next++;
            $candidate = 'PT-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
        }
        return $candidate;
    }

    public function getTotalQuantityAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->items->sum(function ($ti) {
            $unitPrice = (float) ($ti->selling_price > 0 ? $ti->selling_price : $ti->buying_price);
            return $ti->quantity * $unitPrice;
        });
    }

    public function getTotalCostAmountAttribute(): float
    {
        return (float) $this->items->sum(fn ($ti) => $ti->quantity * (float) $ti->buying_price);
    }

    public function canConfirm(User $user): bool
    {
        return $this->status === 'pending'
            && $user->isShopAdmin()
            && (int) $user->shop_id === (int) $this->from_shop_id;
    }

    public function canReject(User $user): bool
    {
        return $this->canConfirm($user);
    }

    public function canCancel(User $user): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }
        return $user->isShopAdmin() && (int) $user->shop_id === (int) $this->to_shop_id;
    }

    public function canRevert(User $user): bool
    {
        return false;
    }
}
