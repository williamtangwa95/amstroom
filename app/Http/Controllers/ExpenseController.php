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
        $user = Auth::user();

        if ($user->isOwner()) {
            // Owner sees expenses recorded by themselves OR approved/processed expenses from others
            $expenses = Expense::with('category', 'recorder', 'approver')
                ->where(function ($query) use ($user) {
                    $query->where('recorded_by', $user->id)
                          ->orWhereIn('status', ['approved', 'review_requested', 'editable']);
                })
                ->latest()
                ->get();
        } elseif ($user->isShopAdmin()) {
            // Admin sees expenses recorded by himself and his sellers
            $sellerIds = \App\Models\User::where('role', 'seller')->pluck('id');
            $expenses = Expense::with('category', 'recorder', 'approver')
                ->where(function ($query) use ($user, $sellerIds) {
                    $query->where('recorded_by', $user->id)
                          ->orWhereIn('recorded_by', $sellerIds);
                })
                ->latest()
                ->get();
        } else {
            // Seller sees only expenses recorded by themselves
            $expenses = Expense::with('category', 'recorder', 'approver')
                ->where('recorded_by', $user->id)
                ->latest()
                ->get();
        }

        return view('expenses.index', compact('expenses'));
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
