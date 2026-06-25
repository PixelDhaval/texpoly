<?php

namespace App\Events;

use App\Models\ConversationParticipant;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];

        ConversationParticipant::where('conversation_id', $this->message->conversation_id)
            ->where('user_id', '!=', $this->message->sender_id)
            ->pluck('user_id')
            ->each(function (int $userId) use (&$channels) {
                $channels[] = new PrivateChannel('user.' . $userId);
            });

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id'              => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sender_id'       => $this->message->sender_id,
                'sender_name'     => $this->message->sender->name,
                'message'         => $this->message->message,
                'has_attachment'  => $this->message->hasAttachment(),
                'attachment_name' => $this->message->attachment_name,
                'attachment_size' => $this->message->attachment_size,
                'created_at'      => $this->message->created_at->toISOString(),
            ],
        ];
    }
}
