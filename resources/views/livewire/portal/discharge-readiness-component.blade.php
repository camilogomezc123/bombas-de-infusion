<div>
    <h1 class="h3 mb-1">🎓 Preparación para el alta</h1>
    <p class="text-muted mb-4">Revisa cada tema con calma. Marca los que ya leíste — tu equipo verificará contigo que quedaron claros.</p>

    @if (! $check || $check->items->isEmpty())
        <div class="alert alert-warning">Tu equipo aún no ha preparado tu plan de alta.</div>
    @else
        @php($total = $check->items->count())
        @php($reviewed = $check->items->whereNotNull('reviewed_at')->count())
        @php($pct = $total > 0 ? round(($reviewed / $total) * 100) : 0)

        <div class="xp-card mb-4 game-pop">
            <div class="d-flex justify-content-between mb-1">
                <strong>Tu progreso</strong>
                <span class="text-muted small">{{ $reviewed }} / {{ $total }} temas revisados</span>
            </div>
            <div class="xp-bar-track">
                <div class="xp-bar-fill" style="width: {{ $pct }}%;"></div>
            </div>
        </div>

        @foreach ($check->items as $item)
            <div class="check-row {{ $item->reviewed_at ? 'is-done' : '' }} game-pop">
                <div class="check-circle">{{ $item->reviewed_at ? '✓' : '' }}</div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                        <h2 class="h6 mb-1">{{ $item->topicLabel() }}</h2>
                        @if ($item->understood === true)
                            <span class="badge bg-success">🏅 Comprensión verificada</span>
                        @elseif ($item->reviewed_at)
                            <span class="badge bg-info text-dark">👀 Ya lo revisaste</span>
                        @else
                            <span class="badge bg-light text-dark border">Sin revisar</span>
                        @endif
                    </div>
                    @if ($item->staff_instructions)
                        <p class="mb-1">{{ $item->staff_instructions }}</p>
                    @endif

                    @if (! $item->reviewed_at)
                        <button type="button" wire:click="reviewItem({{ $item->id }})" class="btn btn-sm btn-game mt-1">✅ Ya lo revisé</button>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
