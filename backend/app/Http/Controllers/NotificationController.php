<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $notifications->items(),
                'total' => $notifications->total(),
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'last_page' => $notifications->lastPage()
            ]
        ]);
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

    // --- Compatibilidade com rotas legadas ---
    public function getUserNotifications(Request $request)
    {
        return $this->index($request);
    }

    public function markAsRead(Request $request, $id)
    {
        return $this->markRead($request, $id);
    }

    public function markAllAsRead(Request $request)
    {
        return $this->markRead($request, null);
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'nullable|string|max:1024',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuário não autenticado.'], 401);
        }

        $user->fcm_token = $request->input('fcm_token');
        $user->save();

        return response()->json(['success' => true, 'message' => 'Token FCM atualizado com sucesso.']);
    }

    public function getNotificationSettings()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuário não autenticado.'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'email_notifications' => (bool) $user->email_notifications,
                'points_notifications' => (bool) $user->points_notifications,
                'security_notifications' => (bool) $user->security_notifications,
                'promotional_notifications' => (bool) $user->promotional_notifications,
            ],
        ]);
    }

    public function updateNotificationSettings(Request $request)
    {
        $validated = $request->validate([
            'email_notifications' => 'sometimes|boolean',
            'points_notifications' => 'sometimes|boolean',
            'security_notifications' => 'sometimes|boolean',
            'promotional_notifications' => 'sometimes|boolean',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuário não autenticado.'], 401);
        }

        $user->fill($validated);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Configurações de notificação atualizadas.',
            'data' => [
                'email_notifications' => (bool) $user->email_notifications,
                'points_notifications' => (bool) $user->points_notifications,
                'security_notifications' => (bool) $user->security_notifications,
                'promotional_notifications' => (bool) $user->promotional_notifications,
            ],
        ]);
    }

    public function sendBroadcast(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => 'nullable|string|max:50',
            'payload' => 'nullable|array',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $targetIds = $validated['user_ids'] ?? User::where('status', 'ativo')->pluck('id')->all();
        if (!$targetIds) {
            return response()->json([
                'success' => true,
                'message' => 'Nenhum usuário alvo para envio.',
                'data' => ['sent' => 0],
            ]);
        }

        self::createForUsers(
            $targetIds,
            $validated['title'],
            $validated['message'],
            $validated['type'] ?? null,
            $validated['payload'] ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Notificação enviada.',
            'data' => ['sent' => count($targetIds)],
        ]);
    }

    public function testNotification(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuário não autenticado.'], 401);
        }

        self::createForUsers(
            [$user->id],
            'Notificação de teste',
            'Este é um teste de notificação interna.',
            'info',
            ['origin' => 'test']
        );

        return response()->json(['success' => true, 'message' => 'Notificação de teste enviada.']);
    }

    public function getStats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total' => Notification::count(),
                'unread' => Notification::whereNull('read_at')->count(),
                'today' => Notification::whereDate('created_at', now()->toDateString())->count(),
            ],
        ]);
    }

    public function processQueue()
    {
        // Notificações internas são persistidas de forma síncrona no momento.
        return response()->json([
            'success' => true,
            'message' => 'Fila de notificações processada (sem itens pendentes).',
        ]);
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
