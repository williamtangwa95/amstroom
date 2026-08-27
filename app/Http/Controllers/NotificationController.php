<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index()
    {
        $user = Auth::user();
        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Poll endpoint for the layouts/app.blade.php bell component.
     */
    public function poll(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Get unread notifications
        $unreadQuery = Notification::where('user_id', $user->id)
            ->where('is_read', false);

        // Find if there are any notifications that haven't played sound yet
        $unplayedNotifications = (clone $unreadQuery)->where('is_played', false)->get();

        // Mark unplayed notifications as played so sound triggers only once
        if ($unplayedNotifications->isNotEmpty()) {
            Notification::whereIn('id', $unplayedNotifications->pluck('id'))
                ->update(['is_played' => true]);
        }

        // Fetch recent unread notifications to show in the dropdown (max 5)
        $recentUnread = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->latest()
            ->limit(5)
            ->get();

        // Total unread count
        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        // Get custom ringtone URL
        $customRingtone = Setting::get('notification_sound_user_' . $user->id);
        $ringtoneUrl = null;
        if ($customRingtone && Storage::disk('public')->exists($customRingtone)) {
            $ringtoneUrl = asset('media/' . $customRingtone);
        }

        return response()->json([
            'unread_count' => $unreadCount,
            'recent' => $recentUnread,
            'play_sound' => $unplayedNotifications->isNotEmpty(),
            'ringtone_url' => $ringtoneUrl,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function clearAll()
    {
        Notification::where('user_id', Auth::id())
            ->update(['is_read' => true]);
 
        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Mark a notification as read and redirect to target URL.
     */
    public function readAndRedirect(Notification $notification, Request $request)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        $redirectUrl = $request->query('redirect');
        if ($redirectUrl) {
            return redirect($redirectUrl);
        }

        return redirect()->route('notifications.index');
    }
}
