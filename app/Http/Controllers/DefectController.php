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
        return view('defects.index');
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $query = Defect::query();

        if (!$user->isOwner()) {
            $query->where('defects.shop_id', $user->shop_id);
        }

        $recordsTotal = (clone $query)->count();

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->orWhere('defects.reason', 'like', "%{$searchValue}%")
                  ->orWhere('defects.status', 'like', "%{$searchValue}%")
                  ->orWhereHas('item', function ($sq) use ($searchValue) {
                      $sq->where('item_name', 'like', "%{$searchValue}%")
                        ->orWhereHas('category', function ($cq) use ($searchValue) {
                            $cq->where('category_name', 'like', "%{$searchValue}%");
                        });
                  })
                  ->orWhereHas('shop', function ($sq) use ($searchValue) {
                      $sq->where('shop_name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('reporter', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [10, 25, 50, 100];
        $requestedLength = (int) $request->input('length', 10);
        $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

        $defects = $query->with('shop', 'item.category', 'reporter')
            ->orderBy('defects.date', 'desc')
            ->orderBy('defects.id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($defects as $index => $def) {
            $iteration = $start + $index + 1;
            $dateStr = $def->date ? $def->date->format('M d, Y') : 'N/A';
            $location = e($def->shop ? $def->shop->shop_name : 'Main Warehouse');
            $productName = e($def->item?->item_name ?? 'Item');
            $categoryName = e($def->item?->category?->category_name ?? 'General');
            $quantityHtml = '<strong style="color:#e94560;">' . $def->quantity . '</strong>';
            $reason = e(\Illuminate\Support\Str::limit($def->reason, 40));
            $reporterName = e($def->reporter?->name ?? 'System');

            $statusClass = $def->status === 'resolved' ? 'approved' : ($def->status === 'reviewed' ? 'pending' : 'rejected');
            $statusBadge = '<span class="status-badge badge-' . $statusClass . '">' . e(ucfirst($def->status)) . '</span>';

            $actions = '';
            if (auth()->user()->isOwner() && $def->status !== 'resolved') {
                $actions .= '<form method="POST" action="' . route('defects.update-status', $def) . '" class="d-inline">' . csrf_field() . method_field('PATCH') . '<input type="hidden" name="status" value="resolved"><button type="submit" class="btn btn-xs btn-outline-custom text-success" title="Mark Resolved"><i class="bi bi-check-lg"></i> Resolve</button></form>';
            } else {
                $actions = '<span style="font-size:.75rem;color:var(--text-secondary);">—</span>';
            }

            $data[] = [
                'iteration' => $iteration,
                'date' => $dateStr,
                'location' => $location,
                'product' => $productName,
                'category' => $categoryName,
                'quantity' => $quantityHtml,
                'reason' => $reason,
                'reporter' => $reporterName,
                'status' => $statusBadge,
                'actions' => $actions,
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $items = Item::with('category')
            ->when($user->isOwner(), fn($q) => $q->where('is_admin_item', false))
            ->when(!$user->isOwner(), fn($q) => $q->where(function ($sq) use ($user) {
                $sq->where('is_admin_item', false)
                  ->orWhere(function ($ssq) use ($user) {
                      $ssq->where('is_admin_item', true)
                         ->where('shop_id', $user->shop_id);
                  });
            }))
            ->orderBy('item_name')
            ->get();
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

        if ($isMainStore) {
            $available = (int) MainStock::where('item_id', $request->item_id)->sum('remaining_quantity');
            if ($request->quantity > $available) {
                return back()->withErrors(['quantity' => "Defect quantity exceeds available Main Warehouse stock (Available: {$available})."])->withInput();
            }
        } else {
            $shopStock = ShopStock::where('shop_id', $shopId)
                ->where('item_id', $request->item_id)
                ->first();
            $available = $shopStock ? (int) $shopStock->remaining_quantity : 0;
            if ($request->quantity > $available) {
                return back()->withErrors(['quantity' => "Defect quantity exceeds available shop stock (Available: {$available})."])->withInput();
            }
        }

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
