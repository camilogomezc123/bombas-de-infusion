<div>
    <h1 class="h3 mb-1">✍️ Mi diario</h1>
    <p class="text-muted mb-4">Un espacio para contar cómo va cada día — tuyo y de tu familia.</p>

    @if (! $case)
        <div class="alert alert-warning">No tienes un caso activo todavía.</div>
    @else
        @if (session('diary_status'))
            <div class="alert alert-success">{{ session('diary_status') }}</div>
        @endif

        @if ($canWrite)
            <div class="feed-card mb-4">
                <h2 class="h5 mb-3">📝 Escribir una entrada</h2>
                <form wire:submit="save">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Fecha</label>
                            <input type="date" class="form-control" wire:model="entry_date">
                            @error('entry_date') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">¿Cómo estuvo el día?</label>
                            <div class="position-relative">
                                <textarea class="form-control" rows="3" wire:model="content" id="diary-content" placeholder="Cuéntale a la familia cómo fue el día..."></textarea>
                                <button type="button" class="voice-input-btn" data-target="diary-content" title="Hablar en vez de escribir">🎤</button>
                            </div>
                            @error('content') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        @if (! $isPatient)
                            <div class="col-md-6">
                                <label class="form-label">Mensaje para el paciente (opcional)</label>
                                <div class="position-relative">
                                    <textarea class="form-control" rows="2" wire:model="message_to_patient" id="diary-message"></textarea>
                                    <button type="button" class="voice-input-btn" data-target="diary-message" title="Hablar en vez de escribir">🎤</button>
                                </div>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <label class="form-label">Un recuerdo significativo (opcional)</label>
                            <div class="position-relative">
                                <textarea class="form-control" rows="2" wire:model="meaningful_memory" id="diary-memory"></textarea>
                                <button type="button" class="voice-input-btn" data-target="diary-memory" title="Hablar en vez de escribir">🎤</button>
                            </div>
                        </div>
                        @if (! $isPatient)
                            <div class="col-12 form-check">
                                <input type="checkbox" class="form-check-input" id="visible" wire:model="visible_to_patient">
                                <label class="form-check-label" for="visible">El paciente puede leer esta entrada</label>
                            </div>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-game mt-3">📮 Publicar entrada</button>
                </form>
            </div>
        @endif

        <h2 class="h5 mb-3">🗞️ Entradas</h2>

        @forelse ($entries as $entry)
            <div class="feed-card game-pop">
                <div class="feed-header">
                    <span class="avatar-circle">{{ mb_substr($entry->authorLabel(), 0, 1) }}</span>
                    <div>
                        <div class="fw-bold">{{ $entry->authorLabel() }}</div>
                        <div class="text-muted small">📅 {{ $entry->entry_date->format('d/m/Y') }}</div>
                    </div>
                </div>
                <p class="mb-2">{{ $entry->content }}</p>
                @if ($entry->message_to_patient)
                    <p class="mb-1 rounded-4 p-2" style="background:#fff7ed;"><em>💌 Mensaje para ti:</em> {{ $entry->message_to_patient }}</p>
                @endif
                @if ($entry->meaningful_memory)
                    <p class="mb-0 rounded-4 p-2" style="background:#eef6ff;"><em>✨ Recuerdo:</em> {{ $entry->meaningful_memory }}</p>
                @endif
                <div class="reaction-bar">
                    <button type="button" class="reaction-btn" data-reaction-id="{{ $entry->id }}" onclick="posuciToggleReaction(this, {{ $entry->id }})">❤️</button>
                </div>
            </div>
        @empty
            <p class="text-muted">{{ $isPatient ? 'Aún no hay entradas visibles para ti.' : 'Todavía no hay entradas registradas.' }}</p>
        @endforelse
    @endif
</div>
