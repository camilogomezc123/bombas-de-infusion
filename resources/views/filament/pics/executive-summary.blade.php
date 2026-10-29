<x-filament-panels::page>
    @php($s = $this->summary)
    <div class="mx-auto w-full max-w-6xl space-y-6">
        <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm font-semibold text-red-950">
            Esta plataforma apoya la gestión, trazabilidad y evaluación del Programa PICS. No sustituye el juicio clínico, la valoración individual, las guías vigentes ni los protocolos institucionales aprobados.
        </div>
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-950">Centro de Seguimiento PICS</h2>
                <p class="text-sm text-slate-700">Síndrome post cuidado intensivo: seguimiento físico, cognitivo y psicológico de sobrevivientes de UCI.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ url('/pics/indicadores') }}" class="rounded-lg border bg-white px-3 py-2 text-sm font-bold text-slate-900">Indicadores</a>
                <a href="{{ url('/pics/pics-cases') }}" class="rounded-lg bg-[#0e7490] px-3 py-2 text-sm font-bold text-white">Casos</a>
            </div>
        </header>
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([['Casos activos',$s['open_cases']],['Casos del año',$s['total']],['Seguimientos registrados',$s['followups_total']],['Remisiones registradas',$s['referrals_total']]] as [$label,$value])
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-sm font-semibold text-slate-700">{{ $label }}</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $value }}</p></article>
            @endforeach
        </section>
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([['Seguimiento post-UCI iniciado',$s['followup_rate_pct']],['Contacto logrado',$s['contact_achieved_pct']],['Tamizaje positivo para PICS',$s['screening_positive_pct']],['Remisiones completadas',$s['referral_completion_pct']],['Reingreso',$s['readmission_pct']],['Retorno laboral',$s['return_to_work_pct']]] as [$label,$value])
                <article class="rounded-xl border bg-white p-4"><p class="text-xs font-bold text-slate-600">{{ $label }}</p><p class="mt-1 text-2xl font-black text-slate-950">{{ $value === null ? 'Sin dato' : number_format($value,1,',','.').'%' }}</p></article>
            @endforeach
        </section>
    </div>
</x-filament-panels::page>
