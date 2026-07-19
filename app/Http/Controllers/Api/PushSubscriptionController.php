<?php
// app/Http/Controllers/Api/PushSubscriptionController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'endpoint' => 'required|string',
                'keys' => 'required|array',
                'keys.p256dh' => 'required|string',
                'keys.auth' => 'required|string',
            ]);
            
            // Метод из трейта HasPushSubscriptions
            $user->updatePushSubscription(
                $validated['endpoint'],
                $validated['keys']['p256dh'],
                $validated['keys']['auth']
            );
            
            Log::info('Push subscription stored', ['user_id' => $user->id]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Store push subscription error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function destroy(Request $request)
    {
        try {
            $user = $request->user();
            $endpoint = $request->input('endpoint');
            
            if ($endpoint) {
                $user->deletePushSubscription($endpoint);
            }
            
            Log::info('Push subscription deleted', ['user_id' => $user->id]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Delete push subscription error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}