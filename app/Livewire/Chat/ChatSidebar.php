<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ChatSidebar extends Component
{
    public bool $showNewModal = false;
    public string $userSearch = '';
    public array $userResults = [];
    public array $selectedUsers = [];
    public string $groupName = '';
    public string $conversationType = 'private';

    public function updatedUserSearch(): void
    {
        if (strlen($this->userSearch) < 1) {
            $this->userResults = [];

            return;
        }

        $this->userResults = User::where('id', '!=', Auth::id())
            ->where('name', 'ilike', '%' . $this->userSearch . '%')
            ->select('id', 'name', 'email', 'last_seen_at')
            ->limit(10)
            ->get()
            ->map(fn ($u) => [
                'id'        => $u->id,
                'name'      => $u->name,
                'email'     => $u->email,
                'is_online' => $u->isOnline(),
            ])
            ->toArray();
    }

    public function addUser(int $userId, string $userName): void
    {
        if (! collect($this->selectedUsers)->contains('id', $userId)) {
            $this->selectedUsers[] = ['id' => $userId, 'name' => $userName];
            $this->conversationType = count($this->selectedUsers) > 1 ? 'group' : 'private';
        }

        $this->userSearch = '';
        $this->userResults = [];
    }

    public function removeUser(int $userId): void
    {
        $this->selectedUsers = collect($this->selectedUsers)
            ->filter(fn ($u) => $u['id'] !== $userId)
            ->values()
            ->all();

        $this->conversationType = count($this->selectedUsers) > 1 ? 'group' : 'private';
    }

    public function createConversation(): void
    {
        $this->validate([
            'selectedUsers' => 'required|array|min:1',
            'groupName'     => 'required_if:conversationType,group|nullable|string|max:255',
        ]);

        $userIds = collect($this->selectedUsers)->pluck('id');

        if ($this->conversationType === 'private' && $userIds->count() === 1) {
            $otherUserId = $userIds->first();

            $existing = Conversation::where('type', 'private')
                ->whereHas('participants', fn ($q) => $q->where('users.id', Auth::id()))
                ->whereHas('participants', fn ($q) => $q->where('users.id', $otherUserId))
                ->whereDoesntHave('participants', fn ($q) => $q->whereNotIn('users.id', [Auth::id(), $otherUserId]))
                ->first();

            if ($existing) {
                $this->resetModal();
                $this->dispatch('conversation-selected', conversationId: $existing->id);

                return;
            }
        }

        $conversation = DB::transaction(function () use ($userIds) {
            $conv = Conversation::create([
                'type'       => $this->conversationType,
                'name'       => $this->conversationType === 'group' ? $this->groupName : null,
                'created_by' => Auth::id(),
            ]);

            $allIds = $userIds->push(Auth::id())->unique()->values();

            $conv->participantRecords()->createMany(
                $allIds->map(fn ($id) => [
                    'user_id'   => $id,
                    'joined_at' => now(),
                ])->all()
            );

            return $conv;
        });

        $this->resetModal();
        $this->dispatch('conversation-created');
        $this->dispatch('conversation-selected', conversationId: $conversation->id);
    }

    private function resetModal(): void
    {
        $this->showNewModal = false;
        $this->selectedUsers = [];
        $this->groupName = '';
        $this->userSearch = '';
        $this->userResults = [];
        $this->conversationType = 'private';
    }

    public function render()
    {
        return view('livewire.chat.chat-sidebar');
    }
}
