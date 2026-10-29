<div>
    <h1 class="h3 mb-1">Mi ruta como cuidador</h1>
    <p class="text-muted">Pasos que te ayudan a prepararte para acompañar la recuperación. Márcalos completados a tu ritmo.</p>

    @if ($steps->isEmpty())
        <div class="alert alert-warning">Tu equipo todavía no ha preparado los pasos de tu ruta.</div>
    @else
        <div class="card shadow-sm">
            <ul class="list-group list-group-flush">
                @foreach ($steps as $step)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="h6 mb-1">
                                    {{ $step->title }}
                                    @if ($step->is_required)
                                        <span class="badge bg-secondary">Obligatorio</span>
                                    @endif
                                </h2>
                                @if ($step->description)
                                    <p class="text-muted small mb-1">{{ $step->description }}</p>
                                @endif
                                @if ($step->caregiver_notes)
                                    <p class="small mb-0"><strong>Tu nota:</strong> {{ $step->caregiver_notes }}</p>
                                @endif
                            </div>
                            <div class="text-end">
                                @if ($step->confirmed_at)
                                    <span class="badge bg-success">Confirmado por tu equipo</span>
                                @elseif ($step->reported_at)
                                    <span class="badge bg-info text-dark">Completado, en revisión</span>
                                @else
                                    <span class="badge bg-light text-dark border">Pendiente</span>
                                @endif
                            </div>
                        </div>

                        @if (! $step->reported_at)
                            <div class="mt-2 d-flex gap-2">
                                <input type="text" wire:model="notes.{{ $step->id }}" class="form-control form-control-sm" placeholder="Nota opcional">
                                <button type="button" wire:click="markComplete({{ $step->id }})" class="btn btn-sm btn-posuci text-nowrap">Marcar completado</button>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
