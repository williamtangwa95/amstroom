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

    public const LOCKING_STATUSES = ['submitted', 'under_review', 'approved', 'completed'];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function editRequests()
    {
        return $this->hasMany(HandoverEditRequest::class);
    }

    public function locksTransactions(): bool
    {
        return in_array($this->status, self::LOCKING_STATUSES, true);
    }

    /**
     * Recalculate financial totals after an Owner-approved post-handover edit/delete/return.
     * Preserves actual_amount and amount_received so any cash variance is transparently visible.
     */
    public function recalculateTotals(?string $auditReason = null): array
    {
        $before = [
            'total_owner_sales' => (float) $this->total_owner_sales,
            'total_expenses'    => (float) $this->total_expenses,
            'expected_amount'   => (float) $this->expected_amount,
            'difference'        => (float) $this->difference,
        ];

        $sales = Sale::with('items')
            ->where('handover_report_id', $this->id)
            ->completed()
            ->get();

        $expenses = Expense::where('handover_report_id', $this->id)
            ->whereIn('status', ['approved', 'review_requested', 'editable'])
            ->get();

        $isIndependent = Setting::get('store_pricing_mode', 'INDEPENDENT') === 'INDEPENDENT';
        $totalOwnerSales = 0.0;
        $adminCostOfGoods = 0.0;
        $adminViewSales = 0.0;

        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if ($item->is_admin_stock) {
                    continue;
                }
                if ($isIndependent && $sale->shop_id !== null) {
                    $itemRevenue = (float) ($item->owner_realized_sp ?? $item->selling_price) * $item->quantity;
                } else {
                    $itemRevenue = (float) ($item->shop_realized_sp ?? $item->selling_price) * $item->quantity;
                }
                $totalOwnerSales += $itemRevenue;
                $itemCost = ($item->parent_id !== null) ? 0.0 : (float) ($item->shop_cost_price ?? $item->owner_realized_sp ?? $item->selling_price ?? 0) * $item->quantity;
                $adminCostOfGoods += $itemCost;
                $adminViewSales += (float) ($item->shop_realized_sp ?? $item->selling_price) * $item->quantity;
            }
        }

        $totalExpenses = (float) $expenses->sum('amount');
        $expectedAmount = $totalOwnerSales - $totalExpenses;
        $netProfit = $adminViewSales - $adminCostOfGoods;
        $actualAmount = (float) $this->actual_amount;
        $difference = $actualAmount - $expectedAmount;

        $differenceStatus = 'exact';
        if ($difference < -0.01) {
            $differenceStatus = 'shortage';
        } elseif ($difference > 0.01) {
            $differenceStatus = 'excess';
        }

        $this->update([
            'total_owner_sales' => $totalOwnerSales,
            'total_expenses'    => $totalExpenses,
            'net_profit'        => $netProfit,
            'expected_amount'   => $expectedAmount,
            'difference'        => $difference,
            'difference_status' => $differenceStatus,
        ]);

        $after = [
            'total_owner_sales' => $totalOwnerSales,
            'total_expenses'    => $totalExpenses,
            'expected_amount'   => $expectedAmount,
            'difference'        => $difference,
        ];

        $reasonText = $auditReason ? " ({$auditReason})" : '';
        ActivityLog::log(
            'RECALCULATED',
            "Recalculated Handover {$this->handover_no}{$reasonText}: Expected TZS " . number_format($before['expected_amount'], 0) . ' -> TZS ' . number_format($after['expected_amount'], 0),
            $this
        );

        // Notify Owners about the financial adjustment on the submitted handover
        if (abs($before['expected_amount'] - $after['expected_amount']) >= 0.01) {
            $owners = User::whereIn('role', ['owner', 'system_admin'])->get();
            foreach ($owners as $owner) {
                Notification::create([
                    'user_id' => $owner->id,
                    'title'   => "Handover #{$this->id} Totals Updated",
                    'message' => "Handover {$this->handover_no} totals were recalculated{$reasonText}. Expected Cash: TZS " . number_format($before['expected_amount'], 0) . ' -> TZS ' . number_format($after['expected_amount'], 0) . '.',
                ]);
            }
        }

        return ['before' => $before, 'after' => $after];
    }
}
