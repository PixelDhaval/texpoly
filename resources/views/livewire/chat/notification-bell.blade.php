<span
    x-data="{}"
    @if($unreadCount > 0)
    class="badge bg-danger rounded-pill ms-1"
    @else
    class="badge bg-secondary rounded-pill ms-1 d-none"
    @endif
    wire:key="notification-bell-badge"
>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>

@script
<script>
    const playNotificationSound = () => {
        try {
            new Audio('{{ asset('audio/notification-tone.mp3') }}').play();
        } catch (e) {}
    };

    const waitForEcho = (cb, attempts = 20) => {
        if (window.Echo) { cb(); return; }
        if (attempts > 0) setTimeout(() => waitForEcho(cb, attempts - 1), 100);
    };
    waitForEcho(() => {
        window.Echo.private(`user.{{ Auth::id() }}`)
            .listen('MessageSent', () => {
                playNotificationSound();
                $wire.refreshCount();
            });
    });
</script>
@endscript
