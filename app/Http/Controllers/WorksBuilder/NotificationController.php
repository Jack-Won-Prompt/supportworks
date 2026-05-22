<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Models\WorksBuilder\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = Notification::where('recipient_id', Auth::id())
            ->latest()
            ->paginate(30);

        $unreadCount = Notification::where('recipient_id', Auth::id())->unread()->count();

        return view('works-builder.notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markRead(Notification $notification): RedirectResponse
    {
        abort_unless($notification->recipient_id === Auth::id(), 403);
        $notification->markRead();

        if ($notification->deep_link) {
            return redirect($notification->deep_link);
        }
        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        Notification::where('recipient_id', Auth::id())
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('status', '모든 알림을 읽음 처리했습니다.');
    }

    public function destroy(Notification $notification): RedirectResponse
    {
        abort_unless($notification->recipient_id === Auth::id(), 403);
        $notification->delete();
        return back()->with('success', '알림을 삭제했습니다.');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $scope = $request->string('scope')->toString();   // 'read' | 'all'
        $q = Notification::where('recipient_id', Auth::id());
        if ($scope === 'read') {
            $q->where('is_read', true);
            $msg = '읽은 알림을 모두 삭제했습니다.';
        } else {
            $msg = '모든 알림을 삭제했습니다.';
        }
        $q->delete();
        return back()->with('success', $msg);
    }

    public function unreadCountJson(): JsonResponse
    {
        $count = Notification::where('recipient_id', Auth::id())->unread()->count();
        return response()->json(['count' => $count]);
    }
}
