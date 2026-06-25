<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ConversationList extends Component
{
    public string $search = '';
    public int $perPage = 20;
    public ?int $selectedConversationId = null;

    public function updatedSearch(): void
    {
        $this->perPage = 20;
    }

    public function loadMore(): void
    {
        $this->perPage += 20;
    }

    public function selectConversation(int $id): void
    {
        $this->selectedConversationId = $id;
        $this->dispatch('conversation-selected', conversationId: $id);
    }

    #[On('conversation-selected')]
    public function setActiveConversation(int $conversationId): void
    {
        $this->selectedConversationId = $conversationId;
    }

    #[On('conversation-created')]
    #[On('conversation-updated')]
    public function refresh(): void {}

    #[On('unread-count-updated')]
    public function refreshUnread(): void {}

    public function render()
    {
        $userId = Auth::id();

        $unreadCounts = DB::table('messages as m')
            ->join('conversation_participants as cp', function ($join) use ($userId) {
                $join->on('cp.conversation_id', '=', 'm.conversation_id')
                    ->where('cp.user_id', '=', $userId);
            })
            ->where('m.sender_id', '!=', $userId)
            ->where(function ($q) {
                $q->whereNull('cp.last_read_message_id')
                    ->orWhereColumn('m.id', '>', 'cp.last_read_message_id');
            })
            ->groupBy('m.conversation_id')
            ->select('m.conversation_id', DB::raw('COUNT(*) as count'))
            ->pluck('count', 'conversation_id');

        $conversations = Conversation::whereHas('participants', fn ($q) => $q->where('users.id', $userId))
            ->with([
                'latestMessage',
                'latestMessage.sender:id,name',
                'participants' => fn ($q) => $q->where('users.id', '!=', $userId)->select('users.id', 'users.name', 'users.last_seen_at'),
            ])
            ->when($this->search, function ($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('name', 'ilike', '%' . $this->search . '%')
                        ->orWhereHas('messages', fn ($mq) => $mq->where('message', 'ilike', '%' . $this->search . '%'))
                        ->orWhereHas('participants', fn ($pq) => $pq
                            ->where('users.id', '!=', $userId)
                            ->where('users.name', 'ilike', '%' . $this->search . '%')
                        );
                });
            })
            ->orderByDesc('updated_at')
            ->take($this->perPage)
            ->get();

        $hasMore = Conversation::whereHas('participants', fn ($q) => $q->where('users.id', $userId))
            ->count() > $this->perPage;

        return view('livewire.chat.conversation-list', [
            'conversations'  => $conversations,
            'unreadCounts'   => $unreadCounts,
            'hasMore'        => $hasMore,
            'currentUserId'  => $userId,
        ]);
    }
}
