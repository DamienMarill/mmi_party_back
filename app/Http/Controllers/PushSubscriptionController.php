<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function vapidPublicKey(): JsonResponse
    {
        return response()->json([
            'publicKey' => config('webpush.public_key'),
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        \Illuminate\Support\Facades\Log::info('Subscribe call', [
            'user_id' => auth()->id(),
            'endpoint' => $request->endpoint,
            'keys' => $request->keys,
        ]);

        $sub = PushSubscription::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'endpoint' => $validated['endpoint'],
            ],
            [
                'p256dh_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
            ]
        );

        return response()->json(['success' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        PushSubscription::where('user_id', auth()->id())
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json(['success' => true]);
    }

    public function status(): JsonResponse
    {
        $hasSubscription = PushSubscription::where('user_id', auth()->id())->exists();

        return response()->json([
            'subscribed' => $hasSubscription,
        ]);
    }
}
