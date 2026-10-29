<x-filament-panels::page>
    @php($s = $this->summary)
    <div class="mx-auto w-full max-w-6xl space-y-6">
        <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm font-semibold text-red-950">
            Esta plataforma apoya la gestión, trazabilidad y evaluación del Programa ICU Liberation. No sustituye el juicio clínico, la valoración individual, las guías vigentes ni los protocolos institucionales aprobados.
        </div>
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div><h2 class="text-xl font-bold text-slate-950">Centro de Excelencia ICU Liberation</h2><p class="text-sm text-slate-700">Bundle ABCDEF, recuperación del paciente crítico y prevención del síndrome post-UCI.</p></div>
            <div class="flex gap-2">
                <a href="{{ url('/icu-liberation/censo') }}" class="rounded-lg border bg-white px-3 py-2 text-sm font-bold text-slate-900">Censo UCI</a>
                <a href="{{ url('/icu-liberation/ronda') }}" class="rounded-lg border bg-white px-3 py-2 text-sm font-bold text-slate-900">Ronda</a>
                <a href="{{ url('/icu-liberation/indicadores') }}" class="rounded-lg border bg-white px-3 py-2 text-sm font-bold text-slate-900">Indicadores</a>
                <a href="{{ url('/icu-liberation/icu-stays') }}" class="rounded-lg bg-[#0e7490] px-3 py-2 text-sm font-bold text-white">Estancias</a>
            </div>
        </header>
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([['Estancias activas',$s['active_stays']],['Ventilados',$s['ventilated']],['Con restricción física activa',$s['restraints_active']],['Estancias del año',$s['stays']]] as [$label,$value])
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-sm font-semibold text-slate-700">{{ $label }}</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $value }}</p></article>
            @endforeach
            <article @class([
                'rounded-xl border p-4 shadow-sm',
                'border-red-300 bg-red-50' => $s['pics_followups_overdue'] > 0,
                'border-slate-200 bg-white' => $s['pics_followups_overdue'] === 0,
            ])>
                <p class="text-sm font-semibold text-slate-700">Seguimiento PICS vencido</p>
                <p @class(['mt-2 text-3xl font-black', 'text-red-700' => $s['pics_followups_overdue'] > 0, 'text-slate-950' => $s['pics_followups_overdue'] === 0])>{{ $s['pics_followups_overdue'] }}</p>
            </article>
        </section>
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([['Cumplimiento del bundle ABCDEF',$s['bundle_compliance_pct'],'%'],['Dolor evaluado',$s['pain_assessment_pct'],'%'],['SAT realizado (ventilados)',$s['sat_realized_pct'],'%'],['SBT realizado (ventilados)',$s['sbt_realized_pct'],'%'],['Evaluación de delirium',$s['delirium_assessment_pct'],'%'],['Prevalencia de delirium',$s['delirium_prevalence_pct'],'%'],['Movilidad realizada',$s['mobility_realized_pct'],'%'],['Seguimiento post-UCI iniciado',$s['pics_followup_rate_pct'],'%']] as [$label,$value,$unit])
                <article class="rounded-xl border bg-white p-4"><p class="text-xs font-bold text-slate-600">{{ $label }}</p><p class="mt-1 text-2xl font-black text-slate-950">{{ $value === null ? 'Sin dato' : number_format($value,1,',','.').$unit }}</p></article>
            @endforeach
        </section>
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([['Días de ventilación (mediana)',$s['ventilation_days_median'],' d'],['Estancia UCI (mediana)',$s['icu_los_median_days'],' d'],['Mortalidad en UCI',$s['icu_mortality_pct'],'%'],['Mortalidad hospitalaria',$s['hospital_mortality_pct'],'%'],['Reintubación',$s['reintubation_pct'],'%'],['Autoextubación',$s['unplanned_extubation_pct'],'%']] as [$label,$value,$unit])
                <article class="rounded-xl border bg-white p-4"><p class="text-xs font-bold text-slate-600">{{ $label }}</p><p class="mt-1 text-2xl font-black text-slate-950">{{ $value === null ? 'Sin dato' : number_format($value,1,',','.').$unit }}</p></article>
            @endforeach
        </section>
    </div>
</x-filament-panels::page>
