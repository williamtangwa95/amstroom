<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_item_id',
        'component_item_id',
        'quantity',
    ];

    public function parentItem()
    {
        return $this->belongsTo(Item::class, 'parent_item_id');
    }

    public function childItem()
    {
        return $this->belongsTo(Item::class, 'component_item_id');
    }
}
