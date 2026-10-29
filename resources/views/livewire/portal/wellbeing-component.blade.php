<div>
    <h1 class="h3 mb-1">💙 Cómo me siento</h1>
    <p class="text-muted mb-4">Un chequeo rápido, sin respuestas correctas o incorrectas — solo cuéntanos cómo vas.</p>

    @if (session('wellbeing_status'))
        <div class="alert alert-success">{{ session('wellbeing_status') }}</div>
    @endif

    @if (! $checkpoint)
        <div class="alert alert-info">
            Por ahora no tienes un cuestionario pendiente. Te avisaremos cuando corresponda diligenciar uno nuevo.
        </div>
    @elseif ($existing)
        <div class="feed-card game-pop">
            <h2 class="h5">✅ Checkpoint {{ $checkpointLabel }} — ya diligenciado</h2>
            <p class="text-muted">
                @if ($existing->confirmed_at)
                    Tu equipo ya revisó este resultado.
                @else
                    Tu equipo revisará este resultado contigo próximamente.
                @endif
            </p>
            <span class="badge {{ match($existing->semaforoGlobal()) { 'rojo' => 'bg-danger', 'amarillo' => 'bg-warning text-dark', 'verde' => 'bg-success', default => 'bg-secondary' } }}">
                {{ match($existing->semaforoGlobal()) { 'rojo' => 'Revisión recomendada', 'amarillo' => 'Revisión recomendada', 'verde' => 'Sin alertas', default => 'En revisión' } }}
            </span>
        </div>
    @else
        <p class="text-muted">Checkpoint actual: <strong>{{ $checkpointLabel }}</strong>. Responde con sinceridad — no hay respuestas correctas o incorrectas.</p>

        <form wire:submit="save">
            @if ($isCaregiver)
                <div class="feed-card game-pop">
                    <h2 class="h5 mb-3">🤝 Cómo se ha sentido usted como cuidador(a)</h2>
                    @foreach (\App\Models\PicsFollowup::PICSF_ITEMS as $i => $item)
                        <div class="mb-3">
                            <label class="form-label">{{ $item[0] }}</label>
                            <select class="form-select" wire:model="picsf.{{ $i }}">
                                <option value="">Selecciona...</option>
                                @foreach ($item[1] as $value => $option)
                                    <option value="{{ $value }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="feed-card game-pop">
                    <h2 class="h5 mb-3">😌 Ansiedad</h2>
                    @foreach (\App\Models\PicsFollowup::HADS_ITEMS as $i => $item)
                        <div class="mb-3">
                            <label class="form-label">{{ $item[0] }}</label>
                            <select class="form-select" wire:model="hads.{{ $i }}">
                                <option value="">Selecciona...</option>
                                @foreach ($item[1] as $value => $option)
                                    <option value="{{ $value }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>

                <div class="feed-card game-pop">
                    <h2 class="h5 mb-3">🌤️ Ánimo (últimas 2 semanas)</h2>
                    @foreach (\App\Models\PicsFollowup::PHQ9_ITEMS as $i => $label)
                        <div class="mb-3">
                            <label class="form-label">{{ $label }}</label>
                            <select class="form-select" wire:model="phq9.{{ $i }}">
                                <option value="">Selecciona...</option>
                                <option value="0">Nunca</option>
                                <option value="1">Varios días</option>
                                <option value="2">La mitad de los días o más</option>
                                <option value="3">Casi todos los días</option>
                            </select>
                        </div>
                    @endforeach
                </div>

                <div class="feed-card game-pop">
                    <h2 class="h5 mb-3">🛏️ Experiencias en la UCI</h2>
                    @foreach (\App\Models\PicsFollowup::PCPTSD_ITEMS as $i => $label)
                        <div class="mb-2 form-check">
                            <input type="checkbox" class="form-check-input" id="pcptsd{{ $i }}" wire:model="pcptsd.{{ $i }}">
                            <label class="form-check-label" for="pcptsd{{ $i }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>

                <div class="feed-card game-pop">
                    <h2 class="h5 mb-3">🔋 Fatiga y dolor (0 = nada, 10 = el peor posible)</h2>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Fatiga</label>
                            <input type="number" min="0" max="10" step="0.5" class="form-control" wire:model="fatigueScore">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dolor en reposo</label>
                            <input type="number" min="0" max="10" step="0.5" class="form-control" wire:model="painRest">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dolor al moverse</label>
                            <input type="number" min="0" max="10" step="0.5" class="form-control" wire:model="painMovement">
                        </div>
                    </div>
                </div>

                @if ($showPtg)
                    <div class="feed-card game-pop">
                        <h2 class="h5 mb-3">🌱 Cómo ha cambiado tu vida (0 = nada, 5 = en gran medida)</h2>
                        @foreach (\App\Models\PicsFollowup::PTG_ITEMS as $i => $label)
                            <div class="mb-3">
                                <label class="form-label">{{ $label }}</label>
                                <select class="form-select" wire:model="ptg.{{ $i }}">
                                    <option value="">Selecciona...</option>
                                    @for ($v = 0; $v <= 5; $v++)
                                        <option value="{{ $v }}">{{ $v }}</option>
                                    @endfor
                                </select>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

            <button type="submit" class="btn btn-game btn-lg">💙 Enviar</button>
        </form>
    @endif
</div>
