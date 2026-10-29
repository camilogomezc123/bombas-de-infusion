<x-filament-panels::page>
    @php($s = $this->summary)
    <div class="mx-auto w-full max-w-6xl space-y-6">
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-950">
            Esta plataforma apoya la gestión, trazabilidad y evaluación del Programa de Síndrome Coronario Agudo. No sustituye el juicio clínico, la valoración individual, las guías vigentes ni los protocolos institucionales aprobados.
        </div>
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div><h2 class="text-xl font-bold text-slate-950">Centro de Excelencia de Infarto</h2><p class="text-sm text-slate-600">Síndrome coronario agudo e infarto agudo de miocardio.</p></div>
            <div class="flex gap-2"><a href="{{ url('/infarto/indicadores') }}" class="rounded-lg border bg-white px-3 py-2 text-sm font-bold">Indicadores</a><a href="{{ url('/infarto/acs-cases') }}" class="rounded-lg bg-[#17375e] px-3 py-2 text-sm font-bold text-white">Casos</a></div>
        </header>
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['Casos SCA',$s['cases'],'#eff6ff'], ['STEMI',$s['stemi_cases'],'#fef2f2'],
                ['NSTEMI',$s['nstemi_cases'],'#f5f3ff'], ['Auditorías pendientes',$s['pending_audits'],'#fffbeb']
            ] as [$label,$value,$bg])
                <article class="rounded-xl border border-slate-200 p-4 shadow-sm" style="background:{{ $bg }}"><p class="text-sm font-semibold text-slate-700">{{ $label }}</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $value }}</p></article>
            @endforeach
        </section>
        <section><h3 class="mb-3 text-sm font-bold text-slate-800">Tiempos y resultados</h3><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['ECG ≤10 min',$s['ecg_10_pct'],'%'],['FMC-dispositivo directo ≤90',$s['fmc_device_direct_90_pct'],'%'],
                ['FMC-dispositivo traslado ≤120',$s['fmc_device_transfer_120_pct'],'%'],['Mortalidad hospitalaria',$s['hospital_mortality_pct'],'%'],
                ['Mediana FMC-dispositivo',$s['median_fmc_device'],' min'],['Remisión a rehabilitación',$s['rehab_referral_pct'],'%'],
                ['MACE 30 días',$s['mace_30d_pct'],'%'],['Registros incompletos',$s['incomplete_cases'],'']
            ] as [$label,$value,$unit])
                <article class="rounded-xl border bg-white p-4"><p class="text-xs font-bold text-slate-600">{{ $label }}</p><p class="mt-1 text-2xl font-black">{{ $value === null ? 'Sin dato' : number_format($value,1,',','.').$unit }}</p></article>
            @endforeach
        </div></section>
    </div>
</x-filament-panels::page>
