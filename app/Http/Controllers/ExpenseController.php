<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index()
    {
        return view('expenses.index');
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $query = Expense::query();

        if ($user->isOwner()) {
            $query->where(function ($q) use ($user) {
                $q->where('recorded_by', $user->id)
                  ->orWhereIn('status', ['approved', 'review_requested', 'editable']);
            });
        } elseif ($user->isShopAdmin()) {
            $sellerIds = \App\Models\User::where('role', 'seller')->pluck('id');
            $query->where(function ($q) use ($user, $sellerIds) {
                $q->where('recorded_by', $user->id)
                  ->orWhereIn('recorded_by', $sellerIds);
            });
        } else {
            $query->where('recorded_by', $user->id);
        }

        $recordsTotal = (clone $query)->count();

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

        $expenses = $query->with('category', 'recorder', 'approver')
            ->orderBy('expenses.activity_date', 'desc')
            ->orderBy('expenses.id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

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

            $actions = '<div class="d-flex justify-content-end gap-1">';
            if ($user->isShopAdmin()) {
                if ($expense->isPending()) {
                    $actions .= '<form method="POST" action="' . route('expenses.approve', $expense) . '" class="d-inline">' . csrf_field() . '<button type="submit" class="btn btn-xs btn-outline-custom btn-success" title="Approve Expense"><i class="bi bi-check-lg"></i> </button></form>';
                    $actions .= '<a href="' . route('expenses.edit', $expense) . '" class="btn btn-xs btn-outline-custom" title="Edit"><i class="bi bi-pencil"></i></a>';
                    $actions .= '<form method="POST" action="' . route('expenses.destroy', $expense) . '" class="d-inline" onsubmit="return confirm(\'Delete this expense?\');">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-xs btn-outline-custom text-danger" title="Delete"><i class="bi bi-trash"></i></button></form>';
                } elseif ($expense->isApproved()) {
                    $actions .= '<form method="POST" action="' . route('expenses.request-review', $expense) . '" class="d-inline">' . csrf_field() . '<button type="submit" class="btn btn-xs btn-outline-custom text-warning" title="Request Edit Review"><i class="bi bi-shield-exclamation"></i> Request Edit</button></form>';
                } elseif ($expense->isEditable()) {
                    $actions .= '<a href="' . route('expenses.edit', $expense) . '" class="btn btn-xs btn-accent" title="Edit"><i class="bi bi-pencil"></i> Edit</a>';
                }
            } elseif ($user->isOwner()) {
                if ($expense->isReviewRequested()) {
                    $actions .= '<form method="POST" action="' . route('expenses.grant-edit', $expense) . '" class="d-inline">' . csrf_field() . '<button type="submit" class="btn btn-xs btn-outline-custom btn-info text-dark" title="Grant Edit Ability"><i class="bi bi-unlock-fill"></i> Grant Edit</button></form>';
                }
                if ($expense->isPending()) {
                    $actions .= '<form method="POST" action="' . route('expenses.approve', $expense) . '" class="d-inline">' . csrf_field() . '<button type="submit" class="btn btn-xs btn-outline-custom btn-success" title="Approve Expense"><i class="bi bi-check-lg"></i> </button></form>';
                    $actions .= '<form method="POST" action="' . route('expenses.destroy', $expense) . '" class="d-inline" onsubmit="return confirm(\'Delete this expense?\');">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-xs btn-outline-custom text-danger" title="Delete"><i class="bi bi-trash"></i></button></form>';
                }
                if ($expense->isApproved() || $expense->isReviewRequested() || $expense->isEditable()) {
                    $actions .= '<form method="POST" action="' . route('expenses.revert-approval', $expense) . '" class="d-inline" onsubmit="return confirm(\'Revert approval for this expense?\');">' . csrf_field() . '<button type="submit" class="btn btn-xs btn-outline-custom text-warning" title="Revert Approval"><i class="bi bi-arrow-counterclockwise"></i> Revert</button></form>';
                }
                $actions .= '<a href="' . route('expenses.edit', $expense) . '" class="btn btn-xs btn-outline-custom" title="Edit"><i class="bi bi-pencil"></i></a>';
            } else { // Seller
                if ($expense->isPending()) {
                    $actions .= '<a href="' . route('expenses.edit', $expense) . '" class="btn btn-xs btn-outline-custom" title="Edit"><i class="bi bi-pencil"></i></a>';
                    $actions .= '<form method="POST" action="' . route('expenses.destroy', $expense) . '" class="d-inline" onsubmit="return confirm(\'Delete this expense?\');">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-xs btn-outline-custom text-danger" title="Delete"><i class="bi bi-trash"></i></button></form>';
                }
                if ($expense->isApproved()) {
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

        $request->validate([
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

        if ($user->isSeller()) {
            $admins = \App\Models\User::where('role', 'shop_admin')->get();
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

        if ($user->isOwner()) {
            // Owner is always allowed
        } elseif ($expense->isPending()) {
            if ($user->isShopAdmin() || $expense->recorded_by === $user->id) {
                // allowed
            } else {
                abort(403, 'Unauthorized action.');
            }
        } elseif ($expense->isEditable()) {
            if ($user->isShopAdmin()) {
                // allowed
            } else {
                abort(403, 'Unauthorized action. Only admins can edit granted expenses.');
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

        if ($user->isOwner()) {
            // Owner is always allowed
        } elseif ($expense->isPending()) {
            if ($user->isOwner() || $user->isShopAdmin() || $expense->recorded_by === $user->id) {
                // allowed
            } else {
                abort(403, 'Unauthorized action.');
            }
        } elseif ($expense->isEditable()) {
            if ($user->isShopAdmin()) {
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

        if ($expense->isEditable() || ($user->isOwner() && !$expense->isPending())) {
            $data['status'] = 'approved';
        }

        $expense->update($data);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $user = Auth::user();

        if (!$expense->isPending()) {
            abort(403, 'Cannot delete an approved or locked expense.');
        }

        if ($user->isOwner() || $user->isShopAdmin() || $expense->recorded_by === $user->id) {
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

        if (!$expense->isPending()) {
            return back()->with('error', 'This expense has already been processed.');
        }

        $expense->update([
            'status' => 'approved',
            'approved_by' => $user->id,
        ]);

        if ($user->isShopAdmin()) {
            $owners = \App\Models\User::where('role', 'owner')->get();
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

        Expense::whereIn('id', $request->ids)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'approved_by' => $user->id,
            ]);

        if ($user->isShopAdmin()) {
            $owners = \App\Models\User::where('role', 'owner')->get();
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

        if (!$expense->isApproved()) {
            return back()->with('error', 'Only approved expenses can be requested for review.');
        }

        $expense->update([
            'status' => 'review_requested',
        ]);

        $owners = \App\Models\User::where('role', 'owner')->get();
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
