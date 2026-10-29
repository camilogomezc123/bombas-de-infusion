<x-filament-panels::page>
    @php($m = $this->metrics)
    <div class="mx-auto max-w-6xl space-y-5">
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-950">Los resultados apoyan la gestión institucional y no constituyen decisiones clínicas automáticas.</div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($this->goals() as $key => $goal)
                @php($value = $m[$key] ?? null)
                @php($color = \App\Services\AcsIndicatorService::status($key, $value))
                <article class="rounded-xl border bg-white p-4 shadow-sm">
                    <div class="mb-2 flex items-start justify-between gap-2"><h3 class="text-sm font-bold text-slate-800">{{ $goal['label'] }}</h3><span class="rounded-full px-2 py-1 text-xs font-bold bg-{{ $color }}-100 text-{{ $color }}-800">{{ strtoupper($color) }}</span></div>
                    <p class="text-3xl font-black text-slate-950">{{ $value === null ? 'Sin dato' : number_format($value,1,',','.').'%' }}</p>
                    <p class="mt-1 text-xs text-slate-500">Meta: {{ $goal['target'] === null ? 'Línea base' : '≥ '.number_format($goal['target'],0).'%' }}</p>
                </article>
            @endforeach
        </div>

        <section class="space-y-3" aria-labelledby="grace-analysis">
            <div>
                <h2 id="grace-analysis" class="text-base font-bold text-slate-950">GRACE, mortalidad y estancias</h2>
                <p class="text-sm text-slate-600">Análisis descriptivo de NSTE-ACS/NSTEMI según la categoría GRACE registrada por el equipo clínico. No calcula riesgo ni demuestra causalidad.</p>
            </div>
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-700">
                        <tr>
                            <th class="px-4 py-3">Categoría documentada</th>
                            <th class="px-4 py-3">Casos</th>
                            <th class="px-4 py-3">Muertes</th>
                            <th class="px-4 py-3">Mortalidad</th>
                            <th class="px-4 py-3">Mediana estancia hospitalaria</th>
                            <th class="px-4 py-3">Mediana estancia UCI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse(($m['grace_analysis'] ?? []) as $row)
                            <tr>
                                <td class="px-4 py-3 font-bold text-slate-900">{{ $row['label'] }}</td>
                                <td class="px-4 py-3">{{ $row['cases'] }}</td>
                                <td class="px-4 py-3">{{ $row['deaths'] }}</td>
                                <td class="px-4 py-3">{{ number_format($row['mortality_pct'], 1, ',', '.') }}%</td>
                                <td class="px-4 py-3">{{ $row['median_hospital_stay'] === null ? 'Sin dato' : number_format($row['median_hospital_stay'], 1, ',', '.').' días' }}</td>
                                <td class="px-4 py-3">{{ $row['median_icu_stay'] === null ? 'Sin dato' : number_format($row['median_icu_stay'], 1, ',', '.').' días' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No hay casos NSTE-ACS/NSTEMI para analizar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
