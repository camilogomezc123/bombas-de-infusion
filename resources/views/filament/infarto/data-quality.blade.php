<x-filament-panels::page>
    @php
        $rows = $this->rows;
        $average = $rows->isEmpty() ? 0 : round($rows->avg('percentage'), 1);
        $chronologyAlerts = $rows->sum(fn ($row) => count($row['issues']));
    @endphp
    <div class="mx-auto max-w-7xl space-y-5">
        <div class="grid gap-3 sm:grid-cols-3">
            <article class="rounded-xl border bg-white p-4"><p class="text-xs font-bold text-slate-600">Completitud promedio</p><p class="text-3xl font-black">{{ number_format($average, 1, ',', '.') }}%</p></article>
            <article class="rounded-xl border bg-white p-4"><p class="text-xs font-bold text-slate-600">Casos revisados</p><p class="text-3xl font-black">{{ $rows->count() }}</p></article>
            <article class="rounded-xl border bg-white p-4"><p class="text-xs font-bold text-slate-600">Alertas cronológicas</p><p class="text-3xl font-black">{{ $chronologyAlerts }}</p></article>
        </div>
        <div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-700"><tr><th class="px-4 py-3">Caso</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Completitud</th><th class="px-4 py-3">Faltantes</th><th class="px-4 py-3">Alertas</th><th class="px-4 py-3">Indicadores</th></tr></thead>
                <tbody class="divide-y">
                    @foreach($rows as $row)
                        <tr class="align-top">
                            <td class="px-4 py-3 font-bold"><a class="text-blue-800 hover:underline" href="{{ url('/infarto/acs-cases/'.$row['case']->id) }}">{{ $row['case']->case_number }}</a><div class="text-xs font-normal text-slate-500">{{ $row['case']->patient?->full_name }}</div></td>
                            <td class="px-4 py-3">{{ \App\Models\AcsCase::TYPES[$row['case']->acs_type] ?? 'Sin clasificar' }}</td>
                            <td class="px-4 py-3 font-bold">{{ number_format($row['percentage'], 1, ',', '.') }}%</td>
                            <td class="px-4 py-3">{{ implode(', ', $row['missing']) ?: 'Completo' }}</td>
                            <td class="px-4 py-3 text-red-800">{{ implode(' · ', $row['issues']) ?: 'Sin inconsistencias' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ implode(' · ', $row['indicator_notes']) ?: 'Elegibilidad sin alertas' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
