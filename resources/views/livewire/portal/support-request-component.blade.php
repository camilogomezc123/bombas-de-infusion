<div>
    <h1 class="h3 mb-1">🆘 Necesito ayuda</h1>
    <p class="text-muted">Cuéntanos qué dificultad tienes. Tu equipo la revisará y te responderá aquí mismo.</p>

    @if (session('support_status'))
        <div class="alert alert-success">{{ session('support_status') }}</div>
    @endif

    <div class="alert alert-secondary small">
        ⚠️ Esto no es un servicio de vigilancia permanente. Si tienes una emergencia médica, comunícate con los canales de urgencia de tu institución.
    </div>

    @if (! $case)
        <div class="alert alert-warning">No tienes un caso activo todavía.</div>
    @else
        <div class="feed-card mb-4">
            <h2 class="h5 mb-3">✍️ Reportar una solicitud</h2>
            <form wire:submit="save">
                <div class="mb-3">
                    <label class="form-label">Tipo de solicitud</label>
                    <select class="form-select" wire:model="type">
                        @foreach (\App\Models\SupportRequest::TYPES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cuéntanos</label>
                    <div class="position-relative">
                        <textarea class="form-control" rows="3" wire:model="description" id="support-description"></textarea>
                        <button type="button" class="voice-input-btn" data-target="support-description" title="Hablar en vez de escribir">🎤</button>
                    </div>
                    @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">¿Qué tan urgente sientes que es?</label>
                    <select class="form-select" wire:model="priority">
                        <option value="baja">🟢 Puede esperar</option>
                        <option value="media">🟡 Me gustaría que la revisen pronto</option>
                        <option value="alta">🔴 Es urgente para mí</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-game">📨 Enviar</button>
            </form>
        </div>

        <h2 class="h5 mb-3">💬 Mis solicitudes</h2>
        @forelse ($requests as $request)
            <div class="feed-card mb-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="avatar-circle" style="background: linear-gradient(135deg,#ef4444,#b91c1c);">🙋</div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between flex-wrap gap-1">
                            <span class="badge {{ match($request->status) { 'resuelta' => 'bg-success', 'respondida' => 'bg-primary', 'escalada' => 'bg-danger', default => 'bg-secondary' } }}">{{ $request->statusLabel() }}</span>
                            <span class="text-muted small">{{ $request->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="mt-2 mb-1">{{ $request->description }}</p>
                    </div>
                </div>
                @if ($request->isAnswered())
                    <div class="d-flex align-items-start gap-2 mt-3">
                        <div class="avatar-circle" style="background: linear-gradient(135deg,#0ea5e9,#0e7490);">🩺</div>
                        <div class="flex-grow-1 rounded-4 p-2" style="background:#eef6ff;">
                            <strong class="small">Tu equipo respondió:</strong>
                            <p class="mb-0">{{ $request->response_text }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-muted small mb-0 mt-2">⏳ Aún sin respuesta.</p>
                @endif
            </div>
        @empty
            <p class="text-muted">Todavía no has reportado ninguna dificultad.</p>
        @endforelse
    @endif
</div>
