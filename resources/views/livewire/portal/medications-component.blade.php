<div>
    <h1 class="h3 mb-1">💊 Medicamentos</h1>
    <p class="text-muted mb-4">La lista de medicamentos que continúas, los nuevos y los que se suspendieron, según lo conciliado por tu equipo.</p>

    @if (! $reconciliation || $reconciliation->items->isEmpty())
        <div class="alert alert-warning">Tu equipo aún no ha registrado la conciliación de medicamentos.</div>
    @else
        @php($statusMeta = [
            'continua' => ['icon' => '✅', 'color' => 'linear-gradient(135deg,#22c55e,#15803d)'],
            'nueva' => ['icon' => '🆕', 'color' => 'linear-gradient(135deg,#0ea5e9,#2563eb)'],
            'ajustada' => ['icon' => '⚖️', 'color' => 'linear-gradient(135deg,#facc15,#ca8a04)'],
            'suspendida' => ['icon' => '⛔', 'color' => 'linear-gradient(135deg,#ef4444,#b91c1c)'],
        ])
        <div class="mission-grid">
            @foreach ($reconciliation->items as $item)
                @php($meta = $statusMeta[$item->status] ?? ['icon' => '💊', 'color' => 'linear-gradient(135deg,#94a3b8,#64748b)'])
                <div class="mission-card game-pop">
                    <span class="mission-icon" style="background: {{ $meta['color'] }};">{{ $meta['icon'] }}</span>
                    <div class="mission-title">{{ $item->medication_name }}</div>
                    <p class="mission-desc mb-2">
                        {{ $item->dose ?: 'Dosis sin especificar' }}
                        @if ($item->route)
                            · {{ \App\Models\MedicationReconciliationItem::ROUTES[$item->route] ?? $item->route }}
                        @endif
                        @if ($item->frequency)
                            · {{ $item->frequency }}
                        @endif
                    </p>
                    @if ($item->patient_instructions)
                        <p class="small mb-2">{{ $item->patient_instructions }}</p>
                    @endif
                    <span @class([
                        'badge text-nowrap d-block mb-2',
                        'bg-success' => $item->status === 'continua',
                        'bg-info text-dark' => $item->status === 'nueva',
                        'bg-warning text-dark' => $item->status === 'ajustada',
                        'bg-danger' => $item->status === 'suspendida',
                    ]) style="width: fit-content;">{{ $item->statusLabel() }}</span>
                    <a href="{{ route('portal.support', ['tipo' => 'duda_medicamento', 'medicamento' => $item->medication_name]) }}" class="small d-block">❓ Tengo una duda sobre este medicamento</a>
                </div>
            @endforeach
        </div>
    @endif
</div>
