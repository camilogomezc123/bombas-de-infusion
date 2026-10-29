<x-filament-panels::page>
    <style>
        @media print {
            .viz-no-print { display: none !important; }
        }
    </style>

    <div class="viz-no-print mb-4 flex flex-wrap items-end gap-3">
        <label class="text-sm font-bold text-slate-700">
            Fecha
            <input type="date" wire:model.live="date" class="ml-2 rounded-lg border-slate-300" />
        </label>
        <label class="text-sm font-bold text-slate-700">
            Turno
            <select wire:model.live="shift" class="ml-2 rounded-lg border-slate-300">
                <option value="day">Día (07:00–19:00)</option>
                <option value="night">Noche (19:00–07:00)</option>
            </select>
        </label>
        <button type="button" onclick="window.print()" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-bold text-slate-700">🖨 Imprimir / exportar</button>
    </div>

    <div class="mx-auto w-full max-w-5xl space-y-3">
        @forelse ($this->rows as $row)
            @php $stay = $row['stay']; @endphp
            <div class="rounded-xl border bg-white p-4">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 class="font-black text-slate-900">{{ $stay->bed_label ?? '—' }} · {{ $stay->patient?->full_name ?? 'Sin paciente' }}</h3>
                    <span class="text-xs text-slate-500">{{ $stay->case_number }}</span>
                </div>

                <dl class="mt-2 grid gap-2 text-sm sm:grid-cols-4">
                    <div>
                        <dt class="text-xs text-slate-500">Fármacos activos en el turno</dt>
                        <dd class="font-semibold text-slate-800">{{ $row['drugs']->isNotEmpty() ? $row['drugs']->implode(', ') : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Eventos de bomba</dt>
                        <dd class="font-semibold text-slate-800">{{ $row['events_count'] }} ({{ $row['hard_overrides'] }} límite(s) duro(s) anulado(s))</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">VIS inicio → fin de turno</dt>
                        <dd class="font-semibold text-slate-800">{{ $row['vis_start'] ?? '—' }} → {{ $row['vis_end'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Alertas abiertas / resueltas</dt>
                        <dd class="font-semibold text-slate-800">{{ $row['alerts_opened']->count() }} abiertas · {{ $row['alerts_resolved']->count() }} resueltas</dd>
                    </div>
                </dl>

                @if ($row['alerts_opened']->isNotEmpty())
                    <div class="mt-2 border-t pt-2">
                        <p class="text-xs font-bold text-slate-600">Alertas nuevas en el turno:</p>
                        <ul class="mt-1 list-inside list-disc text-sm text-slate-700">
                            @foreach ($row['alerts_opened'] as $alert)
                                <li>{{ \App\Models\InfusionClinicalAlert::TYPES[$alert->type] ?? $alert->type }} — {{ $alert->message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @empty
            <p class="rounded-xl border bg-white px-4 py-8 text-center text-sm text-slate-500">
                Sin actividad de bombas registrada para este turno.
            </p>
        @endforelse
    </div>
</x-filament-panels::page>
