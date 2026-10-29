<?php

namespace App\Filament\InfusionMonitoring\Pages;

use App\Models\InfusionEvent;
use App\Models\PumpDevice;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

/**
 * Agrega las alertas del fabricante (DERS/PUMP) y las alarmas de dispositivo
 * (oclusión/aire/batería) por área y turno — el "Alarm Insight" que una central tipo
 * BD HealthSight ya ofrece para detectar patrones (ej. muchas anulaciones de un mismo
 * tipo en el turno de noche), en vez de ver cada alerta solo una por una.
 */
class AlarmInsights extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?string $navigationLabel = 'Alarmas por turno';

    protected static ?string $title = 'Panel de alarmas por área y turno';

    protected static ?int $navigationSort = 21;

    protected static ?string $slug = 'alarmas';

    protected string $view = 'filament.infusion-monitoring.alarm-insights';

    #[Computed]
    public function rows(): array
    {
        $deviceAlarmStatuses = ['occlusion', 'air_in_line', 'battery_low'];

        $rows = InfusionEvent::query()
            ->where(function ($query) use ($deviceAlarmStatuses) {
                $query->whereNotNull('alert_level')->orWhereIn('device_status', $deviceAlarmStatuses);
            })
            ->get()
            ->map(function (InfusionEvent $event) use ($deviceAlarmStatuses): array {
                $hour = (int) $event->occurred_at->format('H');
                $isDeviceAlarm = in_array($event->device_status, $deviceAlarmStatuses, true);

                return [
                    'care_area' => $event->care_area ?? 'Sin área',
                    'shift' => ($hour >= 7 && $hour < 19) ? 'Día (07-19h)' : 'Noche (19-07h)',
                    'category' => $isDeviceAlarm ? (PumpDevice::STATUSES[$event->device_status] ?? $event->device_status) : ($event->alert_level ?? 'Sin clasificar'),
                    'is_critical' => $event->isHardLimitOverride() || in_array($event->device_status, ['occlusion', 'air_in_line'], true),
                ];
            })
            ->groupBy(fn (array $row) => $row['care_area'].'|'.$row['shift'].'|'.$row['category'])
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'care_area' => $first['care_area'],
                    'shift' => $first['shift'],
                    'category' => $first['category'],
                    'is_critical' => $first['is_critical'],
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->values();

        $maxCount = (int) $rows->max('count');

        return $rows->map(fn (array $row): array => [...$row, 'bar_pct' => $maxCount > 0 ? round($row['count'] / $maxCount * 100, 1) : 0])->all();
    }
}
