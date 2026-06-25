<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, int $conversationId) {
    return $user->conversationParticipants()
        ->where('conversation_id', $conversationId)
        ->exists();
});

Broadcast::channel('user.{userId}', function ($user, int $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('online', function ($user) {
    return [
        'id'         => $user->id,
        'name'       => $user->name,
        'last_seen_at' => $user->last_seen_at?->toISOString(),
    ];
});
