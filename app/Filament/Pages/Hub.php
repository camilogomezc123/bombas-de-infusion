<?php

namespace App\Filament\Pages;

use App\Filament\Acv\Pages\AcvIndicators;
use App\Filament\IcuLiberation\Pages\ExecutiveSummary as IcuLiberationExecutiveSummary;
use App\Filament\Pics\Pages\ExecutiveSummary as PicsExecutiveSummary;
use App\Filament\Sepsis\Pages\ExecutiveSummary;
use App\Support\HubMetrics;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

class Hub extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Inicio';

    protected static ?string $title = 'Centros de Excelencia';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = '/';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.hub';

    /**
     * Resumen de los centros que se muestran como tarjetas en el hub.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function centers(): array
    {
        $metrics = HubMetrics::get();
        $acv = $metrics['acv'];
        $sepsis = $metrics['sepsis'];
        $infarto = $metrics['infarto'];
        $tep = $metrics['tep'];
        $icuLiberation = $metrics['icu_liberation'];
        $pics = $metrics['pics'];

        return [
            [
                'name' => 'ACV',
                'subtitle' => 'Ataque cerebrovascular',
                'icon' => 'heroicon-o-bolt',
                'available' => true,
                'url' => AcvIndicators::getUrl(panel: 'acv'),
                'stats' => [
                    ['label' => 'Total', 'value' => $acv['total']],
                    ['label' => 'Hospitalizados', 'value' => $acv['hospitalized']],
                    ['label' => 'Este mes', 'value' => $acv['current_month']],
                ],
            ],
            [
                'name' => 'Sepsis',
                'subtitle' => 'Sepsis y choque séptico',
                'icon' => 'heroicon-o-beaker',
                'available' => true,
                'url' => ExecutiveSummary::getUrl(panel: 'sepsis'),
                'stats' => [
                    ['label' => 'Total', 'value' => $sepsis['total']],
                    ['label' => 'Hospitalizados', 'value' => $sepsis['hospitalized']],
                    ['label' => 'Este mes', 'value' => $sepsis['current_month']],
                ],
            ],
            [
                'name' => 'Infarto',
                'subtitle' => 'Síndrome coronario agudo',
                'icon' => 'heroicon-o-heart',
                'available' => true,
                'url' => url('/infarto'),
                'stats' => [
                    ['label' => 'Total', 'value' => $infarto['total']],
                    ['label' => 'Hospitalizados', 'value' => $infarto['hospitalized']],
                    ['label' => 'Este mes', 'value' => $infarto['current_month']],
                ],
            ],
            [
                'name' => 'TEP',
                'subtitle' => 'Tromboembolismo pulmonar',
                'icon' => 'heroicon-o-shield-check',
                'available' => true,
                'url' => url('/tep'),
                'stats' => [
                    ['label' => 'Total', 'value' => $tep['total']],
                    ['label' => 'Hospitalizados', 'value' => $tep['hospitalized']],
                    ['label' => 'Este mes', 'value' => $tep['current_month']],
                ],
            ],
            [
                'name' => 'ICU Liberation',
                'subtitle' => 'Recuperación del paciente crítico',
                'icon' => 'heroicon-o-heart',
                'available' => true,
                'url' => IcuLiberationExecutiveSummary::getUrl(panel: 'icu-liberation'),
                'stats' => [
                    ['label' => 'Total', 'value' => $icuLiberation['total']],
                    ['label' => 'Activas', 'value' => $icuLiberation['hospitalized']],
                    ['label' => 'Este mes', 'value' => $icuLiberation['current_month']],
                ],
            ],
            [
                'name' => 'PICS',
                'subtitle' => 'Síndrome post cuidado intensivo',
                'icon' => 'heroicon-o-arrow-path-rounded-square',
                'available' => true,
                'url' => PicsExecutiveSummary::getUrl(panel: 'pics'),
                'stats' => [
                    ['label' => 'Total', 'value' => $pics['total']],
                    ['label' => 'Activos', 'value' => $pics['hospitalized']],
                    ['label' => 'Este mes', 'value' => $pics['current_month']],
                ],
            ],
            [
                'name' => 'Colon y Recto',
                'subtitle' => 'Cirugía colorrectal',
                'icon' => 'heroicon-o-clipboard-document-check',
                'available' => false,
            ],
        ];
    }
}
