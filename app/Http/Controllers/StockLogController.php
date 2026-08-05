<?php

namespace App\Http\Controllers;

use App\Models\StockLog;
use Illuminate\Http\Request;

class StockLogController extends Controller
{
    public function index(Request $request)
    {
        $query = StockLog::with('item.category', 'performer')->where('is_admin_stock', false);

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        $logs = $query->latest()->get();

        $types = ['STOCK_RECEIVED', 'STOCK_TRANSFER', 'SALE', 'DEFECT', 'ADJUSTMENT'];

        return view('stock-logs.index', compact('logs', 'types'));
    }
}
