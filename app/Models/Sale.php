<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id', 'seller_id', 'customer_name', 'total_amount', 'payment_method', 'sale_date',
        'status', 'customer_id', 'customer_po_box', 'deliver_to',
        'delivery_date', 'delivery_time', 'validity_date', 'terms_of_payment', 'is_admin_stock',
    ];

    protected $casts = [
        'sale_date'     => 'date',
        'delivery_date' => 'date',
        'validity_date' => 'date',
        'total_amount'  => 'decimal:2',
        'is_admin_stock'=> 'boolean',
    ];

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeDraftProforma($query)
    {
        return $query->where('status', 'draft_proforma');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getReportRevenueAttribute(): float
    {
        $isOwner = auth()->check() && auth()->user()->isOwner();
        $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT';

        return (float) $this->items->sum(function ($item) use ($isOwner, $isIndependent) {
            if ($isOwner && $item->is_admin_stock) {
                return 0.0;
            }
            if ($isOwner && $isIndependent && $this->shop_id !== null) {
                return (float) ($item->owner_realized_sp ?? $item->selling_price) * $item->quantity;
            }
            return (float) ($item->shop_realized_sp ?? $item->selling_price) * $item->quantity;
        });
    }

    public function getReportCostAttribute(): float
    {
        $isOwner = auth()->check() && auth()->user()->isOwner();

        return (float) $this->items->sum(function ($item) use ($isOwner) {
            if ($isOwner && $item->is_admin_stock) {
                return 0.0;
            }
            if ($isOwner) {
                return (float) ($item->owner_cost_price ?? 0) * $item->quantity;
            }
            return (float) ($item->shop_cost_price ?? $item->owner_realized_sp ?? 0) * $item->quantity;
        });
    }

    public function getReportProfitAttribute(): float
    {
        return $this->report_revenue - $this->report_cost;
    }
}
