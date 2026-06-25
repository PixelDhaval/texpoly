<div class="flex flex-col h-full">
    @if($conversationId && $conversation)
        {{-- Header --}}
        <div class="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-200 flex-shrink-0">
            @php
                $otherParticipants = $conversation->participants()->where('users.id', '!=', Auth::id())->get();
                $displayName = $conversation->type === 'group'
                    ? ($conversation->name ?? 'Group Chat')
                    : ($otherParticipants->first()?->name ?? 'Chat');
                $isOnline = $conversation->type === 'private' && $otherParticipants->first()?->isOnline();
            @endphp
            <div class="relative">
                <div class="w-9 h-9 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold text-sm">
                    {{ strtoupper(substr($displayName, 0, 1)) }}
                </div>
                @if($isOnline)
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 border-2 border-white rounded-full"></span>
                @endif
            </div>
            <div>
                <div class="font-semibold text-gray-900 text-sm">{{ $displayName }}</div>
                <div class="text-xs text-gray-400">
                    @if($isOnline)
                        <span class="text-green-500">● Online</span>
                    @elseif($conversation->type === 'private' && $otherParticipants->first())
                        Last seen {{ $otherParticipants->first()->last_seen_at?->diffForHumans() ?? 'a while ago' }}
                    @else
                        {{ $otherParticipants->count() + 1 }} members
                    @endif
                </div>
            </div>
        </div>

        {{-- Messages --}}
        <div
            id="messages-container"
            class="flex-1 overflow-y-auto px-4 py-4 space-y-1 bg-gray-50"
            x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight; } }"
            x-init="$nextTick(() => scrollToBottom())"
            @scroll-to-bottom.window="$nextTick(() => scrollToBottom())"
        >
            @if($hasMoreMessages)
                <div class="text-center py-2">
                    <button wire:click="loadMoreMessages" wire:loading.attr="disabled"
                        class="text-xs text-blue-500 hover:underline">
                        <span wire:loading.remove wire:target="loadMoreMessages">Load older messages</span>
                        <span wire:loading wire:target="loadMoreMessages">Loading...</span>
                    </button>
                </div>
            @endif

            @php $prevDate = null; @endphp
            @foreach($messages as $msg)
                @php
                    $msgDate = \Carbon\Carbon::parse($msg['created_at'])->toDateString();
                    $showDate = $msgDate !== $prevDate;
                    $prevDate = $msgDate;
                @endphp

                @if($showDate)
                    <div class="flex items-center justify-center my-3">
                        <span class="px-3 py-1 text-xs text-gray-500 bg-gray-200 rounded-full">
                            {{ \Carbon\Carbon::parse($msg['created_at'])->isToday() ? 'Today' : (\Carbon\Carbon::parse($msg['created_at'])->isYesterday() ? 'Yesterday' : \Carbon\Carbon::parse($msg['created_at'])->format('d M Y')) }}
                        </span>
                    </div>
                @endif

                <div wire:key="msg-{{ $msg['id'] }}" class="flex {{ $msg['is_mine'] ? 'justify-end' : 'justify-start' }} mb-1">
                    <div class="max-w-[70%]">
                        @if(! $msg['is_mine'])
                            <div class="text-xs text-gray-400 mb-1 ml-1">{{ $msg['sender_name'] }}</div>
                        @endif
                        <div class="px-3 py-2 rounded-2xl text-sm break-words
                            {{ $msg['is_mine']
                                ? 'bg-blue-500 text-white rounded-br-sm'
                                : 'bg-white text-gray-800 shadow-sm rounded-bl-sm' }}">
                            @if($msg['message'])
                                <p>{{ $msg['message'] }}</p>
                            @endif
                            @if($msg['has_attachment'])
                                <a href="{{ route('chat.attachments.download', $msg['id']) }}"
                                   class="{{ $msg['is_mine'] ? 'text-blue-100 hover:text-white' : 'text-blue-600 hover:text-blue-800' }} flex items-center gap-1 text-xs mt-1 underline"
                                   download>
                                    <i class="bi bi-paperclip"></i>
                                    {{ $msg['attachment_name'] }}
                                    @if($msg['attachment_size'])
                                        <span class="opacity-70">({{ $msg['attachment_size'] }})</span>
                                    @endif
                                </a>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5 {{ $msg['is_mine'] ? 'text-right mr-1' : 'ml-1' }}">
                            {{ \Carbon\Carbon::parse($msg['created_at'])->format('H:i') }}
                        </div>
                    </div>
                </div>
            @endforeach

            @if(! empty($typingUsers))
                <div class="flex justify-start mt-1">
                    <div class="bg-white shadow-sm px-3 py-2 rounded-2xl rounded-bl-sm text-sm text-gray-400 italic">
                        {{ collect($typingUsers)->values()->implode(', ') }}
                        {{ count($typingUsers) === 1 ? 'is' : 'are' }} typing…
                    </div>
                </div>
            @endif
        </div>

        {{-- Composer --}}
        <div class="flex-shrink-0 bg-white border-t border-gray-200 p-3">
            @if($attachment)
                <div class="mb-2 flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg text-sm">
                    <i class="bi bi-paperclip text-blue-500"></i>
                    <span class="text-gray-700 truncate">{{ $attachment->getClientOriginalName() }}</span>
                    <button wire:click="$set('attachment', null)" class="ml-auto text-gray-400 hover:text-red-500">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            @endif

            @error('message') <p class="text-xs text-red-500 mb-1">{{ $message }}</p> @enderror
            @error('attachment') <p class="text-xs text-red-500 mb-1">{{ $message }}</p> @enderror

            <div class="flex items-end gap-2">
                <label class="flex-shrink-0 cursor-pointer text-gray-400 hover:text-blue-500 transition-colors pb-2">
                    <i class="bi bi-paperclip text-xl"></i>
                    <input type="file" wire:model="attachment" class="hidden"
                        accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.jpeg,.png">
                </label>
                <textarea
                    wire:model.live.debounce.500ms="message"
                    wire:keydown.enter.prevent="sendMessage"
                    placeholder="Type a message…"
                    rows="1"
                    class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-blue-400 max-h-32 overflow-y-auto"
                    x-data
                    @blur="$wire.stopTyping()"
                ></textarea>
                <button
                    wire:click="sendMessage"
                    wire:loading.attr="disabled"
                    class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-blue-500 text-white rounded-xl hover:bg-blue-600 disabled:opacity-50 transition-colors"
                >
                    <span wire:loading.remove wire:target="sendMessage"><i class="bi bi-send-fill text-sm"></i></span>
                    <span wire:loading wire:target="sendMessage"><i class="bi bi-hourglass-split text-sm animate-spin"></i></span>
                </button>
            </div>
        </div>

    @else
        {{-- Empty state --}}
        <div class="flex-1 flex flex-col items-center justify-center text-gray-400 select-none">
            <i class="bi bi-chat-square-dots text-6xl mb-4 opacity-30"></i>
            <p class="text-lg font-medium">Select a conversation</p>
            <p class="text-sm mt-1">Choose from your existing chats or start a new one.</p>
        </div>
    @endif
