<x-filament-panels::page>
    <div class="mx-auto w-full max-w-5xl space-y-5">
        <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm font-semibold text-red-950">
            Herramienta de apoyo a la ronda interdisciplinaria. No sustituye la evaluación del equipo ni ejecuta acciones clínicas automáticamente.
        </div>

        <div class="max-w-md">
            <label class="mb-1 block text-xs font-semibold text-slate-600">Selecciona una estancia activa</label>
            <select wire:model.live="stayId" class="fi-select-input block w-full rounded-lg border-slate-300 text-sm">
                <option value="">— Selecciona —</option>
                @foreach ($this->stayOptions as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @if ($stayId)
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <h3 class="mb-3 text-sm font-bold text-slate-800">Qué falta hoy para esta estancia</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse ($this->todaysGaps as $component)
                        <span @class([
                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold',
                            'bg-emerald-100 text-emerald-800' => $component['done'],
                            'bg-red-100 text-red-800' => ! $component['done'],
                        ])>
                            <span @class(['h-2 w-2 rounded-full', 'bg-emerald-600' => $component['done'], 'bg-red-600' => ! $component['done']])></span>
                            {{ $component['label'] }}
                            @if ($component['override'])
                                ({{ \App\Models\IcuStay::COMPONENT_STATUS_OPTIONS[$component['override']] ?? $component['override'] }})
                            @endif
                        </span>
                    @empty
                        <p class="text-sm text-slate-500">Sin componentes aplicables para esta estancia.</p>
                    @endforelse
                </div>
                <p class="mt-2 text-xs text-slate-500">Los estados "No aplica"/"No realizado"/"Desconocido" se marcan en la estancia, pestaña "Completitud del bundle".</p>
            </div>

            <form wire:submit="saveRound" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                {{ $this->form }}
                <div class="mt-4">
                    <button type="submit" class="rounded-lg bg-[#0e7490] px-4 py-2 text-sm font-bold text-white">Guardar ronda del día</button>
                </div>
            </form>

            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <h3 class="mb-3 text-sm font-bold text-slate-800">Rondas anteriores</h3>
                <ul class="space-y-2">
                    @forelse ($this->stayHistory as $round)
                        <li class="rounded-lg border border-slate-100 p-3 text-sm">
                            <p class="font-semibold text-slate-800">{{ \Illuminate\Support\Carbon::parse($round->round_date)->format('d/m/Y') }}</p>
                            <p class="text-xs text-slate-600">RASS/SAS: {{ $round->rass_sas_goal ?? '—' }} / {{ $round->rass_sas_actual ?? '—' }} · {{ $round->cam_icdsc_result ?? 'Sin CAM-ICU/ICDSC' }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Sin rondas registradas todavía.</li>
                    @endforelse
                </ul>
            </div>
        @else
            <p class="text-sm text-slate-500">Selecciona una estancia para registrar la ronda del día.</p>
        @endif
    </div>
</x-filament-panels::page>
