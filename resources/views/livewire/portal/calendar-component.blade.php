<div>
    <h1 class="h3 mb-1">📅 Mi calendario</h1>
    <p class="text-muted mb-4">Citas, terapias, medicamentos con horario y tus propios recordatorios, todo en un solo lugar.</p>

    @if (session('calendar_status'))
        <div class="alert alert-success">{{ session('calendar_status') }}</div>
    @endif

    @if (! $case)
        <div class="alert alert-warning">No tienes un caso activo todavía.</div>
    @else
        <div class="feed-card mb-3">
            <div class="d-flex flex-wrap gap-3 small">
                <label class="d-inline-flex align-items-center gap-1 mb-0" style="cursor:pointer;">
                    <input type="checkbox" class="calendar-filter" data-kind="cita" checked>
                    <span style="display:inline-block;width:.8rem;height:.8rem;border-radius:50%;background:#0ea5e9;"></span> Cita
                </label>
                <label class="d-inline-flex align-items-center gap-1 mb-0" style="cursor:pointer;">
                    <input type="checkbox" class="calendar-filter" data-kind="terapia" checked>
                    <span style="display:inline-block;width:.8rem;height:.8rem;border-radius:50%;background:#7c3aed;"></span> Terapia
                </label>
                <label class="d-inline-flex align-items-center gap-1 mb-0" style="cursor:pointer;">
                    <input type="checkbox" class="calendar-filter" data-kind="tarea" checked>
                    <span style="display:inline-block;width:.8rem;height:.8rem;border-radius:50%;background:#64748b;"></span> Tarea
                </label>
                <label class="d-inline-flex align-items-center gap-1 mb-0" style="cursor:pointer;">
                    <input type="checkbox" class="calendar-filter" data-kind="referral" checked>
                    <span style="display:inline-block;width:.8rem;height:.8rem;border-radius:50%;background:#0e7490;"></span> Remisión
                </label>
                <label class="d-inline-flex align-items-center gap-1 mb-0" style="cursor:pointer;">
                    <input type="checkbox" class="calendar-filter" data-kind="medication" checked>
                    <span style="display:inline-block;width:.8rem;height:.8rem;border-radius:50%;background:#f97316;"></span> Medicamento
                </label>
                <label class="d-inline-flex align-items-center gap-1 mb-0" style="cursor:pointer;">
                    <input type="checkbox" class="calendar-filter" data-kind="personal_reminder" checked>
                    <span style="display:inline-block;width:.8rem;height:.8rem;border-radius:50%;background:#ec4899;"></span> Mi recordatorio
                </label>
            </div>
            <p class="text-muted small mb-0 mt-2">💡 Toca cualquier día para agregar un recordatorio ahí mismo. Toca una cita o terapia para confirmar tu asistencia. Arrastra tus propios recordatorios (📌) para reprogramarlos.</p>
        </div>

        <div class="feed-card mb-4" id="portalCalendar" wire:ignore></div>

        <div class="feed-card">
            <h2 class="h5 mb-3">➕ Agregar recordatorio personal</h2>
            <p class="text-muted small">Solo tú lo ves — no es una cita clínica, es tu propio espacio de organización. También puedes tocar un día del calendario de arriba.</p>
            <form wire:submit="addReminder">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">¿Qué quieres recordar?</label>
                        <input type="text" class="form-control" wire:model="title" placeholder="Ej: Tomar agua, llamar a mi hermana">
                        @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha y hora</label>
                        <input type="datetime-local" class="form-control" wire:model="remind_at">
                        @error('remind_at') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nota (opcional)</label>
                        <input type="text" class="form-control" wire:model="notes">
                    </div>
                </div>
                <button type="submit" class="btn btn-game mt-3">📌 Agregar</button>
            </form>
        </div>

        <div class="modal fade" id="appointmentResponseModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 1.5rem;">
                    <div class="modal-header">
                        <h5 class="modal-title">📅 <span id="appointmentResponseTitle"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">¿Podrás asistir?</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-danger" id="appointmentResponseDecline">❌ No podré asistir</button>
                        <button type="button" class="btn btn-game" id="appointmentResponseConfirm">✅ Confirmaré</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="reminderModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 1.5rem;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reminderModalTitle">📌 Nuevo recordatorio</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="reminderModalId">
                        <div class="mb-3">
                            <label class="form-label">¿Qué quieres recordar?</label>
                            <input type="text" class="form-control" id="reminderModalTitleInput" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha y hora</label>
                            <input type="datetime-local" class="form-control" id="reminderModalDate" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Nota (opcional)</label>
                            <input type="text" class="form-control" id="reminderModalNotes">
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-danger me-auto d-none" id="reminderModalDelete">🗑️ Borrar</button>
                        <button type="button" class="btn btn-game" id="reminderModalSave">💾 Guardar</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('portalCalendar');
                if (! el || typeof FullCalendar === 'undefined') {
                    return;
                }

                var wireRoot = el.closest('[wire\\:id]');
                var componentId = wireRoot ? wireRoot.getAttribute('wire:id') : null;
                var component = componentId && typeof Livewire !== 'undefined' ? Livewire.find(componentId) : null;

                var activeKinds = new Set(['cita', 'terapia', 'tarea', 'referral', 'medication', 'personal_reminder']);
                function filterKey(props) {
                    return props.kind === 'agenda_item' ? props.type : props.kind;
                }

                function pad(n) { return String(n).padStart(2, '0'); }
                function toLocalInputValue(date) {
                    return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
                }

                var reminderModalEl = document.getElementById('reminderModal');
                var reminderModal = window.bootstrap ? new bootstrap.Modal(reminderModalEl) : null;

                function openReminderModal(opts) {
                    document.getElementById('reminderModalTitle').textContent = opts.id ? '📌 Editar recordatorio' : '📌 Nuevo recordatorio';
                    document.getElementById('reminderModalId').value = opts.id || '';
                    document.getElementById('reminderModalTitleInput').value = opts.title || '';
                    document.getElementById('reminderModalNotes').value = opts.notes || '';
                    document.getElementById('reminderModalDate').value = toLocalInputValue(opts.date || new Date());
                    document.getElementById('reminderModalDelete').classList.toggle('d-none', ! opts.id);
                    if (reminderModal) { reminderModal.show(); }
                }

                document.getElementById('reminderModalSave').onclick = function () {
                    var idVal = document.getElementById('reminderModalId').value;
                    var title = document.getElementById('reminderModalTitleInput').value.trim();
                    var date = document.getElementById('reminderModalDate').value;
                    var notes = document.getElementById('reminderModalNotes').value.trim();
                    if (! title || ! date) { return; }
                    if (component) { component.call('saveReminder', idVal ? parseInt(idVal, 10) : null, title, date, notes || null); }
                    if (reminderModal) { reminderModal.hide(); }
                };

                document.getElementById('reminderModalDelete').onclick = function () {
                    var idVal = document.getElementById('reminderModalId').value;
                    if (! idVal || ! confirm('¿Borrar este recordatorio?')) { return; }
                    if (component) { component.call('deleteReminder', parseInt(idVal, 10)); }
                    if (reminderModal) { reminderModal.hide(); }
                };

                var calendar = new FullCalendar.Calendar(el, {
                    locale: 'es',
                    initialView: 'dayGridMonth',
                    height: 'auto',
                    headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
                    footerToolbar: { center: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
                    buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día', list: 'Lista' },
                    events: function (fetchInfo, successCallback, failureCallback) {
                        fetch(@js(route('portal.calendar.events')) + '?start=' + fetchInfo.startStr + '&end=' + fetchInfo.endStr)
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                successCallback(data.filter(function (e) { return activeKinds.has(filterKey(e.extendedProps)); }));
                            })
                            .catch(failureCallback);
                    },
                    dateClick: function (info) {
                        var clicked = new Date(info.date);
                        if (info.allDay) { clicked.setHours(9, 0, 0, 0); }
                        openReminderModal({ date: clicked });
                    },
                    eventClick: function (info) {
                        var props = info.event.extendedProps;

                        if (props.kind === 'personal_reminder') {
                            openReminderModal({ id: props.id, title: info.event.title.replace(/^📌 /, ''), date: info.event.start, notes: props.notes });
                            return;
                        }

                        if (! props.respondable) {
                            return;
                        }

                        var modalEl = document.getElementById('appointmentResponseModal');
                        document.getElementById('appointmentResponseTitle').textContent = info.event.title;
                        var modal = window.bootstrap ? new bootstrap.Modal(modalEl) : null;

                        document.getElementById('appointmentResponseConfirm').onclick = function () {
                            if (component) { component.call('respondToAppointment', props.id, 'confirmada'); }
                            if (modal) { modal.hide(); }
                        };
                        document.getElementById('appointmentResponseDecline').onclick = function () {
                            if (component) { component.call('respondToAppointment', props.id, 'no_asistira'); }
                            if (modal) { modal.hide(); }
                        };

                        if (modal) { modal.show(); }
                    },
                    eventDrop: function (info) {
                        var props = info.event.extendedProps;
                        if (props.kind !== 'personal_reminder') { info.revert(); return; }
                        if (component) { component.call('rescheduleReminder', props.id, info.event.start.toISOString()); }
                    },
                });
                calendar.render();

                document.querySelectorAll('.calendar-filter').forEach(function (checkbox) {
                    checkbox.addEventListener('change', function () {
                        var kind = checkbox.getAttribute('data-kind');
                        if (checkbox.checked) { activeKinds.add(kind); } else { activeKinds.delete(kind); }
                        calendar.refetchEvents();
                    });
                });

                if (typeof Livewire !== 'undefined') {
                    Livewire.on('calendar-refresh', function () {
                        calendar.refetchEvents();
                    });
                }
            });
        </script>
    @endif
</div>
