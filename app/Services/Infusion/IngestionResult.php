<?php

namespace App\Services\Infusion;

use Illuminate\Support\Collection;

readonly class IngestionResult
{
    /**
     * @param  Collection<int, \App\Models\InfusionEvent>  $events
     * @param  array<int, int>  $touchedIcuStayIds
     */
    public function __construct(
        public Collection $events,
        public array $touchedIcuStayIds,
    ) {}
}
