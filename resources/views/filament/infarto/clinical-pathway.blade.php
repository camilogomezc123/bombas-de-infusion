<x-filament-panels::page>
    <div class="mx-auto max-w-5xl space-y-4">
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-950">Ruta institucional de referencia. Cada decisión requiere confirmación humana y aplicación del protocolo aprobado.</div>
        @foreach($this->steps() as $index => $step)
            <article class="grid gap-3 rounded-xl border bg-white p-4 shadow-sm md:grid-cols-[3rem_1fr_9rem]">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#17375e] font-black text-white">{{ $index + 1 }}</div>
                <div><h3 class="font-bold text-slate-950">{{ $step['title'] }}</h3><p class="text-sm text-slate-700">{{ $step['objective'] }}</p><p class="mt-2 text-xs text-slate-500"><b>Responsable:</b> {{ $step['responsible'] }} · <b>Evidencia:</b> {{ $step['evidence'] }} · <b>Riesgo:</b> {{ $step['risk'] }}</p></div>
                <div class="text-sm font-bold text-blue-900">{{ $step['time'] }}</div>
            </article>
        @endforeach
    </div>
</x-filament-panels::page>
