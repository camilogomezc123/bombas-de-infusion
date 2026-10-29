<x-filament-panels::page>
    <p class="text-sm text-slate-700">
        Gobierno de seguridad de medicación: quién anuló o retrocedió un límite del fabricante, o programó fuera de la
        librería de dosis segura, y con qué frecuencia — el patrón por operador, no solo la alerta puntual.
    </p>

    <div class="mt-4 overflow-hidden rounded-xl border bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Operador</th>
                    <th class="px-4 py-3">Eventos totales</th>
                    <th class="px-4 py-3">Límites duros anulados</th>
                    <th class="px-4 py-3">Bypass de librería</th>
                    <th class="px-4 py-3">Fármacos involucrados</th>
                    <th class="px-4 py-3">Última ocurrencia</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $row['operator'] }}</td>
                        <td class="px-4 py-3 font-bold text-slate-900">{{ $row['total'] }}</td>
                        <td class="px-4 py-3">
                            @if ($row['hard_overrides'] > 0)
                                <span class="rounded-full px-2 py-0.5 text-xs font-bold" style="background:#d03b3b22; color:#d03b3b">{{ $row['hard_overrides'] }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($row['library_bypasses'] > 0)
                                <span class="rounded-full px-2 py-0.5 text-xs font-bold" style="background:#fab21922; color:#b8860b">{{ $row['library_bypasses'] }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $row['drugs'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ \Illuminate\Support\Carbon::parse($row['last_occurred_at'])->format('d-M-Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">No hay anulaciones ni bypass de librería registrados todavía.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
