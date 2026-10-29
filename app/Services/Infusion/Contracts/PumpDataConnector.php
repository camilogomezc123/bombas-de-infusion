<?php

namespace App\Services\Infusion\Contracts;

use App\Services\Infusion\PumpEventData;
use Carbon\CarbonInterface;

/**
 * Punto de extensión para conectar con la plataforma real del fabricante de bombas
 * (API REST, feed HL7, o lectura directa de su base de datos). Mientras ese mecanismo
 * no esté confirmado, el binding por defecto apunta a FixturePumpConnector.
 */
interface PumpDataConnector
{
    /**
     * @return iterable<int, PumpEventData>
     */
    public function fetchEvents(CarbonInterface $since): iterable;
}