</div>

@script
<script>
    const currentUserId = {{ Auth::id() }};
    let activeChannel = null;
    let activeConvId = null;

    const playNotificationSound = () => {
        try {
            new Audio('{{ asset('audio/notification-tone.mp3') }}').play();
        } catch (e) {}
    };

    const subscribeToConversation = (conversationId) => {
        if (activeChannel && activeConvId !== conversationId) {
            window.Echo.leave(`conversation.${activeConvId}`);
            activeChannel = null;
        }

        if (!conversationId || conversationId === activeConvId) return;

        activeConvId = conversationId;

        activeChannel = window.Echo.private(`conversation.${conversationId}`)
            .listen('MessageSent', (e) => {
                if (e.message.sender_id !== currentUserId) {
                    playNotificationSound();
                    $wire.receiveMessage(e.message);
                }
            })
            .listen('UserTyping', (e) => {
                $wire.userIsTyping(e.user_id, e.user_name);
            })
            .listen('UserStoppedTyping', (e) => {
                $wire.userStoppedTyping(e.user_id);
            });
    };

    window.Echo.join('online')
        .here(() => {})
        .joining(() => {})
        .leaving(() => {});

    window.addEventListener('chat-channel-changed', (e) => {
        subscribeToConversation(e.detail.conversationId);
    });
</script>
@endscript
