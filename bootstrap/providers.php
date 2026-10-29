<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AcvPanelProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\IcuLiberationPanelProvider;
use App\Providers\Filament\InfartoPanelProvider;
use App\Providers\Filament\InfusionMonitoringPanelProvider;
use App\Providers\Filament\PicsPanelProvider;
use App\Providers\Filament\SepsisPanelProvider;
use App\Providers\Filament\TepPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    AcvPanelProvider::class,
    SepsisPanelProvider::class,
    InfartoPanelProvider::class,
    TepPanelProvider::class,
    IcuLiberationPanelProvider::class,
    PicsPanelProvider::class,
    InfusionMonitoringPanelProvider::class,
];
