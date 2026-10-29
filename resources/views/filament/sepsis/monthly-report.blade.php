<x-filament-panels::page>
    @php
        $indicators = $this->indicators;
        $summary = $this->summary;
        $cost = $this->cost;
        $goals = $this->goals();
        $labels = [
            'bundle_pct' => 'Adherencia al bundle de primera hora',
            'map_goal_pct' => 'Meta de PAM en 3 horas',
            'mort_hosp_sepsis_pct' => 'Mortalidad hospitalaria por sepsis',
            'mort_hosp_shock_pct' => 'Mortalidad hospitalaria por choque séptico',
            'mort_30d_sepsis_pct' => 'Mortalidad a 30 días por sepsis',
            'mort_30d_shock_pct' => 'Mortalidad a 30 días por choque séptico',
        ];
    @endphp

    <style>
        @media print {
            .fi-topbar, .fi-sidebar, .no-print { display: none !important; }
            .fi-main { padding: 0 !important; }
        }
    </style>

    <div class="mx-auto w-full max-w-4xl space-y-6 bg-white p-2 text-slate-900">
        <div class="no-print flex justify-end">
            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center rounded-lg bg-[#17375e] px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-[#0d2340]"
            >
                Imprimir / Exportar a PDF
            </button>
        </div>

        <header class="border-b-2 border-[#17375e] pb-4">
            <h1 class="text-xl font-bold text-[#17375e]">Informe mensual — Programa Institucional de Excelencia en Sepsis</h1>
            <p class="mt-1 text-sm text-slate-600">Período: {{ now()->translatedFormat('F Y') }} · Generado el {{ now()->format('d/m/Y H:i') }}</p>
        </header>

        <section>
            <h2 class="mb-2 text-sm font-bold text-[#17375e]">1. Resultado de los 6 indicadores institucionales</h2>
            <table class="w-full border-collapse text-xs">
                <thead>
                    <tr class="border-b-2 border-slate-300 text-left">
                        <th class="py-1 pr-2">Indicador</th>
                        <th class="py-1 pr-2">Resultado</th>
                        <th class="py-1 pr-2">Meta</th>
                        <th class="py-1 pr-2">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($labels as $key => $label)
                        @php
                            $value = $indicators[$key] ?? null;
                            $meets = $this->meetsGoal($key, $value);
                        @endphp
                        <tr class="border-b border-slate-200">
                            <td class="py-1 pr-2">{{ $label }}</td>
                            <td class="py-1 pr-2 font-bold">{{ $value !== null ? number_format($value, 1, ',', '.').'%' : 'Sin dato' }}</td>
                            <td class="py-1 pr-2">{{ $goals[$key]['target'] }}%</td>
                            <td class="py-1 pr-2">{{ $meets === null ? 'Sin dato' : ($meets ? 'Cumple' : 'No cumple') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-2 text-slate-400">Sin casos en el período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section>
            <h2 class="mb-2 text-sm font-bold text-[#17375e]">2. Gobierno y calidad</h2>
            <table class="w-full border-collapse text-xs">
                <tbody>
                    <tr class="border-b border-slate-200"><td class="py-1 pr-2 font-semibold">Estado del programa</td><td class="py-1">{{ $summary['program_status'] }}</td></tr>
                    <tr class="border-b border-slate-200"><td class="py-1 pr-2 font-semibold">Cumplimiento de estándares</td><td class="py-1">{{ number_format($summary['compliance_percentage'], 1, ',', '.') }}%</td></tr>
                    <tr class="border-b border-slate-200"><td class="py-1 pr-2 font-semibold">Hallazgos abiertos</td><td class="py-1">{{ $summary['open_findings'] }}</td></tr>
                    <tr class="border-b border-slate-200"><td class="py-1 pr-2 font-semibold">Acciones vencidas</td><td class="py-1">{{ $summary['overdue_actions'] }}</td></tr>
                    <tr class="border-b border-slate-200"><td class="py-1 pr-2 font-semibold">Eventos de seguridad abiertos</td><td class="py-1">{{ $summary['open_safety_events'] }}</td></tr>
                    <tr class="border-b border-slate-200"><td class="py-1 pr-2 font-semibold">Completitud del registro clínico</td><td class="py-1">{{ number_format($summary['data_completeness_percentage'], 1, ',', '.') }}%</td></tr>
                </tbody>
            </table>
        </section>

        <section>
            <h2 class="mb-2 text-sm font-bold text-[#17375e]">3. Costo de la atención</h2>
            <table class="w-full border-collapse text-xs">
                <tbody>
                    <tr class="border-b border-slate-200"><td class="py-1 pr-2 font-semibold">Casos con costo registrado</td><td class="py-1">{{ $cost['count'] }}</td></tr>
                    <tr class="border-b border-slate-200"><td class="py-1 pr-2 font-semibold">Costo total</td><td class="py-1">{{ $cost['total_cost'] !== null ? '$'.number_format($cost['total_cost'], 0, ',', '.') : 'Sin dato' }}</td></tr>
                    <tr class="border-b border-slate-200"><td class="py-1 pr-2 font-semibold">Costo promedio</td><td class="py-1">{{ $cost['avg_cost'] !== null ? '$'.number_format($cost['avg_cost'], 0, ',', '.') : 'Sin dato' }}</td></tr>
                </tbody>
            </table>
        </section>

        <footer class="mt-8 border-t border-slate-200 pt-3 text-[10px] text-slate-400">
            Generado automáticamente por ÁGORA. Los 6 indicadores institucionales provienen de SepsisIndicatorService,
            fuente única de cálculo — este informe solo los consulta y presenta.
        </footer>
    </div>
</x-filament-panels::page>
