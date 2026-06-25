<?php

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Livewire\Chat\ConversationView;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageRead as MessageReadModel;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(fn () => Event::fake());

it('marks messages as read when conversation is opened', function () {
    $sender = User::factory()->create();
    $reader = User::factory()->create();

    $conversation = Conversation::create(['type' => 'private', 'created_by' => $sender->id]);
    ConversationParticipant::insert([
        ['conversation_id' => $conversation->id, 'user_id' => $sender->id, 'joined_at' => now()],
        ['conversation_id' => $conversation->id, 'user_id' => $reader->id, 'joined_at' => now()],
    ]);

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id'       => $sender->id,
        'message'         => 'Read this',
    ]);

    \Livewire\Livewire::actingAs($reader)
        ->test(ConversationView::class)
        ->call('selectConversation', $conversation->id);

    expect(
        MessageReadModel::where('message_id', $message->id)->where('user_id', $reader->id)->exists()
    )->toBeTrue();

    Event::assertDispatched(MessageRead::class);
});

it('updates last_read_message_id for the participant', function () {
    $sender = User::factory()->create();
    $reader = User::factory()->create();

    $conversation = Conversation::create(['type' => 'private', 'created_by' => $sender->id]);
    ConversationParticipant::insert([
        ['conversation_id' => $conversation->id, 'user_id' => $sender->id, 'joined_at' => now()],
        ['conversation_id' => $conversation->id, 'user_id' => $reader->id, 'joined_at' => now()],
    ]);

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id'       => $sender->id,
        'message'         => 'Hello',
    ]);

    \Livewire\Livewire::actingAs($reader)
        ->test(ConversationView::class)
        ->call('selectConversation', $conversation->id);

    $participant = ConversationParticipant::where('conversation_id', $conversation->id)
        ->where('user_id', $reader->id)
        ->first();

    expect($participant->last_read_message_id)->toBe($message->id);
});

it('does not create duplicate read records', function () {
    $sender = User::factory()->create();
    $reader = User::factory()->create();

    $conversation = Conversation::create(['type' => 'private', 'created_by' => $sender->id]);
    ConversationParticipant::insert([
        ['conversation_id' => $conversation->id, 'user_id' => $sender->id, 'joined_at' => now()],
        ['conversation_id' => $conversation->id, 'user_id' => $reader->id, 'joined_at' => now()],
    ]);

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id'       => $sender->id,
        'message'         => 'Hello',
    ]);

    $component = \Livewire\Livewire::actingAs($reader)->test(ConversationView::class);
    $component->call('selectConversation', $conversation->id);
    $component->call('markMessagesRead');

    expect(MessageReadModel::where('message_id', $message->id)->where('user_id', $reader->id)->count())->toBe(1);
});
