@php
    use App\Enums\CaseStatus;
    use App\Models\PicsCase;

    /** @var \App\Models\PicsCase|null $record */
@endphp

@if ($record && $record->exists)
    @php
        $status = $record->status instanceof CaseStatus ? $record->status : CaseStatus::from($record->status);
    @endphp

    <div class="fi-pics-case-header mb-2 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-white/10 dark:bg-white/5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Caso</p>
            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $record->case_number ?? '—' }}</p>
            <p class="text-xs text-slate-600 dark:text-slate-300">{{ $record->patient?->full_name ?? 'Sin paciente' }}</p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ingreso al programa</p>
            <p class="text-sm font-bold text-slate-900 dark:text-white">
                {{ $record->enrollment_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}
            </p>
            <p class="text-xs text-slate-600 dark:text-slate-300">
                {{ PicsCase::ENROLLMENT_SOURCES[$record->enrollment_source] ?? 'Origen sin definir' }}
            </p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Estado</p>
            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $status->label() }}</p>
            <p class="text-xs text-slate-600 dark:text-slate-300">
                Responsable: {{ $record->assignedAuditor?->name ?? 'Sin asignar' }}
            </p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Seguimientos</p>
            <p class="text-sm font-bold text-slate-900 dark:text-white">
                {{ $record->followups()->count() }} de {{ count(\App\Models\PicsFollowup::CHECKPOINTS) }} checkpoints
            </p>
            <p class="text-xs text-slate-600 dark:text-slate-300">{{ $record->referrals()->count() }} remisión(es) registrada(s)</p>
        </div>
    </div>
@endif
