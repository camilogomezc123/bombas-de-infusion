<?php

namespace App\Services\Infusion\Connectors;

use App\Services\Infusion\Contracts\PumpDataConnector;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Conector real contra BD HealthSight / Alaris Systems Manager — pendiente de
 * implementar. Falta confirmar con el usuario si el acceso es vía API REST/HL7 o
 * lectura directa de la base de datos del servidor BD, y obtener el endpoint/
 * documentación o la cadena de conexión correspondiente. Una vez confirmado, esta
 * clase debe traducir la respuesta/filas nativas de BD a PumpEventData (ver
 * FixturePumpConnector para la forma esperada) y config('services.infusion_pumps.driver')
 * debe apuntar aquí.
 */
class BdHealthSightConnector implements PumpDataConnector
{
    public function fetchEvents(CarbonInterface $since): iterable
    {
        throw new RuntimeException(
            'Conector BD HealthSight no configurado todavía: falta confirmar si el acceso es '.
            'por API REST/HL7 o por base de datos, y los datos de conexión correspondientes. '.
            "Mientras tanto, config('services.infusion_pumps.driver') debe permanecer en 'fixture'."
        );
    }
}
