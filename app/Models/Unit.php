<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Unit extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'symbol',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
