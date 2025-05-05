<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function renderPage() {
        $user = User::find(Auth::id());
        $notifications = $user->getNotifications();
        return view('pages.notifications',compact('notifications'));
    }

    public function moreNotifications(Request $request) {
        $page= $request->input('page');
        $user = User::find(Auth::id());
        $notifications = $user->getMoreNotifications($page);
        $html = view('partials.notifications-load', ['notificationsbanger' => $notifications])->render();

        return response()->json($html);
    }

    public function getUnreadNotificationsCount()
    {
        $unreadCount = Notification::unreadCount(Auth::id());
        return response()->json(['unread_notifications_count' => $unreadCount]);
    }

    public function markAsRead(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notification,id',
        ]);

        Notification::whereIn('id', $request->notification_ids)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Notifications marked as read successfully']);
    }
}
