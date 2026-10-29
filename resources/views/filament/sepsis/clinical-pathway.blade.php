<x-filament-panels::page>
    <div class="mx-auto w-full max-w-5xl space-y-6">
        <p class="text-sm text-slate-600 dark:text-slate-300">
            Referencia institucional de la ruta del Código Sepsis. Cada paso enlaza al documento y al indicador
            relacionado ya existentes en el sistema — esta página no registra datos de pacientes, es material de consulta.
        </p>

        <section aria-labelledby="linea-tiempo">
            <h2 id="linea-tiempo" class="mb-3 text-sm font-bold text-[#17375e] dark:text-white">Línea de tiempo de hitos</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
                @foreach ($this->timeline() as $milestone)
                    <div class="rounded-xl border border-slate-200 bg-white p-3 text-center shadow-sm dark:border-white/10 dark:bg-white/5">
                        <p class="text-xs font-bold text-[#17375e] dark:text-white">{{ $milestone['label'] }}</p>
                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">{{ $milestone['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="ruta-pasos">
            <h2 id="ruta-pasos" class="mb-3 text-sm font-bold text-[#17375e] dark:text-white">Ruta clínica — 17 pasos</h2>
            <ol class="space-y-0">
                @foreach ($this->steps() as $index => $step)
                    <li class="relative flex gap-4 pb-6">
                        @if (! $loop->last)
                            <span class="absolute left-4 top-9 h-full w-px bg-slate-200 dark:bg-white/10"></span>
                        @endif
                        <span class="z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#17375e] text-xs font-bold text-white">
                            {{ $index + 1 }}
                        </span>
                        <div class="flex-1 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $step['title'] }}</h3>
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ $step['objective'] }}</p>
                            <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-xs sm:grid-cols-2">
                                <div><dt class="font-semibold text-slate-500">Responsable</dt><dd class="text-slate-700 dark:text-slate-200">{{ $step['responsible'] }}</dd></div>
                                <div><dt class="font-semibold text-slate-500">Tiempo esperado</dt><dd class="text-slate-700 dark:text-slate-200">{{ $step['time'] }}</dd></div>
                                <div><dt class="font-semibold text-slate-500">Requisitos</dt><dd class="text-slate-700 dark:text-slate-200">{{ $step['requirements'] }}</dd></div>
                                <div><dt class="font-semibold text-slate-500">Evidencia</dt><dd class="text-slate-700 dark:text-slate-200">{{ $step['evidence'] }}</dd></div>
                                <div><dt class="font-semibold text-slate-500">Indicador relacionado</dt><dd class="text-slate-700 dark:text-slate-200">{{ $step['indicator'] }}</dd></div>
                                <div><dt class="font-semibold text-slate-500">Documento relacionado</dt><dd class="text-slate-700 dark:text-slate-200">{{ $step['document'] }}</dd></div>
                                <div class="sm:col-span-2"><dt class="font-semibold text-amber-700 dark:text-amber-400">Riesgo asociado</dt><dd class="text-slate-700 dark:text-slate-200">{{ $step['risk'] }}</dd></div>
                            </dl>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
</x-filament-panels::page>
