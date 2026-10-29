<?php

namespace App\Support;

use App\Enums\CaseStatus;
use App\Models\AcsCase;
use App\Models\AcvCase;
use App\Models\IcuStay;
use App\Models\PicsCase;
use App\Models\SepsisCase;
use App\Models\TepCase;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class HubMetrics
{
    private const CACHE_MINUTES = 10;

    /**
     * @return array<string, array{total: int, hospitalized: int, current_month: int}>
     */
    public static function get(): array
    {
        $currentMonth = CarbonImmutable::now()->format('Y/m');

        return Cache::remember(
            self::cacheKey($currentMonth),
            now()->addMinutes(self::CACHE_MINUTES),
            fn (): array => [
                'acv' => self::caseStats(AcvCase::query(), $currentMonth),
                'sepsis' => self::caseStats(SepsisCase::query(), $currentMonth),
                'infarto' => self::acsStats(CarbonImmutable::now()),
                'tep' => self::tepStats(CarbonImmutable::now()),
                'icu_liberation' => self::icuStats(CarbonImmutable::now()),
                'pics' => self::picsStats(CarbonImmutable::now()),
            ],
        );
    }

    public static function forget(): void
    {
        Cache::forget(self::cacheKey(CarbonImmutable::now()->format('Y/m')));
    }

    /**
     * @return array{total: int, hospitalized: int, current_month: int}
     */
    private static function caseStats(Builder $query, string $currentMonth): array
    {
        $stats = $query
            ->where('is_cancelled', false)
            ->selectRaw(
                'COUNT(*) AS total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS hospitalized,
                SUM(CASE WHEN month = ? THEN 1 ELSE 0 END) AS current_month',
                [CaseStatus::Hospitalized->value, $currentMonth],
            )
            ->first();

        return [
            'total' => (int) $stats->total,
            'hospitalized' => (int) $stats->hospitalized,
            'current_month' => (int) $stats->current_month,
        ];
    }

    /**
     * @return array{total: int, hospitalized: int, current_month: int}
     */
    private static function acsStats(CarbonImmutable $today): array
    {
        $stats = AcsCase::query()
            ->where('is_cancelled', false)
            ->selectRaw(
                'COUNT(*) AS total,
                SUM(CASE WHEN discharged_at IS NULL AND death_at IS NULL THEN 1 ELSE 0 END) AS hospitalized,
                SUM(CASE WHEN admission_at >= ? AND admission_at <= ? THEN 1 ELSE 0 END) AS current_month',
                [$today->startOfMonth(), $today->endOfMonth()],
            )
            ->first();

        return [
            'total' => (int) $stats->total,
            'hospitalized' => (int) $stats->hospitalized,
            'current_month' => (int) $stats->current_month,
        ];
    }

    private static function tepStats(CarbonImmutable $today): array
    {
        $stats = TepCase::query()
            ->where('is_cancelled', false)
            ->selectRaw(
                'COUNT(*) AS total,
                SUM(CASE WHEN discharged_at IS NULL AND death_at IS NULL THEN 1 ELSE 0 END) AS hospitalized,
                SUM(CASE WHEN admission_at >= ? AND admission_at <= ? THEN 1 ELSE 0 END) AS current_month',
                [$today->startOfMonth(), $today->endOfMonth()],
            )->first();

        return ['total' => (int) $stats->total, 'hospitalized' => (int) $stats->hospitalized, 'current_month' => (int) $stats->current_month];
    }

    private static function icuStats(CarbonImmutable $today): array
    {
        $stats = IcuStay::query()
            ->where('is_cancelled', false)
            ->selectRaw(
                'COUNT(*) AS total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS hospitalized,
                SUM(CASE WHEN admission_at >= ? AND admission_at <= ? THEN 1 ELSE 0 END) AS current_month',
                ['active', $today->startOfMonth(), $today->endOfMonth()],
            )->first();

        return ['total' => (int) $stats->total, 'hospitalized' => (int) $stats->hospitalized, 'current_month' => (int) $stats->current_month];
    }

    /**
     * @return array{total: int, hospitalized: int, current_month: int}
     */
    private static function picsStats(CarbonImmutable $today): array
    {
        $stats = PicsCase::query()
            ->where('is_cancelled', false)
            ->selectRaw(
                "COUNT(*) AS total,
                SUM(CASE WHEN status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) AS hospitalized,
                SUM(CASE WHEN enrollment_at >= ? AND enrollment_at <= ? THEN 1 ELSE 0 END) AS current_month",
                [$today->startOfMonth(), $today->endOfMonth()],
            )->first();

        return ['total' => (int) $stats->total, 'hospitalized' => (int) $stats->hospitalized, 'current_month' => (int) $stats->current_month];
    }

    private static function cacheKey(string $month): string
    {
        return "hub.metrics.{$month}";
    }
}
