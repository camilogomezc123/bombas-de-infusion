<x-filament-panels::page>
    <div class="mx-auto w-full max-w-6xl space-y-5">
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950"><strong>Ruta de apoyo.</strong> No representa una orden médica, no calcula elegibilidad automáticamente y no sustituye los protocolos institucionales vigentes.</div>
        <div class="grid gap-3 md:grid-cols-2">
            @foreach($this->steps() as $index => [$title,$time,$objective,$responsible])
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><div class="flex gap-3"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyan-800 font-black text-white">{{ $index + 1 }}</span><div><h3 class="font-bold text-slate-950">{{ $title }}</h3><p class="text-xs font-semibold text-cyan-800">{{ $time }}</p><p class="mt-2 text-sm text-slate-700">{{ $objective }}</p><p class="mt-2 text-xs text-slate-600"><strong>Responsable:</strong> {{ $responsible }}</p></div></div></article>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
