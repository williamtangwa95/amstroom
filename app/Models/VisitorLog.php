<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'url',
        'method',
        'user_id',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'city',
        'country',
        'session_id',
    ];

    /**
     * Relationship to the user who made the request (if logged in).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
