<x-filament-panels::page>
    <p class="text-sm text-slate-700">
        Agrupa las alertas del fabricante (DERS/PUMP) y las alarmas de dispositivo (oclusión, aire en línea, batería baja)
        por área y turno — para detectar patrones (ej. muchas anulaciones en un mismo turno), no solo para revisar alerta por alerta.
    </p>

    <div class="mt-4 overflow-hidden rounded-xl border bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Área</th>
                    <th class="px-4 py-3">Turno</th>
                    <th class="px-4 py-3">Categoría</th>
                    <th class="px-4 py-3">Eventos</th>
                    <th class="px-4 py-3">Distribución</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $row['care_area'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row['shift'] }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-bold" style="background:{{ $row['is_critical'] ? '#d03b3b22' : '#fab21922' }}; color:{{ $row['is_critical'] ? '#d03b3b' : '#b8860b' }}">
                                {{ $row['category'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-bold text-slate-900">{{ $row['count'] }}</td>
                        <td class="px-4 py-3">
                            <div class="h-2 w-40 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full" style="width:{{ $row['bar_pct'] }}%; background:{{ $row['is_critical'] ? '#d03b3b' : '#fab219' }}"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">No hay alertas ni alarmas de dispositivo registradas todavía.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
