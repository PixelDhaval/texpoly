<div class="flex flex-col">
<div class="p-3 border-b border-gray-200">
    <input
        wire:model.live.debounce.400ms="search"
        type="text"
        placeholder="Search conversations..."
        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
    >
</div>

<div class="divide-y divide-gray-100">
    @forelse($conversations as $conversation)
        @php
            $unread = $unreadCounts->get($conversation->id, 0);
            $isSelected = $selectedConversationId === $conversation->id;
            $otherParticipants = $conversation->participants;
            $displayName = $conversation->type === 'group'
                ? ($conversation->name ?? 'Group Chat')
                : ($otherParticipants->first()?->name ?? 'Unknown');
            $isOnline = $conversation->type === 'private' && $otherParticipants->first()?->isOnline();
        @endphp
        <button
            wire:click="selectConversation({{ $conversation->id }})"
            wire:key="conv-{{ $conversation->id }}"
            class="w-full text-left px-4 py-3 flex items-start gap-3 hover:bg-gray-50 transition-colors {{ $isSelected ? 'bg-blue-50 border-l-4 border-l-blue-500' : '' }}"
        >
            <div class="relative flex-shrink-0">
                <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold text-sm">
                    {{ strtoupper(substr($displayName, 0, 1)) }}
                </div>
                @if($isOnline)
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 border-2 border-white rounded-full"></span>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-baseline">
                    <span class="font-medium text-sm text-gray-900 truncate">{{ $displayName }}</span>
                    @if($conversation->latestMessage)
                        <span class="text-xs text-gray-400 ml-1 flex-shrink-0">
                            {{ $conversation->latestMessage->created_at->diffForHumans(short: true) }}
                        </span>
                    @endif
                </div>
                <div class="flex justify-between items-center mt-0.5">
                    <p class="text-xs text-gray-500 truncate">
                        @if($conversation->latestMessage)
                            @if($conversation->latestMessage->sender_id === $currentUserId)
                                <span class="text-gray-400">You: </span>
                            @endif
                            {{ $conversation->latestMessage->message ?? ($conversation->latestMessage->attachment_name ? '📎 ' . $conversation->latestMessage->attachment_name : '') }}
                        @else
                            <span class="italic">No messages yet</span>
                        @endif
                    </p>
                    @if($unread > 0)
                        <span class="flex-shrink-0 ml-1 inline-flex items-center justify-center min-w-[20px] h-5 px-1 bg-blue-500 text-white text-xs font-bold rounded-full">
                            {{ $unread > 99 ? '99+' : $unread }}
                        </span>
                    @endif
                </div>
            </div>
        </button>
    @empty
        <div class="text-center py-8 text-sm text-gray-400">
            @if($search)
                No conversations match "{{ $search }}"
            @else
                No conversations yet.<br>Start one with the + button above.
            @endif
        </div>
    @endforelse

    @if($hasMore)
        <div class="p-3 text-center">
            <button wire:click="loadMore" class="text-sm text-blue-500 hover:underline">Load more</button>
        </div>
    @endif
</div>
</div>

@script
<script>
    window.Echo.private(`user.{{ Auth::id() }}`)
        .listen('MessageSent', (e) => {
            $wire.refresh();
        });
</script>
@endscript
