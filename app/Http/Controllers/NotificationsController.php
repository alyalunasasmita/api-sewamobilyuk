<?php

namespace App\Http\Controllers;

use App\Models\Notifications;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('user');

        return Notification::where('user_id', $user->id)
            ->latest()
            ->get();
    }
    
    public function read($id)
    {
        $notif = Notification::findOrFail($id);
        $notif->update([
            'is_read' => true
        ]);
        return response()->json([
            'message' => 'Notifikasi dibaca'
        ]);
    }

    public function unreadCount(Request $request)
    {
        $user = $request->attributes->get('user');

        return response()->json([
            'count' => Notification::where(
                'user_id',
                $user->id
            )->where('is_read', false)
            ->count()
        ]);
    }
}
