<?php

namespace App\Console\Commands;

use App\Services\Infusion\Contracts\PumpDataConnector;
use App\Services\Infusion\InfusionClinicalEngine;
use App\Services\Infusion\InfusionEventIngestionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

#[Signature('infusion:ingest-events')]
#[Description('Trae eventos nuevos de la plataforma de bombas de infusión y recalcula el motor clínico (VIS, dosis acumulada, alertas)')]
class IngestInfusionPumpEvents extends Command
{
    private const WATERMARK_CACHE_KEY = 'infusion_pumps:last_ingested_at';

    public function handle(PumpDataConnector $connector, InfusionEventIngestionService $ingestionService, InfusionClinicalEngine $engine): int
    {
        $since = Carbon::parse(Cache::get(self::WATERMARK_CACHE_KEY, now()->subDays(7)->toIso8601String()));

        $pumpEvents = $connector->fetchEvents($since);

        $result = $ingestionService->ingest($pumpEvents);

        if ($result->events->isEmpty()) {
            $this->info('Sin eventos nuevos.');

            return self::SUCCESS;
        }

        $engine->process($result);

        Cache::forever(self::WATERMARK_CACHE_KEY, now()->toIso8601String());

        $this->info(sprintf(
            '%d evento(s) ingresados, %d estancia(s) recalculada(s).',
            $result->events->count(),
            count($result->touchedIcuStayIds),
        ));

        return self::SUCCESS;
    }
}
