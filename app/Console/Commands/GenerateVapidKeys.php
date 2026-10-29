<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * Genera el par de llaves VAPID que necesitan las notificaciones push del portal —
 * un comando de configuración inicial, se corre una sola vez por entorno (local,
 * staging, producción) y las llaves se pegan en .env.
 */
class GenerateVapidKeys extends Command
{
    protected $signature = 'agora:generate-vapid-keys';

    protected $description = 'Genera un par de llaves VAPID nuevas para las notificaciones push del portal.';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->info('Agrega estas líneas a tu .env:');
        $this->line('');
        $this->line("VAPID_PUBLIC_KEY={$keys['publicKey']}");
        $this->line("VAPID_PRIVATE_KEY={$keys['privateKey']}");

        return self::SUCCESS;
    }
}
