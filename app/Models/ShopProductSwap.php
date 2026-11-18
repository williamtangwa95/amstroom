<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopProductSwap extends Model
{
    use HasFactory;

    protected $fillable = [
        'swap_number',
        'source_shop_id',
        'destination_shop_id',
        'source_item_id',
        'source_quantity',
        'destination_item_id',
        'destination_quantity',
        'reason_type',
        'notes',
        'status',
        'initiated_by',
        'destination_approved_by',
        'destination_approved_at',
        'owner_approved_by',
        'owner_approved_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'reverted_by',
        'reverted_at',
        'revert_reason',
    ];

    protected $casts = [
        'source_quantity'         => 'integer',
        'destination_quantity'    => 'integer',
        'destination_approved_at' => 'datetime',
        'owner_approved_at'       => 'datetime',
        'cancelled_at'            => 'datetime',
        'reverted_at'             => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($swap) {
            if (empty($swap->swap_number)) {
                $datePrefix = now()->format('Ym');
                $lastId = static::max('id') + 1;
                $swap->swap_number = sprintf('SWAP-%s-%04d', $datePrefix, $lastId);
            }
        });
    }

    // ─── RELATIONSHIPS ──────────────────────────────────────────────────────────

    public function sourceShop()
    {
        return $this->belongsTo(Shop::class, 'source_shop_id');
    }

    public function destinationShop()
    {
        return $this->belongsTo(Shop::class, 'destination_shop_id');
    }

    public function sourceItem()
    {
        return $this->belongsTo(Item::class, 'source_item_id');
    }

    public function destinationItem()
    {
        return $this->belongsTo(Item::class, 'destination_item_id');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function destinationApprover()
    {
        return $this->belongsTo(User::class, 'destination_approved_by');
    }

    public function ownerApprover()
    {
        return $this->belongsTo(User::class, 'owner_approved_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function reverter()
    {
        return $this->belongsTo(User::class, 'reverted_by');
    }

    // ─── STATE HELPERS ──────────────────────────────────────────────────────────

    public function isPendingDestination(): bool
    {
        return $this->status === 'pending_destination';
    }

    public function isPendingOwner(): bool
    {
        return $this->status === 'pending_owner';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isReverted(): bool
    {
        return $this->status === 'reverted';
    }

    // ─── PERMISSION CHECKS ──────────────────────────────────────────────────────

    public function canApproveDestination(?User $user): bool
    {
        if (!$user) return false;
        if (!$this->isPendingDestination()) return false;

        return $user->isOwner() || ($user->isShopAdmin() && (int) $user->shop_id === (int) $this->destination_shop_id);
    }

    public function canConfirmOwner(?User $user): bool
    {
        if (!$user) return false;
        if (!$this->isPendingOwner()) return false;

        return $user->isOwner();
    }

    public function canCancel(?User $user): bool
    {
        if (!$user) return false;
        if (!in_array($this->status, ['pending_destination', 'pending_owner'], true)) return false;

        if ($user->isOwner()) return true;

        if ($user->isShopAdmin()) {
            return (int) $user->shop_id === (int) $this->source_shop_id || (int) $user->shop_id === (int) $this->destination_shop_id;
        }

        return false;
    }

    public function canRevert(?User $user): bool
    {
        if (!$user) return false;
        if (!$this->isConfirmed()) return false;

        return $user->isOwner();
    }

    public function getReasonLabelAttribute(): string
    {
        return match ($this->reason_type) {
            'customer_preference'    => 'Customer Preference',
            'defective_replacement'  => 'Defective / Warranty Replacement',
            'spec_mismatch'          => 'Specification Mismatch',
            'stock_balancing'        => 'Branch Stock Balancing',
            default                  => ucfirst(str_replace('_', ' ', $this->reason_type)),
        };
    }
}
