<div>
    <h1 class="h3 mb-1">🎯 Mis misiones</h1>
    <p class="text-muted mb-4">Cada avance que cuentas suma energía a tu misión. ¡Sigue así!</p>

    @if (! $case)
        <div class="alert alert-warning">No tienes un caso activo todavía.</div>
    @elseif ($goals->isEmpty())
        <p class="text-muted">Tu equipo aún no ha definido metas para ti.</p>
    @else
        <div class="quest-card game-pop">
            <h2 class="h5 mb-3">✨ Contar un avance</h2>
            <form wire:submit="save">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Misión</label>
                        <select class="form-select" wire:model="selectedGoalId">
                            @foreach ($goals->where('status', 'active') as $goal)
                                <option value="{{ $goal->id }}">{{ $goal->description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">¿Cómo te fue?</label>
                        <textarea class="form-control" rows="2" wire:model="notes" placeholder="Cuenta cómo te fue con esta actividad..."></textarea>
                    </div>
                    <div class="col-12 form-check">
                        <input type="checkbox" class="form-check-input" id="difficulty" wire:model.live="had_difficulty">
                        <label class="form-check-label" for="difficulty">Tuve dificultad o no pude hacerlo</label>
                    </div>
                    @if ($had_difficulty)
                        <div class="col-md-6">
                            <label class="form-label">¿Por qué?</label>
                            <select class="form-select" wire:model.live="difficulty_reason">
                                <option value="">Selecciona...</option>
                                @foreach (\App\Models\GoalProgressReport::DIFFICULTY_REASONS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($difficulty_reason === 'otra')
                            <div class="col-md-6">
                                <label class="form-label">Cuéntanos más</label>
                                <input type="text" class="form-control" wire:model="difficulty_reason_other">
                            </div>
                        @endif
                    @endif
                </div>
                <button type="submit" class="btn btn-game mt-3">🚀 Guardar avance</button>
            </form>
        </div>

        @foreach ($goals as $goal)
            @php($reportsCount = $goal->progressReports->count())
            @php($pct = min(100, $reportsCount * 20))
            @php($stars = $reportsCount >= 5 ? 3 : ($reportsCount >= 2 ? 2 : ($reportsCount >= 1 ? 1 : 0)))
            <div class="quest-card game-pop">
                <div class="d-flex align-items-start gap-3 flex-wrap">
                    <div class="quest-ring" style="--pct: {{ $pct }};">{{ $pct }}%</div>
                    <div class="flex-grow-1" style="min-width: 220px;">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <span class="badge {{ $goal->status === 'active' ? 'bg-success' : ($goal->status === 'paused' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                    {{ $goal->status === 'active' ? '🟢' : ($goal->status === 'paused' ? '⏸️' : '🏁') }} {{ $goal->statusLabel() }}
                                </span>
                                <span class="text-muted small ms-2">{{ $goal->domain }}</span>
                            </div>
                            @if ($goal->target_date)
                                <span class="text-muted small">🗓️ Meta al {{ $goal->target_date->format('d/m/Y') }}</span>
                            @endif
                        </div>
                        <p class="mt-2 mb-1 fw-semibold">{{ $goal->description }}</p>
                        <div class="quest-stars mb-2">
                            @for ($i = 1; $i <= 3; $i++)
                                {{ $i <= $stars ? '★' : '☆' }}
                            @endfor
                            <span class="text-muted small ms-1">({{ $reportsCount }} avance{{ $reportsCount === 1 ? '' : 's' }} contado{{ $reportsCount === 1 ? '' : 's' }})</span>
                        </div>
                    </div>
                </div>

                @if ($goal->progressReports->isNotEmpty())
                    <ul class="list-group list-group-flush mt-2">
                        @foreach ($goal->progressReports as $report)
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span class="small text-muted">{{ $report->reported_at->format('d/m/Y H:i') }}</span>
                                    @if ($report->isValidated())
                                        <span class="badge bg-success">✅ Validado por {{ $report->validatedBy?->name }}</span>
                                    @else
                                        <span class="badge bg-secondary">⏳ Pendiente de revisión</span>
                                    @endif
                                </div>
                                @if ($report->notes)
                                    <p class="mb-0 mt-1">{{ $report->notes }}</p>
                                @endif
                                @if ($report->had_difficulty)
                                    <p class="mb-0 text-danger small">
                                        Reportó dificultad
                                        @if ($report->difficulty_reason)
                                            — {{ \App\Models\GoalProgressReport::DIFFICULTY_REASONS[$report->difficulty_reason] ?? $report->difficulty_reason }}
                                            @if ($report->difficulty_reason === 'otra' && $report->difficulty_reason_other)
                                                ({{ $report->difficulty_reason_other }})
                                            @endif
                                        @endif
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    @endif
</div>
