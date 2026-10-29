@php
    use App\Enums\CaseStatus;

    /** @var \App\Models\SepsisCase|null $record */
@endphp

@if ($record && $record->exists)
    @php
        $status = $record->status instanceof CaseStatus ? $record->status : CaseStatus::from($record->status);

        $zeroTime = $record->activation_at;
        $elapsed = $zeroTime ? $zeroTime->diffForHumans(null, true) : null;
        $deadline = $zeroTime ? $zeroTime->copy()->addMinutes(60) : null;

        $bundleTasks = [
            'antibiotic' => ['label' => 'Antibiótico', 'at' => $record->antibiotic_at],
            'lactate' => ['label' => 'Lactato', 'at' => $record->lactate_at],
            'cultures' => ['label' => 'Cultivos', 'at' => $record->culture_at],
        ];
        $bundlePending = collect($bundleTasks)->filter(fn (array $t): bool => ! $t['at'])->pluck('label');

        $service = $record->origin_service ?: ($record->uci ? 'UCI' : 'Urgencias / Hospitalización');

        $alerts = collect([
            $record->assigned_auditor_id ? null : 'Sin auditor asignado',
            $record->code_activated && $bundlePending->isNotEmpty() ? 'Bundle pendiente: '.$bundlePending->implode(', ') : null,
        ])->filter();

        $applicable = (bool) $record->code_activated && $zeroTime;
    @endphp

    <div
        class="fi-sepsis-case-header mb-2 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-white/10 dark:bg-white/5"
        x-data="{
            zero: {{ $zeroTime ? "new Date('".$zeroTime->toIso8601String()."')" : 'null' }},
            deadline: {{ $deadline ? "new Date('".$deadline->toIso8601String()."')" : 'null' }},
            applicable: {{ $applicable ? 'true' : 'false' }},
            now: new Date(),
            elapsedLabel: '{{ $elapsed ?? 'Sin tiempo transcurrido' }}',
            init() {
                if (! this.zero) { return }
                setInterval(() => {
                    this.now = new Date()
                    const diffMs = this.now - this.zero
                    const totalMin = Math.floor(diffMs / 60000)
                    const h = Math.floor(totalMin / 60)
                    const m = totalMin % 60
                    this.elapsedLabel = 'Transcurrido: ' + (h > 0 ? h + ' h ' : '') + m + ' min'
                }, 1000)
            },
            taskColor(doneIso) {
                if (! this.applicable) return 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400'
                if (doneIso) return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400'
                if (! this.deadline) return 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400'
                const minsLeft = (this.deadline - this.now) / 60000
                if (minsLeft <= 0) return 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400'
                if (minsLeft <= 15) return 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400'
                return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400'
            },
        }"
    >
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Caso</p>
            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $record->case_number ?? '—' }}</p>
            <p class="text-xs text-slate-600 dark:text-slate-300">{{ $record->patient?->full_name ?? 'Sin paciente' }}</p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ingreso</p>
            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $record->admission_number ?? '—' }}</p>
            <p class="text-xs text-slate-600 dark:text-slate-300">
                {{ $record->admission_at?->format('d/m/Y H:i') ?? 'Sin fecha de ingreso' }}
                · {{ $service }}
            </p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Estado</p>
            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $status->label() }}</p>
            <p class="text-xs text-slate-600 dark:text-slate-300">
                @if ($record->septic_shock)
                    Choque séptico
                @elseif ($record->is_valid)
                    Sepsis válida
                @else
                    Sin confirmar
                @endif
                · Auditor: {{ $record->assignedAuditor?->name ?? 'Sin asignar' }}
            </p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tiempo cero</p>
            <p class="text-sm font-bold text-slate-900 dark:text-white">
                {{ $zeroTime?->format('d/m/Y H:i') ?? 'Sin activación' }}
            </p>
            <p class="text-xs text-slate-600 dark:text-slate-300" x-text="zero ? elapsedLabel : 'Sin tiempo transcurrido'"></p>
        </div>

        <div class="sm:col-span-2 lg:col-span-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                Bundle de primera hora
                <span class="ml-2 font-normal normal-case text-slate-400">— verde: en tiempo/cumplido · amarillo: próximo a vencer · rojo: vencido · gris: no aplica</span>
            </p>
            <div class="mt-1 flex flex-wrap gap-2">
                @foreach ($bundleTasks as $key => $task)
                    <span
                        @class(['inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold'])
                        :class="taskColor({{ $task['at'] ? "'".$task['at']->toIso8601String()."'" : 'null' }})"
                    >
                        {{ $task['at'] ? '✓' : '·' }} {{ $task['label'] }}
                    </span>
                @endforeach
            </div>

            @if ($alerts->isNotEmpty())
                <p class="mt-2 text-xs font-semibold text-amber-700 dark:text-amber-400">
                    ⚠ {{ $alerts->implode(' · ') }}
                </p>
            @endif
        </div>
    </div>
@endif
