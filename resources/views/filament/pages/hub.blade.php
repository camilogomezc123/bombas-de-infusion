<x-filament-panels::page>
    <div class="mb-2">
        <p class="text-sm font-medium" style="color: #334155">
            Seleccione el Centro de Excelencia que desea revisar. Los módulos en preparación se habilitarán próximamente.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->centers as $center)
            @php
                $base = 'group relative flex flex-col overflow-hidden rounded-2xl border p-6 transition';
                $tone = $center['available']
                    ? 'border-transparent bg-gradient-to-br from-[#0d2340] to-[#17375e] shadow-lg hover:-translate-y-1 hover:shadow-2xl'
                    : 'border-dashed border-gray-300 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900/60';
            @endphp

            @if ($center['available'])
                <a href="{{ $center['url'] }}" class="{{ $base }} {{ $tone }}">
            @else
                <div class="{{ $base }} {{ $tone }}" aria-disabled="true">
            @endif

                <div class="flex items-start justify-between">
                    <span @class([
                        'flex h-14 w-14 items-center justify-center rounded-xl',
                        'bg-white/15 text-white' => $center['available'],
                        'bg-slate-100 text-slate-500 dark:bg-gray-800' => ! $center['available'],
                    ])>
                        <x-filament::icon :icon="$center['icon']" class="h-7 w-7" />
                    </span>

                    @unless ($center['available'])
                        <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600 dark:bg-gray-800 dark:text-gray-300">
                            Próximamente
                        </span>
                    @endunless
                </div>

                <h2 @class([
                    'mt-5 text-2xl font-bold tracking-tight',
                    'text-white' => $center['available'],
                    'text-slate-800 dark:text-gray-100' => ! $center['available'],
                ])>{{ $center['name'] }}</h2>
                <p @class([
                    'text-sm',
                    'text-white/80' => $center['available'],
                    'text-slate-500 dark:text-gray-400' => ! $center['available'],
                ])>{{ $center['subtitle'] }}</p>

                @if ($center['available'])
                    <div class="mt-6 grid grid-cols-3 gap-3 border-t border-white/20 pt-4">
                        @foreach ($center['stats'] as $stat)
                            <div>
                                <div class="text-2xl font-bold text-white">{{ number_format($stat['value'], 0, ',', '.') }}</div>
                                <div class="text-xs uppercase tracking-wide text-white/75">{{ $stat['label'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 flex items-center gap-1.5 text-sm font-semibold text-white transition group-hover:gap-2.5">
                        Entrar
                        <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4" />
                    </div>
                @endif

            @if ($center['available'])
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>
</x-filament-panels::page>
