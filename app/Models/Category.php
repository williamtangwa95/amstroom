<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['category_name', 'description', 'is_admin_category', 'shop_id'];

    protected $casts = [
        'is_admin_category' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
