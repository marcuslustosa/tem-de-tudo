<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

    // --- Backward compatibility with legacy routes ---
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
            return response()->json(['success' => false, 'message' => 'Usuario nao autenticado.'], 401);
        }

        $user->fcm_token = $request->input('fcm_token');
        $user->save();

        return response()->json(['success' => true, 'message' => 'Token FCM atualizado com sucesso.']);
    }

    public function getNotificationSettings()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuario nao autenticado.'], 401);
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
            return response()->json(['success' => false, 'message' => 'Usuario nao autenticado.'], 401);
        }

        $user->fill($validated);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Configuracoes de notificacao atualizadas.',
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
                'message' => 'Nenhum usuario alvo para envio.',
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
            'message' => 'Notificacao enviada.',
            'data' => ['sent' => count($targetIds)],
        ]);
    }

    public function testNotification(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuario nao autenticado.'], 401);
        }

        self::createForUsers(
            [$user->id],
            'Notificacao de teste',
            'Este e um teste de notificacao interna.',
            'info',
            ['origin' => 'test']
        );

        return response()->json(['success' => true, 'message' => 'Notificacao de teste enviada.']);
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
        // Internal notifications are persisted synchronously.
        return response()->json([
            'success' => true,
            'message' => 'Fila de notificacoes processada (sem itens pendentes).',
        ]);
    }

    public function listTickets(Request $request)
    {
        $status = Str::of((string) $request->input('status', 'todos'))->lower()->toString();
        if (!in_array($status, ['todos', 'pendente', 'resolvido'], true)) {
            $status = 'todos';
        }

        $perPage = max(1, min((int) $request->input('per_page', 20), 100));
        $search = trim((string) $request->input('q', ''));

        $query = Notification::query()
            ->with(['user:id,name,email,perfil'])
            ->where('type', 'ticket')
            ->orderByDesc('created_at');

        if ($status === 'pendente') {
            $query->whereNull('read_at');
        }

        if ($status === 'resolvido') {
            $query->whereNotNull('read_at');
        }

        if ($search !== '') {
            $term = '%' . mb_strtolower($search) . '%';
            $query->where(function ($sub) use ($term) {
                $sub->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(message) LIKE ?', [$term])
                    ->orWhereHas('user', function ($userQuery) use ($term) {
                        $userQuery->whereRaw('LOWER(name) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$term]);
                    });
            });
        }

        $tickets = $query->paginate($perPage);
        $rows = collect($tickets->items())
            ->map(fn (Notification $notification) => $this->formatTicket($notification))
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $rows,
                'total' => $tickets->total(),
                'current_page' => $tickets->currentPage(),
                'per_page' => $tickets->perPage(),
                'last_page' => $tickets->lastPage(),
            ],
        ]);
    }

    public function ticketStats()
    {
        $baseQuery = Notification::query()->where('type', 'ticket');

        $total = (clone $baseQuery)->count();
        $pendentes = (clone $baseQuery)->whereNull('read_at')->count();
        $resolvidos = max(0, $total - $pendentes);

        $urgentes = (clone $baseQuery)
            ->whereNull('read_at')
            ->get()
            ->filter(fn (Notification $notification) => $this->ticketPriority($notification) === 'alta')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'pendentes' => $pendentes,
                'resolvidos' => $resolvidos,
                'urgentes' => $urgentes,
            ],
        ]);
    }

    public function createTicket(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'user_id' => 'nullable|integer|exists:users,id',
            'priority' => 'nullable|string|max:20',
            'category' => 'nullable|string|max:50',
        ]);

        $targetUserId = (int) ($validated['user_id'] ?? Auth::id());
        $priority = $this->normalizePriority((string) ($validated['priority'] ?? 'media'));
        $category = trim((string) ($validated['category'] ?? 'suporte'));

        $ticket = Notification::create([
            'user_id' => $targetUserId,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'type' => 'ticket',
            'payload' => [
                'priority' => $priority,
                'category' => $category !== '' ? $category : 'suporte',
                'created_by' => Auth::id(),
                'created_by_name' => Auth::user()?->name,
            ],
            'read_at' => null,
        ]);

        $ticket->loadMissing('user:id,name,email,perfil');

        return response()->json([
            'success' => true,
            'message' => 'Ticket criado com sucesso.',
            'data' => $this->formatTicket($ticket),
        ], 201);
    }

    public function resolveTicket(Request $request, int $id)
    {
        $ticket = Notification::query()
            ->where('type', 'ticket')
            ->where('id', $id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket nao encontrado.',
            ], 404);
        }

        $payload = is_array($ticket->payload) ? $ticket->payload : [];
        $payload['resolved_by'] = Auth::id();
        $payload['resolved_by_name'] = Auth::user()?->name;
        $payload['resolved_at'] = now()->toIso8601String();
        $payload['resolution_note'] = trim((string) $request->input('note', ''));

        $ticket->payload = $payload;
        $ticket->read_at = now();
        $ticket->save();

        $ticket->loadMissing('user:id,name,email,perfil');

        return response()->json([
            'success' => true,
            'message' => 'Ticket resolvido com sucesso.',
            'data' => $this->formatTicket($ticket),
        ]);
    }

    public function reopenTicket(int $id)
    {
        $ticket = Notification::query()
            ->where('type', 'ticket')
            ->where('id', $id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket nao encontrado.',
            ], 404);
        }

        $payload = is_array($ticket->payload) ? $ticket->payload : [];
        $payload['reopened_by'] = Auth::id();
        $payload['reopened_by_name'] = Auth::user()?->name;
        $payload['reopened_at'] = now()->toIso8601String();

        $ticket->payload = $payload;
        $ticket->read_at = null;
        $ticket->save();

        $ticket->loadMissing('user:id,name,email,perfil');

        return response()->json([
            'success' => true,
            'message' => 'Ticket reaberto com sucesso.',
            'data' => $this->formatTicket($ticket),
        ]);
    }

    public function closeTicket(int $id)
    {
        $deleted = Notification::query()
            ->where('type', 'ticket')
            ->where('id', $id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket nao encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket encerrado com sucesso.',
        ]);
    }

    private function formatTicket(Notification $notification): array
    {
        $payload = is_array($notification->payload) ? $notification->payload : [];
        $resolvedAt = $payload['resolved_at'] ?? ($notification->read_at?->toIso8601String());

        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'type' => $notification->type,
            'status' => $notification->read_at ? 'resolvido' : 'pendente',
            'priority' => $this->ticketPriority($notification),
            'category' => trim((string) ($payload['category'] ?? $payload['categoria'] ?? 'suporte')),
            'payload' => $payload,
            'created_at' => $notification->created_at,
            'read_at' => $notification->read_at,
            'resolved_at' => $resolvedAt,
            'user' => $notification->user ? [
                'id' => $notification->user->id,
                'name' => $notification->user->name,
                'email' => $notification->user->email,
                'perfil' => $notification->user->perfil,
            ] : null,
        ];
    }

    private function ticketPriority(Notification $notification): string
    {
        $payload = is_array($notification->payload) ? $notification->payload : [];
        $raw = (string) ($payload['priority'] ?? $payload['prioridade'] ?? 'media');
        return $this->normalizePriority($raw);
    }

    private function normalizePriority(string $priority): string
    {
        $normalized = Str::of($priority)->lower()->trim()->toString();

        if (in_array($normalized, ['alta', 'high', 'urgente', 'critical', 'critico', 'critica'], true)) {
            return 'alta';
        }

        if (in_array($normalized, ['baixa', 'low'], true)) {
            return 'baixa';
        }

        return 'media';
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
