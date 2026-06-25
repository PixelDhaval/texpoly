<div>
<div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-white">
    <h2 class="font-semibold text-gray-800 text-sm">Messages</h2>
    <button
        wire:click="$set('showNewModal', true)"
        class="w-8 h-8 flex items-center justify-center rounded-full bg-blue-500 text-white hover:bg-blue-600 transition-colors"
        title="New conversation"
    >
        <i class="bi bi-plus-lg text-sm"></i>
    </button>
</div>

@if($showNewModal)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4">
        <div class="flex items-center justify-between px-5 py-4 border-b">
            <h3 class="font-semibold text-gray-800">New Conversation</h3>
            <button wire:click="$set('showNewModal', false)" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="px-5 py-4 space-y-4">
            {{-- User search --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Add people</label>
                <div class="flex flex-wrap gap-2 p-2 border border-gray-300 rounded-lg min-h-[42px]">
                    @foreach($selectedUsers as $u)
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                            {{ $u['name'] }}
                            <button wire:click="removeUser({{ $u['id'] }})" class="text-blue-600 hover:text-blue-900">
                                <i class="bi bi-x"></i>
                            </button>
                        </span>
                    @endforeach
                    <input
                        wire:model.live.debounce.300ms="userSearch"
                        type="text"
                        placeholder="{{ empty($selectedUsers) ? 'Search by name...' : '' }}"
                        class="flex-1 min-w-[120px] text-sm focus:outline-none"
                    >
                </div>

                @if(!empty($userResults))
                <div class="mt-1 border border-gray-200 rounded-lg shadow-sm max-h-40 overflow-y-auto">
                    @foreach($userResults as $u)
                        <button
                            wire:click="addUser({{ $u['id'] }}, '{{ addslashes($u['name']) }}')"
                            class="w-full text-left px-3 py-2 hover:bg-gray-50 flex items-center gap-2 text-sm"
                        >
                            <div class="relative">
                                <div class="w-7 h-7 rounded-full bg-gray-400 flex items-center justify-center text-white text-xs font-semibold">
                                    {{ strtoupper(substr($u['name'], 0, 1)) }}
                                </div>
                                @if($u['is_online'])
                                    <span class="absolute bottom-0 right-0 w-2 h-2 bg-green-400 border border-white rounded-full"></span>
                                @endif
                            </div>
                            <div>
                                <div class="font-medium">{{ $u['name'] }}</div>
                                <div class="text-xs text-gray-400">{{ $u['email'] }}</div>
                            </div>
                        </button>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Group name if group --}}
            @if($conversationType === 'group')
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Group name</label>
                <input
                    wire:model.live="groupName"
                    type="text"
                    maxlength="255"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter group name..."
                >
                @error('groupName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            @endif

            @error('selectedUsers') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div class="px-5 py-4 border-t flex justify-end gap-2">
            <button
                wire:click="$set('showNewModal', false)"
                class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            >Cancel</button>
            <button
                wire:click="createConversation"
                wire:loading.attr="disabled"
                class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 transition-colors"
            >
                <span wire:loading.remove wire:target="createConversation">Start Chat</span>
                <span wire:loading wire:target="createConversation">Creating...</span>
            </button>
        </div>
    </div>
</div>
@endif
</div>
