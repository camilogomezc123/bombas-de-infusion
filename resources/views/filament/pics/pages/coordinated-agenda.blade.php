<x-filament-panels::page>
    @php($grouped = $this->groupedAgenda)
    <div class="mx-auto w-full max-w-5xl space-y-5">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            Lista cronológica de remisiones y tareas/citas/recordatorios internos. La edición completa de cada elemento se hace desde el caso correspondiente.
        </div>

        <form class="flex flex-wrap items-end gap-4 rounded-xl border bg-white p-4">
            <div>
                <label class="text-xs font-semibold text-slate-600">Ventana (días)</label>
                <input type="number" min="1" max="90" wire:model.live="days" class="mt-1 block w-24 rounded-md border-slate-300 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model.live="includeOverdue" class="rounded border-slate-300">
                Incluir vencidos
            </label>
        </form>

        @forelse ($grouped as $date => $rows)
            <section class="rounded-xl border bg-white p-4">
                <h3 class="font-bold text-slate-900 mb-3">{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l d \d\e F') }}</h3>
                <ul class="divide-y divide-slate-100">
                    @foreach ($rows as $row)
                        <li class="py-2 flex items-center justify-between gap-3">
                            <div>
                                <span class="text-xs font-semibold uppercase text-slate-500">{{ $row['when']->format('H:i') }}</span>
                                <span class="ml-2 font-medium text-slate-900">{{ $row['title'] }}</span>
                                <span class="ml-2 text-sm text-slate-500">
                                    {{ $row['case']?->case_number }} · {{ $row['case']?->patient?->full_name }}
                                    @if ($row['responsible'])
                                        · {{ $row['responsible'] }}
                                    @endif
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-amber-100 text-amber-800' => $row['status_color'] === 'warning',
                                    'bg-slate-100 text-slate-700' => $row['status_color'] === 'gray',
                                ])>{{ $row['status_label'] }}</span>
                                @if ($row['case'])
                                    <a href="{{ \App\Filament\Pics\Resources\PicsCases\PicsCaseResource::getUrl('view', ['record' => $row['case']], panel: 'pics') }}" class="text-sm text-teal-700 hover:underline">Ver caso</a>
                                @endif
                                @if ($row['type'] === 'agenda_item')
                                    <button type="button" wire:click="completeAgendaItem({{ $row['model']->id }})" class="text-sm text-emerald-700 hover:underline">Completar</button>
                                    @if (auth()->user()?->canManagePicsCases())
                                        <button type="button" wire:click="cancelAgendaItem({{ $row['model']->id }})" class="text-sm text-red-700 hover:underline">Cancelar</button>
                                    @endif
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <div class="rounded-xl border bg-white p-6 text-center text-slate-500">Nada programado en esta ventana.</div>
        @endforelse
    </div>
</x-filament-panels::page>
