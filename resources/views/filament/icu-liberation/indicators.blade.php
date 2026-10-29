<x-filament-panels::page>
    @php($m=$this->metrics)
    <div class="mx-auto w-full max-w-6xl space-y-5">
        <div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-xl font-black text-slate-950">Tablero ICU Liberation</h2><p class="text-sm text-slate-700">Resultados descriptivos sobre estancias válidas; no estima riesgo individual ni sustituye la auditoría clínica.</p></div><label class="text-sm font-bold text-slate-700">Año <input type="number" wire:model.live="year" class="ml-2 w-24 rounded-lg border-slate-300"></label></div>
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($this->goals() as $key=>$goal)
                @php($value=$m[$key]??null)
                <article class="rounded-xl border bg-white p-4"><p class="text-sm font-bold text-slate-700">{{ $goal['label'] }}</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $value===null?'Sin dato':(str_contains($key,'median')?number_format($value,1,',','.').' d':number_format($value,1,',','.').'%') }}</p></article>
            @endforeach
        </section>
        <section class="rounded-xl border bg-white p-4"><h3 class="font-bold text-slate-900">Indicadores complementarios</h3><div class="mt-3 grid gap-3 sm:grid-cols-3"><div><p class="text-xs text-slate-600">Familiar identificado</p><p class="text-xl font-black">{{ $m['family_identified_pct']===null?'Sin dato':number_format($m['family_identified_pct'],1,',','.').'%' }}</p></div><div><p class="text-xs text-slate-600">Objetivo de sedación documentado</p><p class="text-xl font-black">{{ $m['sedation_goal_documented_pct']===null?'Sin dato':number_format($m['sedation_goal_documented_pct'],1,',','.').'%' }}</p></div><div><p class="text-xs text-slate-600">Estancias analizadas</p><p class="text-xl font-black">{{ $m['stays'] }}</p></div></div></section>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
            La ficha técnica completa (numerador, denominador, exclusiones, meta, método de validación) de estos y de los demás indicadores catalogados del programa está disponible en <strong>Programa → Ficha técnica de indicadores</strong>.
        </div>
    </div>
</x-filament-panels::page>
