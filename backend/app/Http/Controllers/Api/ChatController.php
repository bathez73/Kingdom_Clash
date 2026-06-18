<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:255|min:1',
        ]);

        $user = $request->user();

        broadcast(new ChatMessageSent($user->id, $user->name, $validated['message']));

        return response()->json([
            'message' => 'Message envoyé',
        ]);
    }
}
