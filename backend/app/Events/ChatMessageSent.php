<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public int $user_id;
    public string $username;
    public string $message;
    public string $timestamp;

    public function __construct(int $user_id, string $username, string $message)
    {
        $this->user_id = $user_id;
        $this->username = $username;
        $this->message = $message;
        $this->timestamp = now()->toIso8601String();
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('global-chat'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message_sent';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user_id,
            'username' => $this->username,
            'message' => $this->message,
            'timestamp' => $this->timestamp,
        ];
    }
}
