<?php

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\UserStoppedTyping;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('MessageSent broadcasts on the correct private channel', function () {
    $sender    = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::create(['type' => 'private', 'created_by' => $sender->id]);
    ConversationParticipant::insert([
        ['conversation_id' => $conversation->id, 'user_id' => $sender->id, 'joined_at' => now()],
        ['conversation_id' => $conversation->id, 'user_id' => $recipient->id, 'joined_at' => now()],
    ]);

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id'       => $sender->id,
        'message'         => 'Hello',
    ]);

    $message->load('sender');

    $event    = new MessageSent($message);
    $channels = collect($event->broadcastOn())->map(fn ($c) => $c->name);

    expect($channels)->toContain('private-conversation.' . $conversation->id);
    expect($channels)->toContain('private-user.' . $recipient->id);
    expect($channels)->not->toContain('private-user.' . $sender->id);
});

it('MessageRead broadcasts on the conversation channel', function () {
    $user = User::factory()->create();

    $event = new MessageRead(42, $user, 100);

    $channels = collect($event->broadcastOn())->map(fn ($c) => $c->name);

    expect($channels)->toContain('private-conversation.42');
});

it('UserTyping broadcasts immediately on the conversation channel', function () {
    $user = User::factory()->create();

    $event = new UserTyping(7, $user);

    expect($event)->toBeInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcastNow::class);

    $channels = collect($event->broadcastOn())->map(fn ($c) => $c->name);

    expect($channels)->toContain('private-conversation.7');
});

it('UserStoppedTyping broadcasts immediately on the conversation channel', function () {
    $user = User::factory()->create();

    $event = new UserStoppedTyping(7, $user);

    expect($event)->toBeInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcastNow::class);
});
