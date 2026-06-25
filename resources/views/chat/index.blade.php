@extends('chat.layout')

@section('content')
<div class="flex h-full">
    {{-- Sidebar --}}
    <div class="w-80 flex-shrink-0 border-r border-gray-200 bg-white flex flex-col">
        <livewire:chat.chat-sidebar />
        <div class="flex-1 overflow-y-auto">
            <livewire:chat.conversation-list />
        </div>
    </div>

    {{-- Main Panel --}}
    <div class="flex-1 flex flex-col min-w-0">
        <livewire:chat.conversation-view />
    </div>
</div>
@endsection
