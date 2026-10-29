<?php

namespace App\Support;

use App\Filament\Acv\Pages\AcvIndicators;
use App\Filament\IcuLiberation\Pages\ExecutiveSummary as IcuLiberationExecutiveSummary;
use App\Filament\InfusionMonitoring\Pages\PumpCensus;
use App\Filament\Pics\Pages\ExecutiveSummary as PicsExecutiveSummary;
use App\Filament\Sepsis\Pages\ExecutiveSummary;

/**
 * Registro central de los Centros de Excelencia ACTIVOS.
 * Agrega aquí un nuevo centro y aparecerá en el selector de la cabecera.
 */
class ExcellenceCenters
{
    /**
     * @return array<int, array{key:string,name:string,subtitle:string,icon:string,panel:string,url:callable}>
     */
    public static function active(): array
    {
        return [
            [
                'key' => 'acv',
                'name' => 'ACV',
                'subtitle' => 'Ataque cerebrovascular',
                'icon' => 'heroicon-o-bolt',
                'panel' => 'acv',
                'url' => fn (): string => AcvIndicators::getUrl(panel: 'acv'),
            ],
            [
                'key' => 'sepsis',
                'name' => 'Sepsis',
                'subtitle' => 'Sepsis y choque séptico',
                'icon' => 'heroicon-o-beaker',
                'panel' => 'sepsis',
                'url' => fn (): string => ExecutiveSummary::getUrl(panel: 'sepsis'),
            ],
            [
                'key' => 'infarto',
                'name' => 'Infarto',
                'subtitle' => 'Síndrome coronario agudo',
                'icon' => 'heroicon-o-heart',
                'panel' => 'infarto',
                'url' => fn (): string => url('/infarto'),
            ],
            [
                'key' => 'tep',
                'name' => 'TEP',
                'subtitle' => 'Tromboembolismo pulmonar',
                'icon' => 'heroicon-o-shield-check',
                'panel' => 'tep',
                'url' => fn (): string => url('/tep'),
            ],
            [
                'key' => 'icu-liberation',
                'name' => 'ICU Liberation',
                'subtitle' => 'Recuperación del paciente crítico',
                'icon' => 'heroicon-o-heart',
                'panel' => 'icu-liberation',
                'url' => fn (): string => IcuLiberationExecutiveSummary::getUrl(panel: 'icu-liberation'),
            ],
            [
                'key' => 'pics',
                'name' => 'PICS',
                'subtitle' => 'Síndrome post cuidado intensivo',
                'icon' => 'heroicon-o-arrow-path-rounded-square',
                'panel' => 'pics',
                'url' => fn (): string => PicsExecutiveSummary::getUrl(panel: 'pics'),
            ],
            [
                'key' => 'infusion-monitoring',
                'name' => 'Monitoreo de Bombas',
                'subtitle' => 'Infusión, VIS y dosis acumulada',
                'icon' => 'heroicon-o-beaker',
                'panel' => 'infusion-monitoring',
                'url' => fn (): string => PumpCensus::getUrl(panel: 'infusion-monitoring'),
            ],
        ];
    }

    /**
     * Nombre del centro de un panel (para el letrero de cabecera).
     */
    public static function nameForPanel(string $panelId): ?string
    {
        foreach (self::active() as $c) {
            if ($c['panel'] === $panelId) {
                return $c['name'];
            }
        }

        return null;
    }

    /**
     * Letrero grande de cabecera que indica el centro actual (visible para todos).
     */
    public static function headerBadge(string $name): string
    {
        $center = collect(self::active())->firstWhere('name', $name);
        $url = $center ? ($center['url'])() : '#';

        return '<a class="agora-center-home-link" href="'.e($url).'" title="Ir a la vista general">'
            .'Centro de Excelencia <span aria-hidden="true">·</span> '.e($name).'</a>';
    }
}
