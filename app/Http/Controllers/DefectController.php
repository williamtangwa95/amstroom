<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\ShopStock;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DefectController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Defect::with('shop', 'item.category', 'reporter');

        if (!$user->isOwner()) {
            $query->where('shop_id', $user->shop_id);
        }

        $defects = $query->latest()->paginate(15);

        return view('defects.index', compact('defects'));
    }

    public function create()
    {
        $user = Auth::user();
        $items = Item::with('category')->orderBy('item_name')->get();
        $isMainStore = $user->isOwner() && request()->boolean('main_store');

        return view('defects.create', compact('items', 'isMainStore'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_id'    => 'required|exists:items,id',
            'quantity'   => 'required|integer|min:1',
            'reason'     => 'required|string|max:500',
            'is_main_store' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $isMainStore = $request->boolean('is_main_store') && $user->isOwner();
        $shopId = $isMainStore ? null : $user->shop_id;

        DB::transaction(function () use ($request, $user, $shopId, $isMainStore) {
            if ($isMainStore) {
                // Deduct from main stock
                $remaining = $request->quantity;
                $batches = MainStock::where('item_id', $request->item_id)
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('date_received')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    $deduct = min($batch->remaining_quantity, $remaining);
                    $batch->decrement('remaining_quantity', $deduct);
                    $remaining -= $deduct;
                }

                StockLog::create([
                    'item_id'          => $request->item_id,
                    'from_location'    => 'Main Warehouse',
                    'to_location'      => 'Defective',
                    'quantity'         => $request->quantity,
                    'transaction_type' => 'DEFECT',
                    'performed_by'     => $user->id,
                    'date'             => now()->toDateString(),
                    'notes'            => $request->reason,
                ]);
            } else {
                $shopStock = ShopStock::where('shop_id', $shopId)
                    ->where('item_id', $request->item_id)
                    ->firstOrFail();

                if ($request->quantity > $shopStock->remaining_quantity) {
                    throw new \Exception("Defect quantity exceeds available stock.");
                }

                $shopStock->decrement('remaining_quantity', $request->quantity);

                StockLog::create([
                    'item_id'          => $request->item_id,
                    'from_location'    => $shopStock->shop->shop_name,
                    'to_location'      => 'Defective',
                    'quantity'         => $request->quantity,
                    'transaction_type' => 'DEFECT',
                    'performed_by'     => $user->id,
                    'date'             => now()->toDateString(),
                    'notes'            => $request->reason,
                ]);
            }

            Defect::create([
                'shop_id'     => $shopId,
                'item_id'     => $request->item_id,
                'quantity'    => $request->quantity,
                'reason'      => $request->reason,
                'status'      => 'reported',
                'reported_by' => $user->id,
                'date'        => now()->toDateString(),
            ]);
        });

        return redirect()->route('defects.index')
            ->with('success', 'Defect reported and stock adjusted successfully.');
    }

    public function updateStatus(Request $request, Defect $defect)
    {
        $request->validate(['status' => 'required|in:reported,reviewed,resolved']);
        $defect->update(['status' => $request->status]);
        return back()->with('success', 'Defect status updated.');
    }
}
