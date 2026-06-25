<?php

namespace App\Livewire\Chat;

use App\Events\MessageRead as MessageReadEvent;
use App\Events\MessageSent as MessageSentEvent;
use App\Events\UserStoppedTyping;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageRead;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ConversationView extends Component
{
    use WithFileUploads;

    public ?int $conversationId = null;
    public ?Conversation $conversation = null;
    public array $messages = [];
    public ?int $oldestMessageId = null;
    public bool $hasMoreMessages = false;
    public string $message = '';
    public $attachment = null;
    public array $typingUsers = [];

    #[On('conversation-selected')]
    public function selectConversation(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        Gate::authorize('view', $conversation);

        $this->conversationId = $conversationId;
        $this->conversation = $conversation;
        $this->messages = [];
        $this->oldestMessageId = null;
        $this->hasMoreMessages = false;
        $this->message = '';
        $this->attachment = null;
        $this->typingUsers = [];

        $this->loadMessages();
        $this->markAsRead();

        $this->dispatch('chat-channel-changed', conversationId: $conversationId);
    }

    public function loadMoreMessages(): void
    {
        if ($this->conversationId) {
            $this->loadMessages();
        }
    }

    public function updatedMessage(): void
    {
        if ($this->conversationId && $this->message !== '') {
            event(new UserTyping($this->conversationId, Auth::user()));
        }
    }

    public function stopTyping(): void
    {
        if ($this->conversationId) {
            event(new UserStoppedTyping($this->conversationId, Auth::user()));
        }
    }

    public function sendMessage(): void
    {
        if (! $this->conversationId) {
            return;
        }

        $conversation = Conversation::findOrFail($this->conversationId);
        Gate::authorize('sendMessage', $conversation);

        $this->validate([
            'message'    => 'required_without:attachment|nullable|string|max:5000',
            'attachment' => 'nullable|file|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png|max:20480',
        ]);

        $msg = DB::transaction(function () use ($conversation) {
            $attachmentPath = null;
            $attachmentName = null;
            $attachmentSize = null;

            if ($this->attachment) {
                Gate::authorize('uploadAttachment', $conversation);

                $attachmentName = $this->attachment->getClientOriginalName();
                $attachmentSize = $this->attachment->getSize();
                $ext            = $this->attachment->getClientOriginalExtension();
                $attachmentPath = $this->attachment->storeAs(
                    'chat-attachments/' . $this->conversationId,
                    uniqid('', true) . '.' . $ext,
                    'local'
                );
            }

            $msg = Message::create([
                'conversation_id' => $this->conversationId,
                'sender_id'       => Auth::id(),
                'message'         => $this->message ?: null,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'attachment_size' => $attachmentSize,
            ]);

            $msg->load('sender:id,name');
            $conversation->touch();

            MessageRead::insertOrIgnore([[
                'message_id' => $msg->id,
                'user_id'    => Auth::id(),
                'read_at'    => now(),
            ]]);

            ConversationParticipant::where('conversation_id', $this->conversationId)
                ->where('user_id', Auth::id())
                ->update(['last_read_message_id' => $msg->id]);

            event(new MessageSentEvent($msg));

            return $msg;
        });

        $this->messages[] = $this->formatMessage($msg);
        $this->message     = '';
        $this->attachment  = null;

        $this->dispatch('conversation-updated', conversationId: $this->conversationId);
        $this->dispatch('scroll-to-bottom');
        $this->dispatch('unread-count-updated');
    }

    public function receiveMessage(array $message): void
    {
        $exists = collect($this->messages)->contains('id', $message['id']);

        if (! $exists) {
            $msg = Message::with('sender:id,name')->find($message['id']);
            if ($msg) {
                $this->messages[] = $this->formatMessage($msg);
            }
            $this->markAsRead();
            $this->dispatch('scroll-to-bottom');
            $this->dispatch('conversation-updated', conversationId: $this->conversationId);
        }
    }

    public function markMessagesRead(): void
    {
        $this->markAsRead();
    }

    public function userIsTyping(int $userId, string $userName): void
    {
        if ($userId !== Auth::id()) {
            $this->typingUsers[$userId] = $userName;
        }
    }

    public function userStoppedTyping(int $userId): void
    {
        unset($this->typingUsers[$userId]);
    }

    public function render()
    {
        return view('livewire.chat.conversation-view');
    }

    private function loadMessages(): void
    {
        $query = Message::with(['sender:id,name'])
            ->where('conversation_id', $this->conversationId)
            ->orderByDesc('id')
            ->limit(50);

        if ($this->oldestMessageId) {
            $query->where('id', '<', $this->oldestMessageId);
        }

        $fetched = $query->get()->reverse()->values();

        if ($fetched->isNotEmpty()) {
            $this->oldestMessageId  = $fetched->first()->id;
            $this->hasMoreMessages  = $fetched->count() === 50;
            $formatted              = $fetched->map(fn ($m) => $this->formatMessage($m))->all();
            $this->messages         = array_merge($formatted, $this->messages);
        } else {
            $this->hasMoreMessages = false;
        }
    }

    private function markAsRead(): void
    {
        if (! $this->conversationId || empty($this->messages)) {
            return;
        }

        $userId     = Auth::id();
        $lastMsg    = collect($this->messages)->last();

        if (! $lastMsg) {
            return;
        }

        $unreadIds = Message::where('conversation_id', $this->conversationId)
            ->where('sender_id', '!=', $userId)
            ->whereNotIn('id', MessageRead::where('user_id', $userId)->pluck('message_id'))
            ->pluck('id');

        if ($unreadIds->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($unreadIds, $userId, $lastMsg) {
            MessageRead::insertOrIgnore(
                $unreadIds->map(fn ($id) => [
                    'message_id' => $id,
                    'user_id'    => $userId,
                    'read_at'    => now(),
                ])->all()
            );

            ConversationParticipant::where('conversation_id', $this->conversationId)
                ->where('user_id', $userId)
                ->update(['last_read_message_id' => $lastMsg['id']]);
        });

        event(new MessageReadEvent($this->conversationId, Auth::user(), $lastMsg['id']));

        $this->dispatch('unread-count-updated');
    }

    private function formatMessage(Message $message): array
    {
        return [
            'id'              => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id'       => $message->sender_id,
            'sender_name'     => $message->sender->name,
            'message'         => $message->message,
            'has_attachment'  => $message->hasAttachment(),
            'attachment_name' => $message->attachment_name,
            'attachment_size' => $message->formattedSize(),
            'created_at'      => $message->created_at->toISOString(),
            'is_mine'         => $message->sender_id === Auth::id(),
        ];
    }
}
