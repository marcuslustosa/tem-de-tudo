<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    public function markRead(Request $request, $id = null)
    {
        $query = Notification::where('user_id', Auth::id());
        if ($id) {
            $query->where('id', $id);
        }
        $query->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        Notification::where('user_id', Auth::id())->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public static function createForUsers(array $userIds, string $title, string $message, string $type = null, array $payload = []): void
    {
        $rows = [];
        foreach ($userIds as $uid) {
            $rows[] = [
                'user_id' => $uid,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'payload' => json_encode($payload),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if ($rows) {
            Notification::insert($rows);
        }
    }
}
