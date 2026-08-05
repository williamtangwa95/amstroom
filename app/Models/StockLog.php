<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id', 'from_location', 'to_location', 'quantity',
        'transaction_type', 'performed_by', 'date', 'notes', 'is_admin_stock',
    ];

    protected $casts = [
        'date' => 'date',
        'is_admin_stock' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
