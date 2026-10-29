<div class="dropdown me-lg-2 mb-2 mb-lg-0">
    <button class="btn btn-sm btn-light position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        🔔
        @if ($unreadCount > 0)
            <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle" style="font-size:.65rem;">
                {{ $unreadCount }}
            </span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-end p-2" style="width: 320px; max-height: 420px; overflow-y: auto;">
        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
            <strong class="small">Notificaciones</strong>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="btn btn-sm btn-link p-0 small">Marcar todas como leídas</button>
            @endif
        </div>

        @forelse ($notifications as $notification)
            <a href="{{ $notification->data['url'] ?? '#' }}" class="dropdown-item small rounded-3 mb-1 {{ $notification->read_at ? 'text-muted' : 'fw-semibold' }}" style="white-space: normal;">
                <div>{{ $notification->data['title'] ?? 'Notificación' }}</div>
                @if (! empty($notification->data['body']))
                    <div class="text-muted small fw-normal">{{ $notification->data['body'] }}</div>
                @endif
                <div class="text-muted small fw-normal">{{ $notification->created_at->diffForHumans() }}</div>
            </a>
        @empty
            <div class="text-muted small p-2">No tienes notificaciones todavía.</div>
        @endforelse
    </div>
</div>
