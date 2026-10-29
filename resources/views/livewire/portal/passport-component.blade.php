<div>
    <h1 class="h3 mb-1">📖 Antes y ahora</h1>
    <p class="text-muted mb-4">Cuéntanos cómo era la vida antes de la hospitalización. Esto ayuda a tu equipo a entender qué es importante para ti.</p>

    @if (session('passport_status'))
        <div class="alert alert-success">{{ session('passport_status') }}</div>
    @endif

    @if (! $case)
        <div class="alert alert-warning">No tienes un caso activo todavía.</div>
    @else
        @if ($passport)
            <div class="alert {{ $passport->is_confirmed ? 'alert-success' : 'alert-info' }} mb-4">
                @if ($passport->is_confirmed)
                    ✅ Tu equipo confirmó esta información.
                @else
                    ⏳ Tu equipo revisará esta información próximamente.
                @endif
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="feed-card h-100 game-pop" style="background: linear-gradient(135deg,#fef3c7,#fff);">
                    <h2 class="h6 text-muted">⏮️ Antes de la hospitalización</h2>
                    <p class="mb-1"><strong>Movilidad:</strong> {{ $passport?->mobility_before ?: 'Sin diligenciar' }}</p>
                    <p class="mb-1"><strong>Autonomía:</strong> {{ $passport?->autonomy_before ?: 'Sin diligenciar' }}</p>
                    <p class="mb-0"><strong>Actividades habituales:</strong> {{ $passport?->habitual_activities ?: 'Sin diligenciar' }}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feed-card h-100 game-pop" style="background: linear-gradient(135deg,#dcfce7,#fff);">
                    <h2 class="h6 text-muted">🚀 Ahora (último seguimiento)</h2>
                    @if ($latestFollowup)
                        <p class="mb-1"><strong>Capacidad funcional:</strong> {{ $latestFollowup->functional_capacity ?: 'Sin dato' }}</p>
                        <p class="mb-1"><strong>Movilidad:</strong> {{ $latestFollowup->mobility ?: 'Sin dato' }}</p>
                        <p class="mb-0"><strong>Fuerza:</strong> {{ $latestFollowup->strength ?: 'Sin dato' }}</p>
                    @else
                        <p class="text-muted mb-0">Todavía no hay un seguimiento registrado para comparar.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="feed-card mb-4">
            <h2 class="h5 mb-3">✏️ Actualizar información</h2>
            <form wire:submit="save">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Movilidad antes del ingreso</label>
                        <textarea class="form-control" rows="2" wire:model="mobility_before"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Autonomía antes del ingreso</label>
                        <textarea class="form-control" rows="2" wire:model="autonomy_before"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Actividades habituales</label>
                        <textarea class="form-control" rows="2" wire:model="habitual_activities"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Apoyos con los que contaba</label>
                        <textarea class="form-control" rows="2" wire:model="supports_before"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Situación actual</label>
                        <textarea class="form-control" rows="2" wire:model="current_situation"></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Barreras del hogar</label>
                        <textarea class="form-control" rows="2" wire:model="home_barriers"></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Barreras de transporte</label>
                        <textarea class="form-control" rows="2" wire:model="transport_barriers"></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Barreras de acompañamiento</label>
                        <textarea class="form-control" rows="2" wire:model="companion_barriers"></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Barreras de acceso</label>
                        <textarea class="form-control" rows="2" wire:model="access_barriers"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-game mt-3">💾 Guardar</button>
            </form>
        </div>

        <div class="feed-card mb-4">
            <h2 class="h5 mb-3">🌟 Necesidades y objetivos, en tus palabras</h2>
            <p class="text-muted small">Ejemplos: vestirme, caminar hasta el baño, subir las escaleras de mi casa, cocinar, volver al trabajo.</p>
            <form wire:submit="addItem" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" wire:model="new_item_type">
                        @foreach ($itemTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7">
                    <input type="text" class="form-control" wire:model="new_item_description" placeholder="Ej: quiero volver a cocinar">
                    @error('new_item_description') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-game w-100">➕</button>
                </div>
            </form>

            @if ($passport && $passport->items->isNotEmpty())
                <ul class="list-group list-group-flush">
                    @foreach ($passport->items as $item)
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span>{{ $item->type === 'necesidad' ? '🔧' : '⭐' }} {{ $item->description }}</span>
                            <span class="badge {{ $item->type === 'necesidad' ? 'bg-warning text-dark' : 'bg-info text-dark' }}">{{ $itemTypes[$item->type] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</div>
