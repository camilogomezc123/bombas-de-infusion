<x-filament-panels::page>
    @php
        $system = $this->systemTracers;
        $tracer = $this->patientTracer;
    @endphp

    <div class="mx-auto w-full max-w-5xl space-y-6">
        <section aria-labelledby="trazadores-sistema">
            <h2 id="trazadores-sistema" class="mb-3 text-sm font-bold text-slate-800 dark:text-slate-200">Trazadores de sistema</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Equipo con competencia vigente</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">{{ $system['team_competency_ready'] }} / {{ $system['team_total'] }}</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Recursos disponibles</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">{{ $system['resources_available'] }} / {{ $system['resources_total'] }}</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Eventos de seguridad abiertos</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">{{ $system['open_safety_events'] }}</p>
                </article>
            </div>
        </section>

        <section aria-labelledby="trazador-paciente">
            <h2 id="trazador-paciente" class="mb-3 text-sm font-bold text-slate-800 dark:text-slate-200">Trazador de paciente</h2>

            <div class="mb-4 max-w-md">
                <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">Selecciona un caso</label>
                <select
                    wire:model.live="caseId"
                    class="fi-select-input block w-full rounded-lg border-slate-300 text-sm dark:border-white/10 dark:bg-slate-800 dark:text-white"
                >
                    <option value="">— Selecciona —</option>
                    @foreach ($this->caseOptions as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if ($tracer)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <p class="mb-3 text-sm text-slate-600 dark:text-slate-300">
                        <span class="font-bold text-slate-900 dark:text-white">{{ $tracer['case']->case_number }}</span>
                        · {{ $tracer['case']->patient?->full_name }}
                        · Auditor: {{ $tracer['case']->assignedAuditor?->name ?? 'Sin asignar' }}
                    </p>

                    <div class="mb-4 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-slate-200 p-3 dark:border-white/10">
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Cumplimiento del bundle (este caso)</p>
                            <p class="mt-1 text-lg font-extrabold text-slate-950 dark:text-white">
                                @if ($tracer['bundle_compliance']['percentage'] !== null)
                                    {{ number_format($tracer['bundle_compliance']['percentage'], 1, ',', '.') }}%
                                    <span class="text-xs font-normal text-slate-500 dark:text-slate-400">({{ $tracer['bundle_compliance']['done'] + $tracer['bundle_compliance']['done_late'] }}/{{ $tracer['bundle_compliance']['total'] }})</span>
                                @else
                                    Sin tareas registradas
                                @endif
                            </p>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-3 dark:border-white/10">
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Profesionales participantes</p>
                            <p class="mt-1 text-sm text-slate-800 dark:text-slate-100">
                                {{ $tracer['professionals'] ? implode(', ', $tracer['professionals']) : 'Sin dato' }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-3 dark:border-white/10">
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Registros faltantes</p>
                            <p class="mt-1 text-sm text-slate-800 dark:text-slate-100">
                                {{ $tracer['missing_records'] ? implode(', ', $tracer['missing_records']) : 'Ninguno' }}
                            </p>
                        </div>
                    </div>

                    <ol class="space-y-3 border-l-2 border-slate-200 pl-4 dark:border-white/10">
                        @forelse ($tracer['timeline'] as $event)
                            <li>
                                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                    {{ \Illuminate\Support\Carbon::parse($event['at'])->format('d/m/Y H:i') }}
                                    <span class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $event['group'] }}</span>
                                </p>
                                <p class="text-sm text-slate-800 dark:text-slate-100">{{ $event['label'] }}</p>
                            </li>
                        @empty
                            <li class="text-sm text-slate-500 dark:text-slate-400">Este caso todavía no tiene eventos registrados en la ruta clínica.</li>
                        @endforelse
                    </ol>
                </div>
            @else
                <p class="text-sm text-slate-500 dark:text-slate-400">Selecciona un caso para ver su recorrido completo.</p>
            @endif
        </section>
    </div>
</x-filament-panels::page>
