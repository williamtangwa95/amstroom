<?php

namespace App\Http\Controllers;

use App\Models\Diary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiaryController extends Controller
{
    /**
     * Determine if a user can manage (edit/delete) a diary entry.
     */
    private function canManageDiary($user, Diary $diary): bool
    {
        // The creator can always manage their entry
        if ($diary->user_id === $user->id) {
            return true;
        }

        // For shop entries:
        if ($diary->type === 'shop') {
            // Shop Admin can manage entries for their own shop
            if ($user->isShopAdmin() && $user->shop_id && $diary->shop_id === $user->shop_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a user can view a diary entry.
     */
    private function canViewDiary($user, Diary $diary): bool
    {
        if ($diary->type === 'personal') {
            return $diary->user_id === $user->id;
        }

        if ($diary->type === 'shop') {
            return (bool) ($user->shop_id && $diary->shop_id === $user->shop_id);
        }

        return false;
    }

    /**
     * Base query scoped by user role, shop access, and request filters.
     */
    private function scopedDiaryQuery($user, Request $request = null)
    {
        $query = Diary::with(['user', 'shop']);

        if ($user->isOwner()) {
            // Owner is strictly restricted to their own personal diary
            $query->where('type', 'personal')->where('user_id', $user->id);
        } else {
            // Shop Admin or Seller
            $type       = $request ? $request->input('type') : null;
            $userShopId = $user->shop_id;

            if ($type === 'personal') {
                $query->where('type', 'personal')->where('user_id', $user->id);
            } elseif ($type === 'shop') {
                if ($userShopId) {
                    $query->where('type', 'shop')->where('shop_id', $userShopId);
                } else {
                    $query->whereRaw('0 = 1');
                }
            } else {
                // All: User's personal entries OR entries for their assigned shop
                $query->where(function ($q) use ($user, $userShopId) {
                    $q->where(function ($sq) use ($user) {
                        $sq->where('type', 'personal')->where('user_id', $user->id);
                    });
                    if ($userShopId) {
                        $q->orWhere(function ($sq) use ($userShopId) {
                            $sq->where('type', 'shop')->where('shop_id', $userShopId);
                        });
                    }
                });
            }
        }

        return $query;
    }

    // ── Index ───────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $user = Auth::user();

        $dateFrom = $request->has('date_from') ? $request->date_from : now()->startOfMonth()->toDateString();
        $dateTo   = $request->has('date_to')   ? $request->date_to   : now()->endOfMonth()->toDateString();

        $query = $this->scopedDiaryQuery($user, $request);

        if (!empty($dateFrom)) {
            $query->where(function ($q) use ($dateFrom) {
                $q->whereDate('event_date', '>=', $dateFrom)
                  ->orWhereNull('event_date');
            });
        }
        if (!empty($dateTo)) {
            $query->where(function ($q) use ($dateTo) {
                $q->whereDate('event_date', '<=', $dateTo)
                  ->orWhereNull('event_date');
            });
        }

        $totalEntries   = (int) (clone $query)->count();
        $personalCount  = (int) (clone $query)->where('type', 'personal')->count();
        $shopCount      = (int) (clone $query)->where('type', 'shop')->count();
        $followUpCount  = (int) (clone $query)->where('needs_follow_up', true)->count();
        $noFollowUp     = (int) (clone $query)->where('needs_follow_up', false)->count();
        $totalCost      = (float) (clone $query)->sum('cost');

        return view('diary.index', compact(
            'totalEntries',
            'personalCount',
            'shopCount',
            'followUpCount',
            'noFollowUp',
            'totalCost',
            'dateFrom',
            'dateTo',
            'user'
        ));
    }

    // ── DataTables JSON ─────────────────────────────────────────────────────────
    public function data(Request $request)
    {
        $user  = Auth::user();
        $query = $this->scopedDiaryQuery($user, $request);

        $dateFrom = $request->has('date_from') ? $request->date_from : now()->startOfMonth()->toDateString();
        $dateTo   = $request->has('date_to')   ? $request->date_to   : now()->endOfMonth()->toDateString();

        if (!empty($dateFrom)) {
            $query->where(function ($q) use ($dateFrom) {
                $q->whereDate('event_date', '>=', $dateFrom)
                  ->orWhereNull('event_date');
            });
        }
        if (!empty($dateTo)) {
            $query->where(function ($q) use ($dateTo) {
                $q->whereDate('event_date', '<=', $dateTo)
                  ->orWhereNull('event_date');
            });
        }

        if ($request->filled('needs_follow_up') && $request->needs_follow_up !== '') {
            $query->where('needs_follow_up', (bool) $request->needs_follow_up);
        }

        $recordsTotal    = (clone $query)->count();
        $personalCount   = (int) (clone $query)->where('type', 'personal')->count();
        $shopCount       = (int) (clone $query)->where('type', 'shop')->count();
        $followUpCount   = (int) (clone $query)->where('needs_follow_up', true)->count();
        $noFollowUp      = (int) (clone $query)->where('needs_follow_up', false)->count();
        $totalCost       = (float) (clone $query)->sum('cost');

        // Search
        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->where('event_name', 'like', "%{$searchValue}%")
                  ->orWhere('event_description', 'like', "%{$searchValue}%")
                  ->orWhereHas('user', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('shop', function ($sq) use ($searchValue) {
                      $sq->where('shop_name', 'like', "%{$searchValue}%");
                  });
            });
        }

        $recordsFiltered = (clone $query)->count();

        // Ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir         = $request->input('order.0.dir', 'desc');
        $columns          = ['id', 'type', 'event_name', 'event_date', 'due_date', 'cost', 'needs_follow_up', 'created_at'];
        $orderColumn      = $columns[$orderColumnIndex] ?? 'created_at';
        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start   = (int) $request->input('start', 0);
        $length  = (int) $request->input('length', 15);
        $diaries = $query->skip($start)->take($length)->get();

        $data = $diaries->map(function ($diary) use ($user) {
            $canManage = $this->canManageDiary($user, $diary);

            // Type badge
            if ($diary->type === 'shop') {
                $shopName = $diary->shop ? e($diary->shop->shop_name) : 'Shop';
                $typeBadge = '<span class="badge" style="background:rgba(56,139,253,0.15);color:#58a6ff;border:1px solid rgba(56,139,253,0.3);font-size:0.72rem;">
                                <i class="bi bi-shop me-1"></i> ' . $shopName . '
                              </span>';
            } else {
                $typeBadge = '<span class="badge" style="background:rgba(163,113,247,0.15);color:#a371f7;border:1px solid rgba(163,113,247,0.3);font-size:0.72rem;">
                                <i class="bi bi-person-fill me-1"></i> Personal
                              </span>';
            }

            // Follow-up status badge
            if ($diary->needs_follow_up) {
                if ($diary->due_date && $diary->due_date->isPast()) {
                    $statusBadge = '<span class="badge" style="background:rgba(233,69,96,0.15);color:#e94560;border:1px solid rgba(233,69,96,0.3);font-size:0.72rem;">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Overdue Follow-up
                                   </span>';
                } else {
                    $statusBadge = '<span class="badge" style="background:rgba(227,179,65,0.15);color:#e3b341;border:1px solid rgba(227,179,65,0.3);font-size:0.72rem;">
                                        <i class="bi bi-bell-fill me-1"></i>Needs Follow-up
                                   </span>';
                }
            } else {
                $statusBadge = '<span class="badge" style="background:rgba(63,185,80,0.15);color:#3fb950;border:1px solid rgba(63,185,80,0.3);font-size:0.72rem;">
                                    <i class="bi bi-check-circle-fill me-1"></i>No Follow-up
                               </span>';
            }

            // Event date
            $eventDate = $diary->event_date
                ? '<span>' . $diary->event_date->format('d M Y') . '</span>'
                : '<span class="text-muted" style="font-size:0.78rem;">—</span>';

            // Due date
            if ($diary->due_date) {
                $dueDateColor = ($diary->due_date->isPast() && $diary->needs_follow_up) ? '#e94560' : 'var(--text-secondary)';
                $dueDate = '<span style="color:' . $dueDateColor . ';">' . $diary->due_date->format('d M Y') . '</span>';
            } else {
                $dueDate = '<span class="text-muted" style="font-size:0.78rem;">—</span>';
            }

            // Cost
            $cost = $diary->cost !== null
                ? '<span>TZS ' . number_format((float) $diary->cost, 0) . '</span>'
                : '<span class="text-muted" style="font-size:0.78rem;">—</span>';

            // Description
            $desc = e($diary->event_description);
            $descDisplay = strlen($diary->event_description) > 75
                ? '<span title="' . $desc . '" data-bs-toggle="tooltip">' . e(substr($diary->event_description, 0, 75)) . '…</span>'
                : '<span>' . $desc . '</span>';

            // Event title with creator info
            $creator = $diary->user ? e($diary->user->name) : 'User';
            $eventTitle = '<div>
                            <span class="fw-600">' . e($diary->event_name) . '</span>
                            <div class="small text-muted" style="font-size:0.70rem;">By: ' . $creator . '</div>
                           </div>';

            // Action buttons
            $viewBtn = '<button type="button" class="btn btn-sm btn-outline-info btn-view-diary me-1"
                            style="font-size:0.72rem;padding:3px 8px;"
                            data-id="' . $diary->id . '"
                            data-type="' . $diary->type . '"
                            data-shop-name="' . ($diary->shop ? e($diary->shop->shop_name) : '') . '"
                            data-user-name="' . $creator . '"
                            data-event-name="' . e($diary->event_name) . '"
                            data-event-description="' . e($diary->event_description) . '"
                            data-event-date="' . ($diary->event_date ? $diary->event_date->format('d M Y') : '—') . '"
                            data-due-date="' . ($diary->due_date ? $diary->due_date->format('d M Y') : '—') . '"
                            data-cost="' . ($diary->cost !== null ? 'TZS ' . number_format((float) $diary->cost, 0) : '—') . '"
                            data-needs-follow-up="' . ($diary->needs_follow_up ? '1' : '0') . '"
                            data-created-at="' . $diary->created_at->format('d M Y, h:i A') . '"
                            title="View Details">
                            <i class="bi bi-eye-fill"></i>
                        </button>';

            $editBtn = '';
            $deleteBtn = '';

            if ($canManage) {
                $editBtn = '<button type="button" class="btn btn-sm btn-outline-warning btn-edit-diary me-1"
                                style="font-size:0.72rem;padding:3px 8px;"
                                data-id="' . $diary->id . '"
                                data-type="' . $diary->type . '"
                                data-shop-id="' . ($diary->shop_id ?? '') . '"
                                data-event-name="' . e($diary->event_name) . '"
                                data-event-description="' . e($diary->event_description) . '"
                                data-event-date="' . ($diary->event_date ? $diary->event_date->toDateString() : '') . '"
                                data-due-date="' . ($diary->due_date ? $diary->due_date->toDateString() : '') . '"
                                data-cost="' . ($diary->cost ?? '') . '"
                                data-needs-follow-up="' . ($diary->needs_follow_up ? '1' : '0') . '"
                                title="Edit">
                                <i class="bi bi-pencil-fill"></i>
                            </button>';

                $deleteBtn = '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-diary"
                                style="font-size:0.72rem;padding:3px 8px;"
                                data-id="' . $diary->id . '"
                                data-name="' . e($diary->event_name) . '"
                                title="Delete">
                                <i class="bi bi-trash-fill"></i>
                            </button>';
            }

            return [
                'id'                => $diary->id,
                'type'              => $typeBadge,
                'event_name'        => $eventTitle,
                'event_description' => $descDisplay,
                'event_date'        => $eventDate,
                'due_date'          => $dueDate,
                'cost'              => $cost,
                'status'            => $statusBadge,
                'actions'           => $viewBtn . $editBtn . $deleteBtn,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'totals' => [
                'total_entries'  => $recordsTotal,
                'personal_count' => $personalCount,
                'shop_count'     => $shopCount,
                'follow_up'      => $followUpCount,
                'no_follow_up'   => $noFollowUp,
                'total_cost'     => $totalCost,
            ],
        ]);
    }

    // ── Store ───────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'event_name'        => 'required|string|max:255',
            'event_description' => 'required|string|max:5000',
            'event_date'        => 'nullable|date',
            'due_date'          => 'nullable|date|after_or_equal:event_date',
            'cost'              => 'nullable|numeric|min:0',
            'needs_follow_up'   => 'nullable|boolean',
        ];

        if ($user->isOwner()) {
            // Owner is restricted to personal diary only
            $rules['type'] = 'nullable|in:personal';
        } else {
            $rules['type'] = 'required|in:personal,shop';
        }

        $validated = $request->validate($rules);

        if ($user->isOwner()) {
            $validated['type']    = 'personal';
            $validated['shop_id'] = null;
        } else {
            if ($validated['type'] === 'shop') {
                if (!$user->shop_id) {
                    return back()->withErrors(['type' => 'You are not assigned to any shop to record a shop diary entry.'])->withInput();
                }
                $validated['shop_id'] = $user->shop_id;
            } else {
                $validated['shop_id'] = null;
            }
        }

        $validated['user_id']         = $user->id;
        $validated['needs_follow_up'] = $request->boolean('needs_follow_up');

        Diary::create($validated);

        return redirect()->route('diary.index')
            ->with('success', 'Diary entry recorded successfully.');
    }

    // ── Update ──────────────────────────────────────────────────────────────────
    public function update(Request $request, Diary $diary)
    {
        $user = Auth::user();

        if (!$this->canManageDiary($user, $diary)) {
            abort(403, 'Unauthorized action.');
        }

        $rules = [
            'event_name'        => 'required|string|max:255',
            'event_description' => 'required|string|max:5000',
            'event_date'        => 'nullable|date',
            'due_date'          => 'nullable|date|after_or_equal:event_date',
            'cost'              => 'nullable|numeric|min:0',
            'needs_follow_up'   => 'nullable|boolean',
        ];

        if ($user->isOwner()) {
            $rules['type'] = 'nullable|in:personal';
        } else {
            $rules['type'] = 'required|in:personal,shop';
        }

        $validated = $request->validate($rules);

        if ($user->isOwner()) {
            $validated['type']    = 'personal';
            $validated['shop_id'] = null;
        } else {
            if ($validated['type'] === 'shop') {
                if (!$user->shop_id) {
                    return back()->withErrors(['type' => 'You are not assigned to any shop to record a shop diary entry.'])->withInput();
                }
                $validated['shop_id'] = $user->shop_id;
            } else {
                $validated['shop_id'] = null;
            }
        }

        $validated['needs_follow_up'] = $request->boolean('needs_follow_up');

        $diary->update($validated);

        return redirect()->route('diary.index')
            ->with('success', 'Diary entry updated successfully.');
    }

    // ── Destroy ─────────────────────────────────────────────────────────────────
    public function destroy(Diary $diary)
    {
        $user = Auth::user();

        if (!$this->canManageDiary($user, $diary)) {
            abort(403, 'Unauthorized action.');
        }

        $diary->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Diary entry deleted.']);
        }

        return redirect()->route('diary.index')
            ->with('success', 'Diary entry deleted successfully.');
    }
}
