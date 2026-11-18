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
        'name', 'email', 'phone', 'password', 'role', 'shop_id', 'avatar_path', 'allow_stock_addition', 'allow_pos_admin', 'status',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'allow_stock_addition' => 'boolean',
            'allow_pos_admin' => 'boolean',
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
    public function isSystemAdmin(): bool
    {
        return $this->role === 'system_admin';
    }

    /**
     * Check if user is owner or system admin (who has identical owner privileges and duties).
     */
    public function isOwner(): bool
    {
        return in_array($this->role, ['owner', 'system_admin'], true);
    }

    public function isShopAdmin(): bool
    {
        return $this->role === 'shop_admin';
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function canAccessAdminPos(): bool
    {
        return $this->isOwner() || $this->isShopAdmin() || ($this->isSeller() && (bool) $this->allow_pos_admin);
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : func_get_args();
        if (in_array($this->role, $roles, true)) {
            return true;
        }
        // System admin has full owner capabilities and satisfies any owner role checks
        if ($this->isOwner() && in_array('owner', $roles, true)) {
            return true;
        }
        return false;
    }

    /**
     * Scope query to users with owner capabilities (owner and system_admin).
     */
    public function scopeOwners($query)
    {
        return $query->whereIn('role', ['owner', 'system_admin']);
    }

    /**
     * Determine if there are any transactions, stocks, or records dependent on this user.
     */
    public function hasDependencies(): bool
    {
        if ($this->sales()->exists()) return true;
        if ($this->stockRequests()->exists()) return true;
        if ($this->defects()->exists()) return true;

        if (\DB::table('stock_transfers')->where('approved_by', $this->id)->exists()) return true;
        if (\DB::table('expenses')->where('recorded_by', $this->id)->orWhere('approved_by', $this->id)->exists()) return true;
        if (\DB::table('handover_reports')->where('shop_admin_id', $this->id)->orWhere('created_by', $this->id)->orWhere('approved_by', $this->id)->orWhere('received_by', $this->id)->exists()) return true;
        if (\DB::table('stock_logs')->where('performed_by', $this->id)->exists()) return true;

        return false;
    }
}
