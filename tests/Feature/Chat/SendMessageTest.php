<?php

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Livewire\Chat\ConversationView;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(fn () => Event::fake());

it('allows a participant to send a text message', function () {
    $sender    = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::create(['type' => 'private', 'created_by' => $sender->id]);
    ConversationParticipant::insert([
        ['conversation_id' => $conversation->id, 'user_id' => $sender->id, 'joined_at' => now()],
        ['conversation_id' => $conversation->id, 'user_id' => $recipient->id, 'joined_at' => now()],
    ]);

    \Livewire\Livewire::actingAs($sender)
        ->test(ConversationView::class)
        ->call('selectConversation', $conversation->id)
        ->set('message', 'Hello world')
        ->call('sendMessage')
        ->assertHasNoErrors();

    expect(Message::where('conversation_id', $conversation->id)
        ->where('sender_id', $sender->id)
        ->where('message', 'Hello world')
        ->exists()
    )->toBeTrue();

    Event::assertDispatched(MessageSent::class);
});

it('prevents sending an empty message without attachment', function () {
    $sender = User::factory()->create();

    $conversation = Conversation::create(['type' => 'private', 'created_by' => $sender->id]);
    ConversationParticipant::create(['conversation_id' => $conversation->id, 'user_id' => $sender->id, 'joined_at' => now()]);

    \Livewire\Livewire::actingAs($sender)
        ->test(ConversationView::class)
        ->call('selectConversation', $conversation->id)
        ->set('message', '')
        ->call('sendMessage')
        ->assertHasErrors(['message']);
});

it('prevents a non-participant from viewing a conversation', function () {
    $owner    = User::factory()->create();
    $outsider = User::factory()->create();

    $conversation = Conversation::create(['type' => 'private', 'created_by' => $owner->id]);
    ConversationParticipant::create(['conversation_id' => $conversation->id, 'user_id' => $owner->id, 'joined_at' => now()]);

    \Livewire\Livewire::actingAs($outsider)
        ->test(ConversationView::class)
        ->call('selectConversation', $conversation->id)
        ->assertForbidden();
});
