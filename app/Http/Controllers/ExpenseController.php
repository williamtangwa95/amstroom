<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    private function scopedExpenseQuery($user)
    {
        $query = Expense::query();

        if ($user->isOwner()) {
            $query->where(function ($q) use ($user) {
                $q->where('recorded_by', $user->id)
                  ->orWhereIn('status', ['approved', 'review_requested', 'editable']);
            });
        } elseif ($user->isShopAdmin()) {
            $sellerIds = \App\Models\User::where('role', 'seller')
                ->where('shop_id', $user->shop_id)
                ->pluck('id');
            $query->where(function ($q) use ($user, $sellerIds) {
                $q->where('recorded_by', $user->id)
                  ->orWhereIn('recorded_by', $sellerIds);
            });
        } else {
            $query->where('recorded_by', $user->id);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // Default to current month if no date filter provided
        $dateFrom = $request->has('date_from') ? $request->date_from : now()->startOfMonth()->toDateString();
        $dateTo   = $request->has('date_to')   ? $request->date_to   : now()->endOfMonth()->toDateString();

        $query = $this->scopedExpenseQuery($user);

        if (!empty($dateFrom)) {
            $query->whereDate('expenses.activity_date', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('expenses.activity_date', '<=', $dateTo);
        }

        if ($request->filled('category_id')) {
            $query->where('expenses.expense_category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('expenses.status', $request->status);
        }

        $totalExpenses    = (float) (clone $query)->sum('amount');
        $approvedExpenses = (float) (clone $query)->where('status', 'approved')->sum('amount');
        $pendingExpenses  = (float) (clone $query)->where('status', 'pending')->sum('amount');
        $expensesCount    = (int) (clone $query)->count();

        $categories = ExpenseCategory::orderBy('name')->get();

        return view('expenses.index', compact(
            'totalExpenses',
            'approvedExpenses',
            'pendingExpenses',
            'expensesCount',
            'dateFrom',
            'dateTo',
            'categories'
        ));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $query = $this->scopedExpenseQuery($user);

        // Default to current month if no date filter provided
        $dateFrom = $request->has('date_from') ? $request->date_from : now()->startOfMonth()->toDateString();
        $dateTo   = $request->has('date_to')   ? $request->date_to   : now()->endOfMonth()->toDateString();

        if (!empty($dateFrom)) {
            $query->whereDate('expenses.activity_date', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('expenses.activity_date', '<=', $dateTo);
        }

        if ($request->filled('category_id')) {
            $query->where('expenses.expense_category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('expenses.status', $request->status);
        }

        $recordsTotal = (clone $query)->count();

        // Calculate totals for the filtered range
        $totalsQuery = clone $query;
        $totalExpenses    = (float) (clone $totalsQuery)->sum('amount');
        $approvedExpenses = (float) (clone $totalsQuery)->where('status', 'approved')->sum('amount');
        $pendingExpenses  = (float) (clone $totalsQuery)->where('status', 'pending')->sum('amount');

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $cleanId = preg_replace('/[^0-9]/', '', $searchValue);
                if ($cleanId !== '') {
                    $q->orWhere('expenses.id', $cleanId);
                }
                $q->orWhere('expenses.activity', 'like', "%{$searchValue}%")
                  ->orWhere('expenses.description', 'like', "%{$searchValue}%")
                  ->orWhere('expenses.status', 'like', "%{$searchValue}%")
                  ->orWhereHas('category', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('recorder', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('approver', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [10, 25, 50, 100];
        $requestedLength = (int) $request->input('length', 10);
        $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

        $expenses = $query->with('category', 'recorder', 'approver', 'handoverReport')
            ->orderBy('expenses.activity_date', 'desc')
            ->orderBy('expenses.id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        // Batch-load active handover edit/delete unlock requests for this page
        \App\Models\HandoverEditRequest::ensureTableExists();
        $expenseIds = $expenses->pluck('id')->all();
        $activeRequestsByExpense = empty($expenseIds)
            ? collect()
            : \App\Models\HandoverEditRequest::with('requester')
                ->where('record_type', 'expense')
                ->whereIn('record_id', $expenseIds)
                ->whereIn('status', ['pending', 'approved'])
                ->orderByRaw("CASE WHEN status = 'approved' THEN 0 ELSE 1 END")
                ->orderByDesc('id')
                ->get()
                ->groupBy('record_id')
                ->map(fn ($group) => $group->first());

        $isOwnerOrAdmin = $user->isOwner() || $user->isShopAdmin();

        $data = [];
        foreach ($expenses as $index => $expense) {
            $iteration = $start + $index + 1;
            $dateStr = $expense->activity_date ? $expense->activity_date->format('M d, Y') : 'N/A';
            $categoryName = '<span class="badge" style="background:rgba(188,140,255,.12);color:#bc8cff;">' . e($expense->category?->name ?? 'General') . '</span>';
            $activity = '<strong>' . e($expense->activity) . '</strong>';
            $descText = e($expense->description ?? '—');
            $descriptionHtml = '<span style="max-width: 200px; display:inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="' . $descText . '">' . $descText . '</span>';
            $amountHtml = '<strong class="text-dark">TZS ' . number_format($expense->amount, 0) . '</strong>';
            $recorderName = e($expense->recorder?->name ?? '—');
            $approverName = e($expense->approver?->name ?? '—');

            $isLockedByHandover = $expense->isLockedByHandover();
            $handoverNo = $expense->handoverReport?->handover_no;
            $activeReq = $activeRequestsByExpense->get($expense->id);

            if ($expense->isPending()) {
                $statusBadge = '<span class="badge badge-pending"><i class="bi bi-hourglass-split me-1"></i>Pending</span>';
            } elseif ($expense->isApproved()) {
                $statusBadge = '<span class="badge badge-approved"><i class="bi bi-check-circle-fill me-1"></i>Approved</span>';
            } elseif ($expense->isReviewRequested()) {
                $statusBadge = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Review Requested</span>';
            } elseif ($expense->isEditable()) {
                $statusBadge = '<span class="badge bg-info text-dark"><i class="bi bi-pencil-square me-1"></i>Editable</span>';
            } else {
                $statusBadge = '<span class="badge bg-secondary">' . e(ucfirst($expense->status)) . '</span>';
            }

            if ($isLockedByHandover) {
                $statusBadge .= '<div class="mt-1"><span class="badge bg-dark-subtle text-dark border" style="font-size:.66rem;max-width:100%;white-space:normal;word-break:break-word;text-align:left;" title="Locked under submitted Cash Handover ' . e($handoverNo) . '"><i class="bi bi-lock-fill me-1"></i>' . e($handoverNo) . '</span></div>';
                if ($activeReq && $activeReq->status === 'pending') {
                    $statusBadge .= '<div class="mt-1"><span class="badge bg-warning text-dark" style="font-size:.65rem;max-width:100%;white-space:normal;word-break:break-word;text-align:left;" title="' . e($activeReq->reason) . '"><i class="bi bi-hourglass-split me-1"></i>' . e($activeReq->action_label) . ' Requested</span></div>';
                } elseif ($activeReq && $activeReq->status === 'approved') {
                    $statusBadge .= '<div class="mt-1"><span class="badge bg-info text-dark" style="font-size:.65rem;max-width:100%;white-space:normal;word-break:break-word;text-align:left;"><i class="bi bi-unlock-fill me-1"></i>Unlocked: ' . e($activeReq->action_label) . '</span></div>';
                }
            }

            $actions = '<div class="d-flex justify-content-end gap-1 flex-wrap">';
            if ($isLockedByHandover && !$user->isOwner()) {
                // Handover-locked expense for Shop Admin or Seller: strictly gated by Owner unlock approval
                if ($activeReq && $activeReq->status === 'approved') {
                    if ($activeReq->action_type === 'edit') {
                        $actions .= '<a href="' . route('expenses.edit', $expense) . '" class="btn btn-xs btn-accent" title="Edit (Owner Approved)"><i class="bi bi-pencil me-1"></i>Edit</a>';
                    } elseif ($activeReq->action_type === 'delete') {
                        $actions .= '<form method="POST" action="' . route('expenses.destroy', $expense) . '" class="d-inline" onsubmit="return confirm(\'Delete this handover-linked expense? Handover totals will recalculate.\');">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-xs btn-danger" title="Delete (Owner Approved)"><i class="bi bi-trash me-1"></i>Delete</button></form>';
                    }
                } elseif ($activeReq && $activeReq->status === 'pending') {
                    $actions .= '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1 px-2" style="font-size:.7rem;"><i class="bi bi-hourglass-split me-1"></i>Awaiting Owner</span>';
                } else {
                    $actions .= '<button type="button" class="btn btn-xs btn-outline-warning text-dark request-handover-edit-btn" data-record-type="expense" data-record-id="' . $expense->id . '" data-record-label="Expense #' . $expense->id . ' (' . e($expense->activity) . ')" data-handover-no="' . e($handoverNo) . '" title="Request Edit or Delete Approval from Owner"><i class="bi bi-shield-lock me-1"></i>Request Edit</button>';
                }
            } elseif ($user->isShopAdmin()) {
                if ($expense->isPending()) {
                    $actions .= '<form method="POST" action="' . route('expenses.approve', $expense) . '" class="d-inline">' . csrf_field() . '<button type="submit" class="btn btn-xs btn-outline-custom btn-success" title="Approve Expense"><i class="bi bi-check-lg"></i> </button></form>';
                    $actions .= '<a href="' . route('expenses.edit', $expense) . '" class="btn btn-xs btn-outline-custom" title="Edit"><i class="bi bi-pencil"></i></a>';
                    $actions .= '<form method="POST" action="' . route('expenses.destroy', $expense) . '" class="d-inline" onsubmit="return confirm(\'Delete this expense?\');">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-xs btn-outline-custom text-danger" title="Delete"><i class="bi bi-trash"></i></button></form>';
                } elseif ($expense->isApproved()) {
                    $actions .= '<form method="POST" action="' . route('expenses.request-review', $expense) . '" class="d-inline">' . csrf_field() . '<button type="submit" class="btn btn-xs btn-outline-custom text-warning" title="Request Edit Review"><i class="bi bi-shield-exclamation"></i> Request Edit</button></form>';
                } elseif ($expense->isEditable()) {
                    $actions .= '<a href="' . route('expenses.edit', $expense) . '" class="btn btn-xs btn-accent me-1" title="Edit"><i class="bi bi-pencil"></i> Edit</a>';
                    $actions .= '<form method="POST" action="' . route('expenses.destroy', $expense) . '" class="d-inline" onsubmit="return confirm(\'Delete this expense?\');">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-xs btn-outline-custom text-danger" title="Delete"><i class="bi bi-trash"></i></button></form>';
                }
            } elseif ($user->isOwner()) {
                if ($isLockedByHandover && $activeReq && $activeReq->status === 'pending') {
                    $actions .= '<button type="button" class="btn btn-xs btn-success owner-approve-unlock-btn" data-request-id="' . $activeReq->id . '" data-label="Expense #' . $expense->id . ' — ' . e($expense->activity) . ' (' . e($activeReq->action_label) . ')" data-reason="' . e($activeReq->reason) . '" title="Approve Unlock Request"><i class="bi bi-unlock-fill me-1"></i>Approve</button>';
                    $actions .= '<button type="button" class="btn btn-xs btn-outline-danger owner-reject-unlock-btn" data-request-id="' . $activeReq->id . '" data-label="Expense #' . $expense->id . ' — ' . e($expense->activity) . '" title="Reject Unlock Request"><i class="bi bi-x-lg"></i></button>';
                }
                if (!$isLockedByHandover && $expense->isReviewRequested()) {
                    $actions .= '<form method="POST" action="' . route('expenses.grant-edit', $expense) . '" class="d-inline">' . csrf_field() . '<button type="submit" class="btn btn-xs btn-outline-custom btn-info text-dark" title="Grant Edit Ability"><i class="bi bi-unlock-fill"></i> Grant Edit</button></form>';
                }
                if ($expense->isPending() || $expense->isEditable() || $isLockedByHandover) {
                    $confirmDel = $isLockedByHandover ? 'Delete this handover-linked expense? Linked handover totals will recalculate.' : 'Delete this expense?';
                    $actions .= '<form method="POST" action="' . route('expenses.destroy', $expense) . '" class="d-inline" onsubmit="return confirm(\'' . $confirmDel . '\');">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-xs btn-outline-custom text-danger" title="Delete"><i class="bi bi-trash"></i></button></form>';
                }
                if ($expense->isPending()) {
                    $actions .= '<form method="POST" action="' . route('expenses.approve', $expense) . '" class="d-inline">' . csrf_field() . '<button type="submit" class="btn btn-xs btn-outline-custom btn-success" title="Approve Expense"><i class="bi bi-check-lg"></i> </button></form>';
                }
                if (!$isLockedByHandover && ($expense->isApproved() || $expense->isReviewRequested() || $expense->isEditable())) {
                    $actions .= '<form method="POST" action="' . route('expenses.revert-approval', $expense) . '" class="d-inline" onsubmit="return confirm(\'Revert approval for this expense?\');">' . csrf_field() . '<button type="submit" class="btn btn-xs btn-outline-custom text-warning" title="Revert Approval"><i class="bi bi-arrow-counterclockwise"></i> Revert</button></form>';
                }
                $actions .= '<a href="' . route('expenses.edit', $expense) . '" class="btn btn-xs btn-outline-custom" title="Edit"><i class="bi bi-pencil"></i></a>';
            } else { // Seller
                if ($expense->isPending()) {
                    $actions .= '<a href="' . route('expenses.edit', $expense) . '" class="btn btn-xs btn-outline-custom" title="Edit"><i class="bi bi-pencil"></i></a>';
                    $actions .= '<form method="POST" action="' . route('expenses.destroy', $expense) . '" class="d-inline" onsubmit="return confirm(\'Delete this expense?\');">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-xs btn-outline-custom text-danger" title="Delete"><i class="bi bi-trash"></i></button></form>';
                } elseif ($expense->isEditable()) {
                    $actions .= '<a href="' . route('expenses.edit', $expense) . '" class="btn btn-xs btn-accent me-1" title="Edit"><i class="bi bi-pencil"></i> Edit</a>';
                    $actions .= '<form method="POST" action="' . route('expenses.destroy', $expense) . '" class="d-inline" onsubmit="return confirm(\'Delete this expense?\');">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-xs btn-outline-custom text-danger" title="Delete"><i class="bi bi-trash"></i></button></form>';
                } elseif ($expense->isApproved()) {
                    $actions .= '<span class="text-muted small py-1 px-2"><i class="bi bi-lock-fill"></i> Locked</span>';
                }
            }
            $actions .= '</div>';

            $row = [];
            if ($isOwnerOrAdmin) {
                $row['checkbox'] = $expense->isPending()
                    ? '<input type="checkbox" class="expense-checkbox form-check-input" value="' . $expense->id . '">'
                    : '<input type="checkbox" class="form-check-input" disabled style="opacity:0.4;">';
            }
            $row['iteration'] = $iteration;
            $row['date'] = $dateStr;
            $row['category'] = $categoryName;
            $row['activity'] = $activity;
            $row['description'] = $descriptionHtml;
            $row['amount'] = $amountHtml;
            $row['recorder'] = $recorderName;
            $row['approver'] = $approverName;
            $row['status'] = $statusBadge;
            $row['actions'] = $actions;

            $data[] = $row;
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'totalExpenses' => $totalExpenses,
            'formattedTotalExpenses' => 'TZS ' . number_format($totalExpenses, 0),
            'approvedExpenses' => 'TZS ' . number_format($approvedExpenses, 0),
            'pendingExpenses' => 'TZS ' . number_format($pendingExpenses, 0),
            'totalCount' => number_format($recordsFiltered),
        ]);
    }

    public function create()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $idempotencyKey = $request->input('idempotency_key');
        if ($idempotencyKey) {
            $existingId = \Illuminate\Support\Facades\Cache::get("idempotency_expense_{$idempotencyKey}");
            if ($existingId) {
                return redirect()->route('expenses.index')
                    ->with('success', 'Expense already recorded!');
            }
        }

        $request->validate([
            'idempotency_key'     => 'nullable|string|max:64',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'activity'            => 'required|string|max:150',
            'description'         => 'nullable|string',
            'amount'              => 'required|numeric|min:0',
            'activity_date'       => 'required|date',
        ]);

        $expense = Expense::create([
            'expense_category_id' => $request->expense_category_id,
            'activity'            => $request->activity,
            'description'         => $request->description,
            'amount'              => $request->amount,
            'activity_date'       => $request->activity_date,
            'recorded_by'         => $user->id,
            'status'              => 'pending',
        ]);

        if ($idempotencyKey) {
            \Illuminate\Support\Facades\Cache::put("idempotency_expense_{$idempotencyKey}", $expense->id, now()->addMinutes(10));
        }

        if ($user->isSeller()) {
            $admins = \App\Models\User::where('role', 'shop_admin')
                ->where('shop_id', $user->shop_id)
                ->get();
            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'title'   => 'New Expense Recorded',
                    'message' => "Seller {$user->name} recorded a new expense: \"{$expense->activity}\" of TZS " . number_format($expense->amount) . ", pending approval.",
                ]);
            }
        }

        return redirect()->route('expenses.index')
            ->with('success', 'Expense recorded successfully and is pending approval.');
    }

    public function edit(Expense $expense)
    {
        $user = Auth::user();

        // Handover lock takes highest precedence for Shop Admin & Seller
        if ($expense->isLockedByHandover()) {
            if (!$user->isOwner()) {
                $sameShop = $user->isShopAdmin() && $expense->recorder?->shop_id === $user->shop_id;
                $isRecorder = $expense->recorded_by === $user->id;
                if ((!$sameShop && !$isRecorder) || !$expense->canMutateUnderHandover($user, 'edit')) {
                    abort(403, 'This expense is locked by a submitted Cash Handover (' . ($expense->handoverReport?->handover_no ?? '') . '). Please request edit approval from the Owner first.');
                }
            }
        } elseif ($user->isOwner()) {
            // Owner is always allowed
        } elseif ($expense->isPending()) {
            if (($user->isShopAdmin() && $expense->recorder?->shop_id === $user->shop_id) || $expense->recorded_by === $user->id) {
                // allowed
            } else {
                abort(403, 'Unauthorized action.');
            }
        } elseif ($expense->isEditable()) {
            if (($user->isShopAdmin() && $expense->recorder?->shop_id === $user->shop_id) || $expense->recorded_by === $user->id) {
                // allowed
            } else {
                abort(403, 'Unauthorized action. Only authorized admins or the recording seller can edit granted expenses.');
            }
        } else {
            abort(403, 'This expense is locked. Request a review from the owner to edit it.');
        }

        $categories = ExpenseCategory::orderBy('name')->get();
        return view('expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        $user = Auth::user();
        $wasHandoverLocked = $expense->isLockedByHandover();

        if ($wasHandoverLocked) {
            if (!$user->isOwner()) {
                $sameShop = $user->isShopAdmin() && $expense->recorder?->shop_id === $user->shop_id;
                $isRecorder = $expense->recorded_by === $user->id;
                if ((!$sameShop && !$isRecorder) || !$expense->canMutateUnderHandover($user, 'edit')) {
                    abort(403, 'This expense is locked by a submitted Cash Handover. Please request edit approval from the Owner first.');
                }
            }
        } elseif ($user->isOwner()) {
            // Owner is always allowed
        } elseif ($expense->isPending()) {
            if (($user->isShopAdmin() && $expense->recorder?->shop_id === $user->shop_id) || $expense->recorded_by === $user->id) {
                // allowed
            } else {
                abort(403, 'Unauthorized action.');
            }
        } elseif ($expense->isEditable()) {
            if (($user->isShopAdmin() && $expense->recorder?->shop_id === $user->shop_id) || $expense->recorded_by === $user->id) {
                // allowed
            } else {
                abort(403, 'Unauthorized action.');
            }
        } else {
            abort(403, 'This expense is locked.');
        }

        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'activity'            => 'required|string|max:150',
            'description'         => 'nullable|string',
            'amount'              => 'required|numeric|min:0',
            'activity_date'       => 'required|date',
        ]);

        $data = $request->only('expense_category_id', 'activity', 'description', 'amount', 'activity_date');

        if ($wasHandoverLocked || $expense->isEditable() || ($user->isOwner() && !$expense->isPending())) {
            $data['status'] = 'approved';
        }

        $expense->update($data);

        if ($wasHandoverLocked) {
            if (!$user->isOwner()) {
                $expense->approvedHandoverUnlock('edit')?->markConsumed();
            }
            $expense->handoverReport?->recalculateTotals("Edited Expense #{$expense->id} ({$expense->activity})");
        }

        return redirect()->route('expenses.index')
            ->with('success', $wasHandoverLocked
                ? 'Expense updated, handover totals recalculated, and expense re-locked.'
                : 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $user = Auth::user();
        $wasHandoverLocked = $expense->isLockedByHandover();
        $handoverReport = $wasHandoverLocked ? $expense->handoverReport : null;
        $expenseId = $expense->id;
        $expenseActivity = $expense->activity;

        if ($wasHandoverLocked) {
            if (!$user->isOwner()) {
                $sameShop = $user->isShopAdmin() && $expense->recorder?->shop_id === $user->shop_id;
                $isRecorder = $expense->recorded_by === $user->id;
                if ((!$sameShop && !$isRecorder) || !$expense->canMutateUnderHandover($user, 'delete')) {
                    abort(403, 'Cannot delete an expense locked by a submitted Cash Handover without Owner approval.');
                }
            }

            $approvedUnlock = !$user->isOwner() ? $expense->approvedHandoverUnlock('delete') : null;
            $expense->delete();
            $approvedUnlock?->markConsumed();
            $handoverReport?->recalculateTotals("Deleted Expense #{$expenseId} ({$expenseActivity})");

            return redirect()->route('expenses.index')
                ->with('success', 'Expense deleted and linked handover totals recalculated.');
        }

        if (!$expense->isPending() && !$expense->isEditable()) {
            abort(403, 'Cannot delete an approved or locked expense.');
        }

        if ($user->isOwner() || ($user->isShopAdmin() && $expense->recorder?->shop_id === $user->shop_id) || $expense->recorded_by === $user->id) {
            $expense->delete();
            return redirect()->route('expenses.index')
                ->with('success', 'Expense deleted successfully.');
        }

        abort(403, 'Unauthorized action.');
    }

    public function approve(Expense $expense)
    {
        $user = Auth::user();

        if (!$user->isShopAdmin() && !$user->isOwner()) {
            abort(403, 'Unauthorized action.');
        }

        if ($user->isShopAdmin() && $expense->recorder?->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized action.');
        }

        if (!$expense->isPending()) {
            return back()->with('error', 'This expense has already been processed.');
        }

        $expense->update([
            'status' => 'approved',
            'approved_by' => $user->id,
        ]);

        if ($user->isShopAdmin()) {
            $owners = \App\Models\User::whereIn('role', ['owner', 'system_admin'])->get();
            foreach ($owners as $owner) {
                \App\Models\Notification::create([
                    'user_id' => $owner->id,
                    'title'   => 'Expense Approved by Admin',
                    'message' => "Admin {$user->name} approved expense: \"{$expense->activity}\" of TZS " . number_format($expense->amount) . ".",
                ]);
            }
        }

        return redirect()->route('expenses.index')
            ->with('success', 'Expense approved successfully.');
    }

    public function bulkApprove(Request $request)
    {
        $user = Auth::user();

        if (!$user->isOwner() && !$user->isShopAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:expenses,id',
        ]);

        $query = Expense::whereIn('id', $request->ids)
            ->where('status', 'pending');

        if ($user->isShopAdmin()) {
            $query->whereHas('recorder', function ($q) use ($user) {
                $q->where('shop_id', $user->shop_id);
            });
        }

        $query->update([
            'status' => 'approved',
            'approved_by' => $user->id,
        ]);

        if ($user->isShopAdmin()) {
            $owners = \App\Models\User::whereIn('role', ['owner', 'system_admin'])->get();
            foreach ($owners as $owner) {
                \App\Models\Notification::create([
                    'user_id' => $owner->id,
                    'title'   => 'Expenses Approved by Admin (Bulk)',
                    'message' => "Admin {$user->name} approved " . count($request->ids) . " selected expenses.",
                ]);
            }
        }

        return redirect()->route('expenses.index')
            ->with('success', 'Selected expenses approved successfully.');
    }

    public function requestReview(Expense $expense)
    {
        $user = Auth::user();

        if (!$user->isShopAdmin()) {
            abort(403, 'Only admins can request edit review.');
        }

        if ($expense->recorder?->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized action.');
        }

        if (!$expense->isApproved()) {
            return back()->with('error', 'Only approved expenses can be requested for review.');
        }

        $expense->update([
            'status' => 'review_requested',
        ]);

        $owners = \App\Models\User::whereIn('role', ['owner', 'system_admin'])->get();
        foreach ($owners as $owner) {
            \App\Models\Notification::create([
                'user_id' => $owner->id,
                'title'   => 'Expense Edit Review Requested',
                'message' => "Admin {$user->name} requested to edit approved expense: \"{$expense->activity}\".",
            ]);
        }

        return redirect()->route('expenses.index')
            ->with('success', 'Review requested from owner.');
    }

    public function grantEdit(Expense $expense)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Only the owner can grant edit permission.');
        }

        if (!$expense->isReviewRequested()) {
            return back()->with('error', 'No edit review requested for this expense.');
        }

        $expense->update([
            'status' => 'editable',
        ]);

        $adminToNotifyId = $expense->approved_by;
        if ($adminToNotifyId) {
            \App\Models\Notification::create([
                'user_id' => $adminToNotifyId,
                'title'   => 'Expense Edit Permission Granted',
                'message' => "Owner {$user->name} granted you permission to edit the expense: \"{$expense->activity}\".",
            ]);
        } else {
            $admins = \App\Models\User::where('role', 'shop_admin')->get();
            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'title'   => 'Expense Edit Permission Granted',
                    'message' => "Owner {$user->name} granted edit permission for expense: \"{$expense->activity}\".",
                ]);
            }
        }

        return redirect()->route('expenses.index')
            ->with('success', 'Edit permission granted to admin.');
    }

    public function revertApproval(Expense $expense)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Only the owner can revert expense approvals.');
        }

        if ($expense->isLockedByHandover()) {
            return back()->with('error', 'This expense is part of a submitted Cash Handover (' . ($expense->handoverReport?->handover_no ?? '') . ') and its approval cannot be reverted.');
        }

        if (!$expense->isApproved() && !$expense->isReviewRequested() && !$expense->isEditable()) {
            return back()->with('error', 'Only processed expenses can be reverted.');
        }

        $expense->update([
            'status' => 'pending',
            'approved_by' => null,
        ]);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense approval reverted successfully. Status is now pending.');
    }
}
