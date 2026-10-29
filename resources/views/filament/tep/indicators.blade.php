<x-filament-panels::page>
    @php($m=$this->metrics)
    <div class="mx-auto w-full max-w-6xl space-y-5">
        <div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-xl font-black text-slate-950">Tablero TEP</h2><p class="text-sm text-slate-700">Resultados descriptivos sobre casos válidos; no estima riesgo individual.</p></div><label class="text-sm font-bold text-slate-700">Año <input type="number" wire:model.live="year" class="ml-2 w-24 rounded-lg border-slate-300"></label></div>
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($this->goals() as $key=>$goal)
                @php($value=$m[$key]??null)
                <article class="rounded-xl border bg-white p-4"><p class="text-sm font-bold text-slate-700">{{ $goal['label'] }}</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $value===null?'Sin dato':number_format($value,1,',','.').'%' }}</p>@if($goal['target']!==null)<p class="mt-1 text-xs text-slate-600">Meta: {{ number_format($goal['target'],0) }}%</p>@endif</article>
            @endforeach
        </section>
        <section class="rounded-xl border bg-white p-4"><h3 class="font-bold text-slate-900">Indicadores complementarios</h3><div class="mt-3 grid gap-3 sm:grid-cols-3"><div><p class="text-xs text-slate-600">Diagnóstico a anticoagulación</p><p class="text-xl font-black">{{ $m['median_diagnosis_anticoagulation']===null?'Sin dato':number_format($m['median_diagnosis_anticoagulation'],1,',','.').' min' }}</p></div><div><p class="text-xs text-slate-600">Sangrado mayor</p><p class="text-xl font-black">{{ $m['major_bleeding_pct']===null?'Sin dato':number_format($m['major_bleeding_pct'],1,',','.').'%' }}</p></div><div><p class="text-xs text-slate-600">Recurrencia 90 días</p><p class="text-xl font-black">{{ $m['recurrence_90d_pct']===null?'Sin dato':number_format($m['recurrence_90d_pct'],1,',','.').'%' }}</p></div></div></section>
    </div>
</x-filament-panels::page>
