<div>
    <h1 class="h3 mb-1">📚 Educación</h1>
    <p class="text-muted mb-4">Contenido que tu equipo eligió para tu recuperación.</p>

    @if (session('education_status'))
        <div class="alert alert-success" role="status">{{ session('education_status') }}</div>
    @endif

    @if ($assignments->isEmpty())
        <div class="alert alert-warning">Tu equipo aún no te ha asignado contenido educativo.</div>
    @else
        @php($categoryMeta = [
            'respiratorio' => ['🌬️', 'linear-gradient(135deg,#0ea5e9,#2563eb)'],
            'movilidad' => ['🚶', 'linear-gradient(135deg,#f97316,#ea580c)'],
            'cognitivo' => ['🧠', 'linear-gradient(135deg,#a855f7,#7c3aed)'],
            'emocional' => ['💙', 'linear-gradient(135deg,#ec4899,#db2777)'],
            'nutricion' => ['🍎', 'linear-gradient(135deg,#22c55e,#15803d)'],
            'cuidador' => ['🤝', 'linear-gradient(135deg,#14b8a6,#0f766e)'],
            'general' => ['📌', 'linear-gradient(135deg,#6366f1,#4338ca)'],
            'otro' => ['📄', 'linear-gradient(135deg,#94a3b8,#64748b)'],
        ])
        @foreach ($assignments->groupBy('resource.category') as $category => $group)
            @php([$icon, $color] = $categoryMeta[$category] ?? $categoryMeta['otro'])
            <h2 class="h6 text-muted mt-4 mb-3">{{ $icon }} {{ \App\Models\EducationResource::CATEGORIES[$category] ?? 'General' }}</h2>
            @foreach ($group as $assignment)
                <div class="feed-card game-pop" wire:key="education-assignment-{{ $assignment->id }}">
                    <div class="feed-header">
                        <span class="story-ring {{ $assignment->isViewed() ? 'seen' : '' }}">
                            <span class="avatar-circle" style="background: {{ $color }};">{{ $icon }}</span>
                        </span>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                                <h3 class="h6 mb-0">{{ $assignment->resource->title }}</h3>
                                @if ($assignment->isValidated())
                                    <span class="badge bg-success text-nowrap">✓ Validado por tu equipo</span>
                                @elseif ($assignment->isCompleted())
                                    <span class="badge bg-warning text-dark text-nowrap">Pendiente de validación</span>
                                @elseif ($assignment->isViewed())
                                    <span class="badge bg-light text-dark border text-nowrap">Leído</span>
                                @else
                                    <span class="badge bg-light text-dark border text-nowrap">Nuevo</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <p class="mb-2" style="white-space: pre-line;">{{ $assignment->resource->body }}</p>
                    @if ($assignment->notes)
                        <p class="text-muted small mb-2"><em>💬 {{ $assignment->notes }}</em></p>
                    @endif

                    <div class="rounded border p-3 mt-3 bg-light-subtle">
                        <label for="education-response-{{ $assignment->id }}" class="form-label fw-semibold mb-1">
                            ¿Cómo te resultó este contenido?
                        </label>
                        <select
                            id="education-response-{{ $assignment->id }}"
                            wire:model="responses.{{ $assignment->id }}"
                            class="form-select form-select-sm mb-2"
                        >
                            <option value="">Selecciona una respuesta</option>
                            @foreach (\App\Models\EducationAssignment::RESPONSES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <label for="education-feedback-{{ $assignment->id }}" class="form-label small text-muted mb-1">
                            Comentario para tu equipo (opcional)
                        </label>
                        <textarea
                            id="education-feedback-{{ $assignment->id }}"
                            wire:model="feedback.{{ $assignment->id }}"
                            class="form-control form-control-sm"
                            rows="2"
                            maxlength="2000"
                            placeholder="Escribe una duda o cuéntanos qué fue lo más útil."
                        ></textarea>

                        @error('response')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                        @error('feedback')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button
                                type="button"
                                wire:click="saveResponse({{ $assignment->id }})"
                                wire:loading.attr="disabled"
                                wire:target="saveResponse({{ $assignment->id }})"
                                class="btn btn-sm btn-game"
                            >
                                {{ $assignment->isCompleted() ? 'Actualizar respuesta' : 'Enviar respuesta' }}
                            </button>
                            @if (! $assignment->isViewed())
                                <button type="button" wire:click="markViewed({{ $assignment->id }})" class="btn btn-sm btn-outline-secondary">
                                    Marcar como leído
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($assignment->isValidated())
                        <div class="alert alert-success small mt-3 mb-0">
                            <strong>Revisado por tu equipo.</strong>
                            @if ($assignment->validation_notes)
                                <span class="d-block mt-1">{{ $assignment->validation_notes }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        @endforeach
    @endif
</div>
