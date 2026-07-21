<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequestItem extends Model
{
    use HasFactory;

    protected $fillable = ['request_id', 'item_id', 'quantity'];

    public function request()
    {
        return $this->belongsTo(StockRequest::class, 'request_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
