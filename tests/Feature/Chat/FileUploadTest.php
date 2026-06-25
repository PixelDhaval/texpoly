<?php

use App\Events\MessageSent;
use App\Livewire\Chat\ConversationView;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(fn () => Event::fake());

it('allows a participant to upload a supported file', function () {
    Storage::fake('local');

    $sender    = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::create(['type' => 'private', 'created_by' => $sender->id]);
    ConversationParticipant::insert([
        ['conversation_id' => $conversation->id, 'user_id' => $sender->id, 'joined_at' => now()],
        ['conversation_id' => $conversation->id, 'user_id' => $recipient->id, 'joined_at' => now()],
    ]);

    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    \Livewire\Livewire::actingAs($sender)
        ->test(ConversationView::class)
        ->call('selectConversation', $conversation->id)
        ->set('attachment', $file)
        ->call('sendMessage')
        ->assertHasNoErrors();

    Event::assertDispatched(MessageSent::class);
});

it('rejects a disallowed file type', function () {
    Storage::fake('local');

    $sender = User::factory()->create();

    $conversation = Conversation::create(['type' => 'private', 'created_by' => $sender->id]);
    ConversationParticipant::create(['conversation_id' => $conversation->id, 'user_id' => $sender->id, 'joined_at' => now()]);

    $file = UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream');

    \Livewire\Livewire::actingAs($sender)
        ->test(ConversationView::class)
        ->call('selectConversation', $conversation->id)
        ->set('attachment', $file)
        ->call('sendMessage')
        ->assertHasErrors(['attachment']);
});

it('rejects files exceeding 20 MB via validation rule', function () {
    // Test the max:20480 rule directly — Livewire temp-uploads lose fake size in getSize()
    $oversized = UploadedFile::fake()->create('huge.pdf', 21 * 1024, 'application/pdf');
    $within    = UploadedFile::fake()->create('small.pdf', 100, 'application/pdf');

    $rule = 'nullable|file|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png|max:20480';

    $oversizedValidator = \Illuminate\Support\Facades\Validator::make(
        ['attachment' => $oversized], ['attachment' => $rule]
    );

    $withinValidator = \Illuminate\Support\Facades\Validator::make(
        ['attachment' => $within], ['attachment' => $rule]
    );

    expect($oversizedValidator->fails())->toBeTrue();
    expect($oversizedValidator->errors()->has('attachment'))->toBeTrue();
    expect($withinValidator->passes())->toBeTrue();
});
