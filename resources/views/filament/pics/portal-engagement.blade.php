<x-filament-panels::page>
    @php($s = $this->summary)
    @php($alerts = $this->alerts)
    @php($trend = $this->trend)
    <div class="mx-auto w-full max-w-6xl space-y-5">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            Mide el uso real de <strong>{{ url('/portal/login') }}</strong> por parte de los pacientes y sus cuidadores — no reemplaza el juicio clínico, es un indicador de participación y respuesta.
        </div>

        @if (count($alerts) > 0)
            <section class="rounded-xl border border-red-300 bg-red-50 p-4">
                <h3 class="font-bold text-red-900">Alertas — {{ count($alerts) }} caso(s) necesitan atención</h3>
                <ul class="mt-2 space-y-1 text-sm text-red-950">
                    @foreach ($alerts as $alert)
                        <li>
                            <strong>{{ $alert['case']->case_number }}</strong> ({{ $alert['case']->patient?->full_name }}) —
                            {{ \App\Services\PortalEngagementService::ALERT_LABELS[$alert['reason']] ?? $alert['reason'] }}
                            @if ($alert['days'] !== null)
                                (hace {{ $alert['days'] }} día{{ $alert['days'] === 1 ? '' : 's' }})
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="rounded-xl border bg-white p-4">
            <h3 class="font-bold text-slate-900 mb-3">Actividad del portal — últimas 8 semanas</h3>
            @php($max = max(1, collect($trend)->max('value')))
            <div class="flex items-end gap-2" style="height: 120px;">
                @foreach ($trend as $week)
                    <div class="flex-1 flex flex-col items-center justify-end h-full">
                        <span class="text-xs font-bold text-slate-700">{{ $week['value'] }}</span>
                        <div class="w-full rounded-t bg-teal-500" style="height: {{ $week['value'] === 0 ? 2 : max(4, ($week['value'] / $max) * 90) }}px;"></div>
                        <span class="mt-1 text-[10px] text-slate-500">{{ $week['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['Casos con cuidador autorizado', $s['caregiver_authorized_pct']],
                ['Casos con al menos un ingreso al portal', $s['any_login_pct']],
                ['Casos con actividad en el diario', $s['diary_activity_pct']],
                ['Casos con autorreporte de bienestar', $s['wellbeing_self_report_pct']],
            ] as [$label, $value])
                <article class="rounded-xl border bg-white p-4">
                    <p class="text-xs font-bold text-slate-600">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-black text-slate-950">{{ $value === null ? 'Sin dato' : number_format($value, 1, ',', '.').'%' }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <article class="rounded-xl border bg-white p-4">
                <p class="text-xs font-bold text-slate-600">Reportes de meta por caso (promedio)</p>
                <p class="mt-1 text-2xl font-black text-slate-950">{{ $s['avg_goal_reports_per_case'] ?? 'Sin dato' }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4">
                <p class="text-xs font-bold text-slate-600">De esos reportes, hechos por el paciente</p>
                <p class="mt-1 text-2xl font-black text-slate-950">{{ $s['patient_report_share_pct'] === null ? 'Sin dato' : number_format($s['patient_report_share_pct'], 1, ',', '.').'%' }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4">
                <p class="text-xs font-bold text-slate-600">Tiempo promedio de respuesta a solicitudes</p>
                <p class="mt-1 text-2xl font-black text-slate-950">{{ $s['avg_support_response_hours'] === null ? 'Sin dato' : number_format($s['avg_support_response_hours'], 1, ',', '.').' h' }}</p>
            </article>
        </section>

        <section>
            <h3 class="font-bold text-slate-900 mb-3">Preparación de egreso</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['Pasaporte de recuperación confirmado', $s['passport_confirmed_pct']],
                    ['Casos con plan interdisciplinario', $s['care_plan_pct']],
                    ['Casos con medicamentos conciliados', $s['medication_reconciliation_pct']],
                    ['Preparación para el alta (promedio)', $s['discharge_readiness_avg_pct']],
                    ['Contenido educativo ya visto', $s['education_viewed_pct']],
                    ['Ruta del cuidador completada (promedio)', $s['caregiver_journey_avg_pct']],
                ] as [$label, $value])
                    <article class="rounded-xl border bg-white p-4">
                        <p class="text-xs font-bold text-slate-600">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-black text-slate-950">{{ $value === null ? 'Sin dato' : number_format($value, 1, ',', '.').'%' }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
