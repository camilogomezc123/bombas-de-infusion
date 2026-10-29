<x-filament-panels::page>
    @php($m = $this->metrics)
    <div class="mx-auto w-full max-w-5xl space-y-5">
        <p class="text-sm text-slate-700">Indicadores de estado actual (tiempo real), no un corte anual — el módulo es reciente y aún no acumula histórico suficiente para comparar períodos.</p>
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <article class="rounded-xl border bg-white p-4">
                <p class="text-sm font-bold text-slate-700">Anulación/retroceso de límites duros</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $m['hard_limit_override_rate_pct'] === null ? 'Sin dato' : number_format($m['hard_limit_override_rate_pct'], 1, ',', '.').'%' }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4">
                <p class="text-sm font-bold text-slate-700">Score VIS promedio (estancias activas)</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $m['avg_vis_score'] ?? 'Sin dato' }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4">
                <p class="text-sm font-bold text-slate-700">Alertas de riesgo PIS abiertas</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $m['open_pis_alerts'] }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4">
                <p class="text-sm font-bold text-slate-700">Eventos sin paciente asociado</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $m['unresolved_events_pct'] === null ? 'Sin dato' : number_format($m['unresolved_events_pct'], 1, ',', '.').'%' }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4">
                <p class="text-sm font-bold text-slate-700">Alertas críticas abiertas</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $m['open_critical_alerts'] }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4">
                <p class="text-sm font-bold text-slate-700">Eventos totales registrados</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $m['total_events'] }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4">
                <p class="text-sm font-bold text-slate-700">Cumplimiento de librería de dosis</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $m['library_compliance_pct'] === null ? 'Sin dato' : number_format($m['library_compliance_pct'], 1, ',', '.').'%' }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4">
                <p class="text-sm font-bold text-slate-700">Interrupciones de antibiótico (código sepsis)</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $m['open_antibiotic_interruptions'] }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4">
                <p class="text-sm font-bold text-slate-700">Opioide sin dolor evaluado (ABCDEF)</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $m['open_pain_assessment_gaps'] }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4">
                <p class="text-sm font-bold text-slate-700">Duplicidad terapéutica</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $m['open_therapeutic_duplications'] }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4">
                <p class="text-sm font-bold text-slate-700">Doble verificación (alto riesgo)</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $m['co_sign_compliance_pct'] === null ? 'Sin dato' : number_format($m['co_sign_compliance_pct'], 1, ',', '.').'%' }}</p>
            </article>
        </section>
    </div>
</x-filament-panels::page>
