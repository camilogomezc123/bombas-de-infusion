<?php

namespace App\Filament\IcuLiberation\Pages;

use App\Models\IcuLiberationRound as RoundModel;
use App\Models\IcuStay;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class LiberationRound extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Ronda ICU Liberation';

    protected static ?string $title = 'Ronda interdisciplinaria diaria';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'ronda';

    protected string $view = 'filament.icu-liberation.round';

    public ?int $stayId = null;

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    #[Computed]
    public function stayOptions(): array
    {
        return IcuStay::query()
            ->where('status', 'active')->where('is_cancelled', false)
            ->with('patient')
            ->orderBy('admission_at')
            ->get()
            ->mapWithKeys(fn (IcuStay $stay): array => [
                $stay->id => "{$stay->case_number} · ".($stay->patient?->full_name ?? 'Sin paciente'),
            ])
            ->all();
    }

    #[Computed]
    public function todaysRound(): ?RoundModel
    {
        if (! $this->stayId) {
            return null;
        }

        return RoundModel::query()
            ->where('icu_stay_id', $this->stayId)
            ->where('round_date', today())
            ->first();
    }

    #[Computed]
    public function stayHistory(): array
    {
        if (! $this->stayId) {
            return [];
        }

        return RoundModel::query()
            ->where('icu_stay_id', $this->stayId)
            ->orderByDesc('round_date')
            ->limit(10)
            ->get()
            ->all();
    }

    /**
     * Checklist de lo que falta hoy para la estancia seleccionada — convierte la ronda
     * en una herramienta que dirige la revisión en vez de solo registrar notas libres.
     *
     * @return array<string, array{label: string, applicable: bool, done: bool, override: ?string}>
     */
    #[Computed]
    public function todaysGaps(): array
    {
        if (! $this->stayId) {
            return [];
        }

        $stay = IcuStay::query()
            ->with(['painAssessments', 'sedationAssessments', 'deliriumAssessments', 'mobilitySessions', 'satTrials', 'sbtTrials'])
            ->find($this->stayId);

        return $stay?->bundleStatusForDate(today()) ?? [];
    }

    public function updatedStayId(): void
    {
        $this->form->fill($this->todaysRound()?->toArray() ?? []);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->columns(3)->components([
            Select::make('rass_sas_goal')->label('RASS/SAS objetivo')->options([
                '-5' => '-5', '-4' => '-4', '-3' => '-3', '-2' => '-2', '-1' => '-1',
                '0' => '0', '+1' => '+1', '+2' => '+2', '+3' => '+3', '+4' => '+4',
            ]),
            Select::make('rass_sas_actual')->label('RASS/SAS actual')->options([
                '-5' => '-5', '-4' => '-4', '-3' => '-3', '-2' => '-2', '-1' => '-1',
                '0' => '0', '+1' => '+1', '+2' => '+2', '+3' => '+3', '+4' => '+4',
            ]),
            Select::make('cam_icdsc_result')->label('CAM-ICU / ICDSC')->options([
                'positive' => 'Positivo', 'negative' => 'Negativo', 'unable' => 'No evaluable',
            ]),
            Textarea::make('analgesics_sedatives')->label('Analgésicos y sedantes recibidos')->columnSpanFull(),
            Textarea::make('barriers_to_wakefulness')->label('Barreras para la vigilia')->columnSpanFull(),
            Textarea::make('adjustment_plan')->label('Plan de ajuste')->columnSpanFull(),
            Textarea::make('pain_summary')->label('Dolor')->columnSpan(1),
            Textarea::make('sat_plan')->label('Plan SAT')->columnSpan(1),
            Textarea::make('sbt_plan')->label('Plan SBT')->columnSpan(1),
            Textarea::make('delirium_summary')->label('Delirium')->columnSpan(1),
            Textarea::make('mobility_goal_text')->label('Meta de movilidad')->columnSpan(1),
            Textarea::make('sleep_plan')->label('Sueño')->columnSpan(1),
            Toggle::make('restraints_reviewed')->label('Restricciones revisadas'),
            Toggle::make('nutrition_reviewed')->label('Nutrición revisada'),
            Toggle::make('devices_reviewed')->label('Dispositivos revisados'),
            Toggle::make('pics_risk_reviewed')->label('Riesgo de PICS revisado'),
            Textarea::make('family_engagement_notes')->label('Participación familiar')->columnSpanFull(),
            Textarea::make('transfer_plan')->label('Plan de traslado')->columnSpanFull(),
            Textarea::make('goals_of_day')->label('Metas del día')->columnSpanFull(),
        ]);
    }

    public function saveRound(): void
    {
        $this->validate();

        if (! $this->stayId) {
            return;
        }

        RoundModel::query()->updateOrCreate(
            ['icu_stay_id' => $this->stayId, 'round_date' => today()],
            [...$this->form->getState(), 'created_by' => auth()->id()],
        );

        Notification::make()->title('Ronda del día guardada')->success()->send();
        unset($this->stayHistory, $this->todaysRound);
    }
}
