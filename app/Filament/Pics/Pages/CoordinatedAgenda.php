<?php

namespace App\Filament\Pics\Pages;

use App\Models\PicsAgendaItem;
use App\Services\PicsAgendaService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

/**
 * Lista cronológica simple de remisiones y tareas/citas/recordatorios internos,
 * agrupada por fecha — no un calendario, porque no hay librería JS disponible en
 * este entorno (sin Node.js). Las ediciones completas se hacen desde el relation
 * manager de cada caso; esta página es de lectura + acciones rápidas.
 */
class CoordinatedAgenda extends Page
{
    protected string $view = 'filament.pics.pages.coordinated-agenda';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Agenda coordinada';

    protected static ?string $title = 'Agenda coordinada';

    protected static ?int $navigationSort = 86;

    protected static ?string $slug = 'agenda-coordinada';

    public int $days = 14;

    public bool $includeOverdue = true;

    #[Computed]
    public function groupedAgenda(): array
    {
        $rows = app(PicsAgendaService::class)->upcoming($this->days, $this->includeOverdue);

        return $rows->groupBy(fn (array $row) => $row['when']->format('Y-m-d'))->all();
    }

    public function completeAgendaItem(int $id): void
    {
        $item = PicsAgendaItem::query()->findOrFail($id);
        $item->update(['status' => 'completada', 'completed_by' => auth('web')->id(), 'completed_at' => now()]);
        unset($this->groupedAgenda);
        Notification::make()->success()->title('Marcado como completado')->send();
    }

    public function cancelAgendaItem(int $id): void
    {
        abort_unless(auth()->user()?->canManagePicsCases(), 403);

        $item = PicsAgendaItem::query()->findOrFail($id);
        $item->update(['status' => 'cancelada']);
        unset($this->groupedAgenda);
        Notification::make()->success()->title('Cancelado')->send();
    }
}
