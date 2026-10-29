<x-filament-panels::page>
    @php($m=$this->metrics['current'] ?? [])
    <div class="mx-auto w-full max-w-6xl space-y-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-black text-slate-950">Tablero PICS</h2>
                <p class="text-sm text-slate-700">Resultados descriptivos sobre casos válidos; no estima riesgo individual ni sustituye la valoración clínica.</p>
            </div>
            <label class="text-sm font-bold text-slate-700">Año <input type="number" wire:model.live="year" class="ml-2 w-24 rounded-lg border-slate-300"></label>
        </div>
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($this->goals() as $key=>$goal)
                @php($value=$m[$key]??null)
                <article class="rounded-xl border bg-white p-4">
                    <p class="text-sm font-bold text-slate-700">{{ $goal['label'] }}</p>
                    <p class="mt-2 text-3xl font-black text-slate-950">{{ $value===null?'Sin dato':number_format($value,1,',','.').'%' }}</p>
                    @if($goal['target'] === null)
                        <p class="mt-1 text-xs text-slate-500">Sin meta institucional asignada todavía.</p>
                    @endif
                </article>
            @endforeach
        </section>
        <section class="rounded-xl border bg-white p-4">
            <h3 class="font-bold text-slate-900">Indicadores complementarios</h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-4">
                <div><p class="text-xs text-slate-600">Contacto logrado</p><p class="text-xl font-black">{{ ($m['contact_achieved_pct']??null)===null?'Sin dato':number_format($m['contact_achieved_pct'],1,',','.').'%' }}</p></div>
                <div><p class="text-xs text-slate-600">Reingreso</p><p class="text-xl font-black">{{ ($m['readmission_pct']??null)===null?'Sin dato':number_format($m['readmission_pct'],1,',','.').'%' }}</p></div>
                <div><p class="text-xs text-slate-600">Retorno laboral</p><p class="text-xl font-black">{{ ($m['return_to_work_pct']??null)===null?'Sin dato':number_format($m['return_to_work_pct'],1,',','.').'%' }}</p></div>
                <div><p class="text-xs text-slate-600">Casos analizados</p><p class="text-xl font-black">{{ $m['total'] ?? 0 }}</p></div>
            </div>
        </section>
        <a href="{{ \App\Filament\Pics\Pages\PortalEngagement::getUrl() }}" class="block rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-900 hover:bg-teal-100">
            <strong>Trazabilidad del portal →</strong> estos indicadores miden los instrumentos clínicos PICS. Para ver cómo se están comportando el paciente y la familia en <code>/portal</code> (ingresos, diario, metas, "Cómo me siento", solicitudes de ayuda), entra al módulo de trazabilidad del portal.
        </a>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
            La ficha técnica completa (numerador, denominador, exclusiones, meta, método de validación) de estos y de los demás indicadores catalogados del programa está disponible en <strong>Programa → Ficha técnica de indicadores</strong>.
        </div>
    </div>
</x-filament-panels::page>
