<div>
    <h1 class="h3 mb-1">🩺 Monitoreo en casa</h1>
    <p class="text-muted">Registra aquí las lecturas que tomes en casa (oxímetro, tensiómetro, termómetro, etc.). Es un registro manual — no se conecta automáticamente a ningún dispositivo todavía.</p>

    @if (session('monitoring_status'))
        <div class="alert alert-success">{{ session('monitoring_status') }}</div>
    @endif

    @if (! $case)
        <div class="alert alert-warning">No tienes un caso activo todavía.</div>
    @else
        <div class="feed-card mb-4">
            <h2 class="h5 mb-3">➕ Registrar una lectura</h2>
            <form wire:submit="save">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" wire:model="reading_type">
                            @foreach (\App\Models\HomeMonitoringReading::READING_TYPES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valor</label>
                        <input type="text" class="form-control" wire:model="value" placeholder="Ej: 97 o 120/80">
                        @error('value') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unidad (opcional)</label>
                        <input type="text" class="form-control" wire:model="unit" placeholder="Ej: %, lpm, mmHg">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha y hora</label>
                        <input type="datetime-local" class="form-control" wire:model="measured_at">
                        @error('measured_at') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Notas (opcional)</label>
                        <input type="text" class="form-control" wire:model="notes">
                    </div>
                </div>
                <button type="submit" class="btn btn-game mt-3">🩺 Guardar lectura</button>
            </form>
        </div>

        <h2 class="h5 mb-3">📈 Historial</h2>
        @if ($readings->isEmpty())
            <p class="text-muted">Todavía no hay lecturas registradas.</p>
        @else
            @php($readingIcons = [
                'spo2' => ['🌬️', 'linear-gradient(135deg,#0ea5e9,#2563eb)'],
                'heart_rate' => ['❤️', 'linear-gradient(135deg,#ef4444,#b91c1c)'],
                'blood_pressure' => ['🩸', 'linear-gradient(135deg,#ec4899,#db2777)'],
                'temperature' => ['🌡️', 'linear-gradient(135deg,#f97316,#ea580c)'],
                'respiratory_rate' => ['💨', 'linear-gradient(135deg,#22c55e,#15803d)'],
                'glucose' => ['🍬', 'linear-gradient(135deg,#a855f7,#7c3aed)'],
                'weight' => ['⚖️', 'linear-gradient(135deg,#facc15,#ca8a04)'],
                'otro' => ['📋', 'linear-gradient(135deg,#94a3b8,#64748b)'],
            ])
            <div class="mission-grid">
                @foreach ($readings as $reading)
                    @php([$icon, $color] = $readingIcons[$reading->reading_type] ?? ['📋', 'linear-gradient(135deg,#94a3b8,#64748b)'])
                    <div class="stat-tile game-pop" style="background: {{ $color }};">
                        <div class="d-flex justify-content-between align-items-start">
                            <span style="font-size:1.6rem;">{{ $icon }}</span>
                            <span class="small opacity-75">{{ $reading->measured_at->format('d/m H:i') }}</span>
                        </div>
                        <div class="fs-4 fw-bold mt-1">{{ $reading->value }} <span class="fs-6 fw-normal">{{ $reading->unit }}</span></div>
                        <div class="small opacity-75">{{ $reading->typeLabel() }}</div>
                        <div class="small opacity-75 mt-1">👤 {{ $reading->recordedBy?->name ?? $reading->recordedBy?->full_name ?? '—' }}</div>
                        @if ($reading->notes)
                            <div class="small mt-1">📝 {{ $reading->notes }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
