<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\SendWebPushJob;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PushSubscriptionController extends Controller
{
    public function publicKey()
    {
        return response()->json([
            'vapidPublicKey' => config('services.webpush.public_key') ?? env('VAPID_PUBLIC_KEY'),
        ]);
    }

    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $userId = Auth::id();
        $endpoint = $request->input('endpoint');

        $sub = PushSubscription::updateOrCreate(
            ['endpoint' => $endpoint],
            [
                'user_id' => $userId,
                'p256dh' => $request->input('keys.p256dh'),
                'auth' => $request->input('keys.auth'),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]
        );

        return response()->json(['success' => true, 'data' => $sub]);
    }

    public function unsubscribe(Request $request)
    {
        $endpoint = $request->input('endpoint');
        if (!$endpoint) {
            return response()->json(['success' => false, 'message' => 'Endpoint obrigatório'], 422);
        }
        PushSubscription::where('endpoint', $endpoint)->delete();
        return response()->json(['success' => true]);
    }

    public function test(Request $request)
    {
        $user = Auth::user();
        SendWebPushJob::dispatch(
            title: 'Teste de Push',
            body: 'Push enviado com sucesso.',
            data: ['type' => 'test'],
            userIds: [$user?->id]
        );

        return response()->json(['success' => true, 'message' => 'Push de teste enfileirado']);
    }
}
