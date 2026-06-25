<?php

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(fn () => Event::fake());

it('redirects guests away from chat', function () {
    $this->get(route('chat.index'))->assertRedirect(route('login'));
});

it('allows authenticated users to access chat', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('chat.index'))->assertOk();
});

it('blocks non-participants from downloading attachments', function () {
    $owner    = User::factory()->create();
    $outsider = User::factory()->create();

    $conversation = Conversation::create(['type' => 'private', 'created_by' => $owner->id]);
    ConversationParticipant::create(['conversation_id' => $conversation->id, 'user_id' => $owner->id, 'joined_at' => now()]);

    $message = $conversation->messages()->create([
        'sender_id'       => $owner->id,
        'attachment_path' => 'chat-attachments/1/test.pdf',
        'attachment_name' => 'test.pdf',
        'attachment_size' => 1024,
    ]);

    $this->actingAs($outsider)
        ->get(route('chat.attachments.download', $message))
        ->assertForbidden();
});

it('blocks non-participants from viewing a conversation', function () {
    $owner    = User::factory()->create();
    $outsider = User::factory()->create();

    $conversation = Conversation::create(['type' => 'private', 'created_by' => $owner->id]);
    ConversationParticipant::create(['conversation_id' => $conversation->id, 'user_id' => $owner->id, 'joined_at' => now()]);

    \Livewire\Livewire::actingAs($outsider)
        ->test(\App\Livewire\Chat\ConversationView::class)
        ->call('selectConversation', $conversation->id)
        ->assertForbidden();
});

it('allows a participant to view their conversation', function () {
    $user = User::factory()->create();

    $conversation = Conversation::create(['type' => 'private', 'created_by' => $user->id]);
    ConversationParticipant::create(['conversation_id' => $conversation->id, 'user_id' => $user->id, 'joined_at' => now()]);

    \Livewire\Livewire::actingAs($user)
        ->test(\App\Livewire\Chat\ConversationView::class)
        ->call('selectConversation', $conversation->id)
        ->assertHasNoErrors();
});
