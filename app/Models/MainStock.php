<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id', 'buying_price', 'selling_price',
        'stocked_quantity', 'remaining_quantity', 'date_received',
    ];

    protected $casts = [
        'date_received' => 'date',
        'buying_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function isLowStock(): bool
    {
        return $this->remaining_quantity <= 5;
    }
}
