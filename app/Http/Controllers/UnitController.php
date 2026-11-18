<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $totalUnits = Unit::count();
        $defaultUnit = Unit::where('is_default', true)->first();

        return view('units.index', compact('totalUnits', 'defaultUnit'));
    }

    public function data(Request $request)
    {
        $query = Unit::withCount('items');

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('symbol', 'like', "%{$searchValue}%")
                  ->orWhere('description', 'like', "%{$searchValue}%");
            });
        }

        $recordsTotal = Unit::count();
        $recordsFiltered = (clone $query)->count();

        // Order
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = ['id', 'name', 'symbol', 'description', 'items_count'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'name';
        $query->orderBy($orderColumn, $orderDir);

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 15);
        $units = $query->skip($start)->take($length)->get();

        $data = $units->map(function ($unit, $index) use ($start) {
            $defaultBadge = $unit->is_default
                ? '<span class="badge ms-2" style="background:rgba(63,185,80,0.15);color:#3fb950;font-size:0.68rem;">Default</span>'
                : '';

            $symbolBadge = '<span class="badge px-2 py-1" style="background:rgba(88,166,255,0.15);color:#58a6ff;font-size:0.78rem;font-weight:600;">' . e($unit->symbol) . '</span>';

            $itemsBadge = '<span class="badge" style="background:rgba(255,255,255,0.06);color:var(--text-secondary);font-size:0.75rem;">'
                . number_format($unit->items_count) . ' product(s)</span>';

            $actions = '<div class="d-flex align-items-center gap-1 justify-content-end">';
            $actions .= '<button type="button" class="btn btn-sm btn-outline-warning btn-edit-unit"
                            style="font-size:0.72rem;padding:3px 8px;"
                            data-id="' . $unit->id . '"
                            data-name="' . e($unit->name) . '"
                            data-symbol="' . e($unit->symbol) . '"
                            data-description="' . e($unit->description ?? '') . '"
                            data-is-default="' . ($unit->is_default ? '1' : '0') . '"
                            title="Edit Unit">
                            <i class="bi bi-pencil-fill"></i>
                         </button>';

            $actions .= '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-unit"
                            style="font-size:0.72rem;padding:3px 8px;"
                            data-id="' . $unit->id . '"
                            data-name="' . e($unit->name) . '"
                            data-items-count="' . $unit->items_count . '"
                            title="Delete Unit">
                            <i class="bi bi-trash-fill"></i>
                         </button>';
            $actions .= '</div>';

            return [
                'id'          => $start + $index + 1,
                'name'        => '<span class="fw-600">' . e($unit->name) . '</span>' . $defaultBadge,
                'symbol'      => $symbolBadge,
                'description' => '<span class="text-muted small">' . e($unit->description ?? '—') . '</span>',
                'items_count' => $itemsBadge,
                'actions'     => $actions,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:units,name',
            'symbol'      => 'required|string|max:20',
            'description' => 'nullable|string|max:255',
            'is_default'  => 'nullable|boolean',
        ]);

        $validated['is_default'] = $request->boolean('is_default');

        if ($validated['is_default']) {
            Unit::where('is_default', true)->update(['is_default' => false]);
        }

        Unit::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Unit registered successfully.']);
        }

        return redirect()->route('units.index')->with('success', 'Unit registered successfully.');
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:units,name,' . $unit->id,
            'symbol'      => 'required|string|max:20',
            'description' => 'nullable|string|max:255',
            'is_default'  => 'nullable|boolean',
        ]);

        $validated['is_default'] = $request->boolean('is_default');

        if ($validated['is_default']) {
            Unit::where('id', '!=', $unit->id)->where('is_default', true)->update(['is_default' => false]);
        }

        $unit->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Unit updated successfully.']);
        }

        return redirect()->route('units.index')->with('success', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit)
    {
        if ($unit->items()->exists()) {
            $count = $unit->items()->count();
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete unit '{$unit->name}' because {$count} product(s) are currently assigned to it."
                ], 422);
            }
            return back()->with('error', "Cannot delete unit '{$unit->name}' because {$count} product(s) are currently assigned to it.");
        }

        $unit->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Unit deleted successfully.']);
        }

        return redirect()->route('units.index')->with('success', 'Unit deleted successfully.');
    }
}
