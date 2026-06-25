<?php

namespace App\Livewire\Chat;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->refreshCount();
    }

    #[On('unread-count-updated')]
    public function refreshCount(): void
    {
        $userId = Auth::id();

        $this->unreadCount = DB::table('messages as m')
            ->join('conversation_participants as cp', function ($join) use ($userId) {
                $join->on('cp.conversation_id', '=', 'm.conversation_id')
                    ->where('cp.user_id', '=', $userId);
            })
            ->where('m.sender_id', '!=', $userId)
            ->where(function ($q) {
                $q->whereNull('cp.last_read_message_id')
                    ->orWhereColumn('m.id', '>', 'cp.last_read_message_id');
            })
            ->count();
    }

    public function render()
    {
        return view('livewire.chat.notification-bell');
    }
}
