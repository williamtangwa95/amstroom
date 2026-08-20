<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use App\Traits\LogsActivity;

class User extends Authenticatable
{
    use HasFactory, Notifiable, LogsActivity;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'shop_id', 'avatar_path',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'seller_id');
    }

    public function stockRequests()
    {
        return $this->hasMany(StockRequest::class, 'requested_by');
    }

    public function defects()
    {
        return $this->hasMany(Defect::class, 'reported_by');
    }

    // Role helpers
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isShopAdmin(): bool
    {
        return $this->role === 'shop_admin';
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        return $this->role === $roles;
    }
}
