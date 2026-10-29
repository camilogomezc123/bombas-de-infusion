<x-filament-panels::page>
    <div class="mx-auto w-full max-w-7xl space-y-4">
        <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm font-semibold text-red-950">
            Esta plataforma apoya la gestión y trazabilidad del Programa ICU Liberation. No sustituye el juicio clínico ni la evaluación del equipo interdisciplinario.
        </div>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-bold uppercase text-slate-600">
                        <th class="px-3 py-2">Caso</th>
                        <th class="px-3 py-2">Cama</th>
                        <th class="px-3 py-2">UCI</th>
                        <th class="px-3 py-2">Día UCI</th>
                        <th class="px-3 py-2">Ventilación</th>
                        <th class="px-3 py-2">RASS/SAS</th>
                        <th class="px-3 py-2">Dolor</th>
                        <th class="px-3 py-2">Delirium</th>
                        <th class="px-3 py-2">Movilidad</th>
                        <th class="px-3 py-2">Restricción</th>
                        <th class="px-3 py-2">Familia</th>
                        <th class="px-3 py-2">Bundle del día</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->stays as $row)
                        @php($stay = $row['stay'])
                        <tr>
                            <td class="px-3 py-2 font-bold text-slate-900">{{ $stay->case_number }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $stay->bed_label ?? '—' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $stay->icuUnit?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $row['icu_day'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-slate-700">
                                @if ($stay->mechanical_ventilation)
                                    Día {{ $row['ventilation_day'] ?? '—' }}
                                @else
                                    No
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-700">
                                @if ($row['sedation'])
                                    {{ $row['sedation']->goal_value ?? '—' }} / {{ $row['sedation']->actual_value ?? '—' }}
                                @else
                                    Sin dato
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-700">{{ $row['pain']?->score ?? 'Sin dato' }}</td>
                            <td class="px-3 py-2 text-slate-700">
                                {{ match($row['delirium']?->result) { 'positive' => 'Positivo', 'negative' => 'Negativo', 'unable' => 'No evaluable', default => 'Sin dato' } }}
                            </td>
                            <td class="px-3 py-2 text-slate-700">{{ $row['mobility']?->achieved_level ?? 'Sin dato' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $row['restraints'] > 0 ? "Sí ({$row['restraints']})" : 'No' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $row['family_present'] ? 'Sí' : 'Sin dato' }}</td>
                            <td class="px-3 py-2">
                                @php($color = $row['bundle_status']['color'])
                                <span @class([
                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold',
                                    'bg-emerald-100 text-emerald-800' => $color === 'green',
                                    'bg-amber-100 text-amber-800' => $color === 'yellow',
                                    'bg-red-100 text-red-800' => $color === 'red',
                                    'bg-slate-100 text-slate-600' => $color === 'gray',
                                ])>
                                    <span @class(['h-2 w-2 rounded-full', 'bg-emerald-600' => $color === 'green', 'bg-amber-600' => $color === 'yellow', 'bg-red-600' => $color === 'red', 'bg-slate-400' => $color === 'gray'])></span>
                                    {{ $row['bundle_status']['label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="px-3 py-6 text-center text-sm text-slate-500">No hay estancias activas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
