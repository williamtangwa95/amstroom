<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id', 'requested_by', 'approved_by', 'status', 'reason', 'return_date',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class)->withTrashed();
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function isAdminStock(): bool
    {
        if ($this->sale) {
            $saleItems = $this->sale->items;
            if ($saleItems && $saleItems->isNotEmpty()) {
                foreach ($this->items as $returnItem) {
                    $matchingSaleItem = $saleItems->firstWhere('item_id', $returnItem->item_id);
                    if ($matchingSaleItem && !$matchingSaleItem->is_admin_stock) {
                        return false;
                    }
                }
            }
            return (bool) ($this->sale->is_admin_stock ?? false);
        }
        return false;
    }
}
