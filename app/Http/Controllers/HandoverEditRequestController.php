<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\HandoverEditRequest;
use App\Models\Notification;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HandoverEditRequestController extends Controller
{
    /**
     * Shop Admin or Seller submits an unlock request for a handover-locked Sale or Expense.
     */
    public function store(Request $request)
    {
        HandoverEditRequest::ensureTableExists();
        $user = Auth::user();

        if (!$user->isShopAdmin() && !$user->isSeller()) {
            abort(403, 'Only Shop Admins or Sellers can submit handover edit requests.');
        }

        $validated = $request->validate([
            'record_type' => 'required|in:sale,expense',
            'record_id'   => 'required|integer|min:1',
            'action_type' => 'required|in:edit,delete,return',
            'reason'      => 'required|string|min:3|max:1000',
        ], [
            'reason.required' => 'Please explain why you need to modify this handover-locked record.',
        ]);

        $recordType = $validated['record_type'];
        $recordId   = (int) $validated['record_id'];
        $actionType = $validated['action_type'];

        if ($recordType === 'sale') {
            if (!in_array($actionType, ['edit', 'return'], true)) {
                return $this->respondError($request, 'Sales only support Edit or Sale Return unlock requests.', 422);
            }

            $record = Sale::with(['handoverReport', 'shop'])->findOrFail($recordId);
            if ((int) $record->shop_id !== (int) $user->shop_id) {
                abort(403, 'Unauthorized access to this sale.');
            }
            if (!$record->isLockedByHandover()) {
                return $this->respondError($request, 'This sale is not locked by a submitted Cash Handover.', 422);
            }

            $handover   = $record->handoverReport;
            $shopId     = $record->shop_id;
            $recordDesc = "Sale #SL-{$record->id}";
        } else {
            if (!in_array($actionType, ['edit', 'delete'], true)) {
                return $this->respondError($request, 'Expenses only support Edit or Delete unlock requests.', 422);
            }

            $record = Expense::with(['handoverReport', 'recorder'])->findOrFail($recordId);
            $sameShop = $user->isShopAdmin() && (int) ($record->recorder?->shop_id) === (int) $user->shop_id;
            $isRecorder = (int) $record->recorded_by === (int) $user->id;
            if (!$sameShop && !$isRecorder) {
                abort(403, 'Unauthorized access to this expense.');
            }
            if (!$record->isLockedByHandover()) {
                return $this->respondError($request, 'This expense is not locked by a submitted Cash Handover.', 422);
            }

            $handover   = $record->handoverReport;
            $shopId     = $record->recorder?->shop_id ?? $user->shop_id;
            $recordDesc = "Expense #{$record->id} ({$record->activity})";
        }

        // Prevent duplicate active request for the same record
        $existing = HandoverEditRequest::where('record_type', $recordType)
            ->where('record_id', $recordId)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            $msg = $existing->status === 'approved'
                ? "An approved unlock already exists for {$recordDesc}. You can proceed with the {$existing->action_label} now."
                : "An edit request for {$recordDesc} is already pending Owner approval.";
            return $this->respondError($request, $msg, 422);
        }

        $editRequest = HandoverEditRequest::create([
            'handover_report_id' => $handover->id,
            'shop_id'            => $shopId,
            'record_type'        => $recordType,
            'record_id'          => $recordId,
            'action_type'        => $actionType,
            'requested_by'       => $user->id,
            'reason'             => trim($validated['reason']),
            'status'             => 'pending',
        ]);

        ActivityLog::log(
            'HANDOVER_EDIT_REQUESTED',
            "{$user->name} requested {$editRequest->action_label} on {$recordDesc} (Handover {$handover->handover_no}). Reason: {$editRequest->reason}",
            $handover
        );

        // Notify Owners in real-time
        $shopName = $handover->shop?->shop_name ?? $user->shop?->shop_name ?? 'Shop';
        $owners = User::whereIn('role', ['owner', 'system_admin'])->get();
        foreach ($owners as $owner) {
            Notification::create([
                'user_id' => $owner->id,
                'title'   => 'Handover Edit Request Pending',
                'message' => "{$user->name} ({$shopName}) requested {$editRequest->action_label} on {$recordDesc} in Handover #{$handover->id} ({$handover->handover_no}). Reason: {$editRequest->reason}",
            ]);
        }

        $successMsg = "Unlock request submitted to Owner for {$recordDesc} ({$editRequest->action_label}).";
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'request' => $editRequest,
            ]);
        }

        return back()->with('success', $successMsg);
    }

    /**
     * Owner approves an unlock request (single-use unlock until the authorized action finishes).
     */
    public function approve(Request $request, HandoverEditRequest $editRequest)
    {
        HandoverEditRequest::ensureTableExists();
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Only the Owner can approve handover edit requests.');
        }

        if ($editRequest->status !== 'pending') {
            return $this->respondError($request, 'This unlock request has already been processed.', 422);
        }

        $validated = $request->validate([
            'review_remarks' => 'nullable|string|max:500',
        ]);

        $editRequest->update([
            'status'         => 'approved',
            'reviewed_by'    => $user->id,
            'reviewed_at'    => now(),
            'review_remarks' => $validated['review_remarks'] ?? null,
        ]);

        $editRequest->load(['handoverReport', 'requester']);
        $handover = $editRequest->handoverReport;
        $recordDesc = $editRequest->record_label;

        ActivityLog::log(
            'HANDOVER_EDIT_APPROVED',
            "Owner {$user->name} approved {$editRequest->action_label} unlock on {$recordDesc} (Handover {$handover?->handover_no}).",
            $handover ?? $editRequest
        );

        // Notify requester (and shop admin if requester was a seller)
        $notifyUserIds = collect([$editRequest->requested_by]);
        if ($editRequest->shop_id) {
            $shopAdminIds = User::where('shop_id', $editRequest->shop_id)
                ->where('role', 'shop_admin')
                ->pluck('id');
            $notifyUserIds = $notifyUserIds->merge($shopAdminIds);
        }

        foreach ($notifyUserIds->unique()->filter() as $uid) {
            Notification::create([
                'user_id' => $uid,
                'title'   => 'Handover Unlock Approved',
                'message' => "Owner {$user->name} approved {$editRequest->action_label} on {$recordDesc} (Handover {$handover?->handover_no}). You may now complete the change.",
            ]);
        }

        $msg = "Unlock approved for {$recordDesc} ({$editRequest->action_label}). It will automatically re-lock once completed.";
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Owner rejects an unlock request — record remains locked.
     */
    public function reject(Request $request, HandoverEditRequest $editRequest)
    {
        HandoverEditRequest::ensureTableExists();
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Only the Owner can reject handover edit requests.');
        }

        if ($editRequest->status !== 'pending') {
            return $this->respondError($request, 'This unlock request has already been processed.', 422);
        }

        $validated = $request->validate([
            'review_remarks' => 'required|string|min:2|max:500',
        ], [
            'review_remarks.required' => 'Please provide a reason for rejecting this unlock request.',
        ]);

        $editRequest->update([
            'status'         => 'rejected',
            'reviewed_by'    => $user->id,
            'reviewed_at'    => now(),
            'review_remarks' => trim($validated['review_remarks']),
        ]);

        $editRequest->load(['handoverReport', 'requester']);
        $handover = $editRequest->handoverReport;
        $recordDesc = $editRequest->record_label;

        ActivityLog::log(
            'HANDOVER_EDIT_REJECTED',
            "Owner {$user->name} rejected {$editRequest->action_label} unlock on {$recordDesc} (Handover {$handover?->handover_no}). Remarks: {$editRequest->review_remarks}",
            $handover ?? $editRequest
        );

        Notification::create([
            'user_id' => $editRequest->requested_by,
            'title'   => 'Handover Unlock Request Rejected',
            'message' => "Owner {$user->name} rejected your {$editRequest->action_label} request for {$recordDesc}. Remarks: {$editRequest->review_remarks}",
        ]);

        $msg = "Unlock request rejected. {$recordDesc} remains handover-locked.";
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    private function respondError(Request $request, string $message, int $status = 422)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return back()->with('error', $message);
    }
}
