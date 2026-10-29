<x-filament-panels::page>
    @php($s = $this->summary)
    <div class="mx-auto w-full max-w-6xl space-y-6">
        <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm font-semibold text-red-950">
            Esta plataforma apoya la gestión, trazabilidad y evaluación del Programa TEP. No diagnostica, prescribe, clasifica automáticamente ni sustituye el juicio clínico, la valoración individual o los protocolos institucionales aprobados.
        </div>
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div><h2 class="text-xl font-bold text-slate-950">Centro de Excelencia TEP</h2><p class="text-sm text-slate-700">Tromboembolismo pulmonar agudo y seguimiento post-TEP.</p></div>
            <div class="flex gap-2"><a href="{{ url('/tep/indicadores') }}" class="rounded-lg border bg-white px-3 py-2 text-sm font-bold text-slate-900">Indicadores</a><a href="{{ url('/tep/tep-cases') }}" class="rounded-lg bg-[#0f5c5c] px-3 py-2 text-sm font-bold text-white">Casos</a></div>
        </header>
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([['Casos registrados',$s['cases']],['TEP confirmados',$s['confirmed_cases']],['Casos en proceso',$s['in_process']],['PERT por resolver',$s['pending_pert']]] as [$label,$value])
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-sm font-semibold text-slate-700">{{ $label }}</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $value }}</p></article>
            @endforeach
        </section>
        <section>
            <h3 class="mb-3 text-sm font-bold text-slate-800">Distribución por categoría documentada</h3>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">@foreach($s['categories'] as $category=>$count)<article class="rounded-xl border bg-teal-50 p-4 text-center"><p class="text-sm font-bold text-teal-900">Categoría {{ $category }}</p><p class="text-2xl font-black text-slate-950">{{ $count }}</p></article>@endforeach</div>
        </section>
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([['Pretest documentado',$s['pretest_documented_pct'],'%'],['Categoría A-E completa',$s['category_documented_pct'],'%'],['PERT C-E',$s['pert_activation_pct'],'%'],['Anticoagulación documentada',$s['anticoagulation_documented_pct'],'%'],['Mediana diagnóstico-anticoagulación',$s['median_diagnosis_anticoagulation'],' min'],['Seguimiento 3 meses',$s['followup_3m_pct'],'%'],['Mortalidad hospitalaria',$s['hospital_mortality_pct'],'%'],['Síntomas persistentes',$s['persistent_symptoms_pct'],'%']] as [$label,$value,$unit])
                <article class="rounded-xl border bg-white p-4"><p class="text-xs font-bold text-slate-600">{{ $label }}</p><p class="mt-1 text-2xl font-black text-slate-950">{{ $value === null ? 'Sin dato' : number_format($value,1,',','.').$unit }}</p></article>
            @endforeach
        </section>
    </div>
</x-filament-panels::page>
