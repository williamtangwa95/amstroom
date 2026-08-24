<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class HandoverReport extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'handover_no', 'shop_id', 'shop_admin_id', 'start_date', 'end_date',
        'total_owner_sales', 'total_admin_sales', 'admin_stock_cost', 'total_expenses', 'net_profit',
        'expected_amount', 'commission_amount', 'actual_amount', 'difference', 'difference_status',
        'difference_reason', 'notes', 'attachment_path', 'status',
        'created_by', 'submitted_at', 'approved_by', 'approved_at',
        'received_by', 'received_at', 'received_remarks', 'amount_received'
    ];

    protected $casts = [
        'start_date'        => 'date',
        'end_date'          => 'date',
        'total_owner_sales' => 'decimal:2',
        'total_admin_sales' => 'decimal:2',
        'admin_stock_cost'  => 'decimal:2',
        'total_expenses'    => 'decimal:2',
        'net_profit'        => 'decimal:2',
        'expected_amount'   => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'actual_amount'     => 'decimal:2',
        'difference'        => 'decimal:2',
        'submitted_at'      => 'datetime',
        'approved_at'       => 'datetime',
        'received_at'       => 'datetime',
        'amount_received'   => 'decimal:2',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function shopAdmin()
    {
        return $this->belongsTo(User::class, 'shop_admin_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
