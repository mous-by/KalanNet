<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = AppNotification::where('user_id', $request->user()->idUtilisateur);

        if ($since = $request->input('since')) {
            // Sync mode: everything touched since a given timestamp (read or not),
            // so an offline client can reconcile its local copy.
            $query->where('updated_at', '>', $since);
        } else {
            $query->whereNull('read_at');
        }

        $notifications = $query->orderByDesc('id')->limit(100)->get();

        return NotificationResource::collection($notifications);
    }

    public function unreadCount(Request $request)
    {
        $count = AppNotification::where('user_id', $request->user()->idUtilisateur)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markAsRead(Request $request, int $id)
    {
        $notification = AppNotification::where('user_id', $request->user()->idUtilisateur)->find($id);

        if ($notification) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }
}
