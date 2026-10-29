<?php

namespace App\Support\Posuci;

use App\Models\Caregiver;
use App\Models\Patient;
use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Envío de notificaciones push (Web Push / PWA) a un paciente o cuidador. Sin llaves
 * VAPID configuradas (VAPID_PUBLIC_KEY/VAPID_PRIVATE_KEY en .env) simplemente no
 * envía nada — así el resto del portal sigue funcionando en un entorno que todavía
 * no las tiene configuradas (como este de desarrollo, por defecto).
 */
class WebPushSender
{
    private ?WebPush $client = null;

    private bool $clientResolved = false;

    public function isConfigured(): bool
    {
        return $this->client() !== null;
    }

    /**
     * Manda la misma notificación a todas las suscripciones activas del actor (puede
     * tener varias: celular, computador, etc.). Las suscripciones que el navegador ya
     * invalidó (desinstaló la app, revocó el permiso) se borran automáticamente.
     */
    public function sendToActor(Patient|Caregiver $actor, string $title, string $body, string $url = '/portal'): int
    {
        $client = $this->client();

        if (! $client) {
            return 0;
        }

        $subscriptions = $actor->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $payload = json_encode(['title' => $title, 'body' => $body, 'url' => $url]);

        foreach ($subscriptions as $subscription) {
            $client->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding,
                ]),
                $payload,
            );
        }

        $sent = 0;

        foreach ($client->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;

                continue;
            }

            if ($report->isSubscriptionExpired()) {
                $endpoint = (string) $report->getRequest()->getUri();
                PushSubscription::query()->where('endpoint', $endpoint)->delete();
            }
        }

        return $sent;
    }

    private function client(): ?WebPush
    {
        if ($this->clientResolved) {
            return $this->client;
        }

        $this->clientResolved = true;

        $publicKey = config('services.webpush.public_key');
        $privateKey = config('services.webpush.private_key');

        if (! $publicKey || ! $privateKey) {
            return $this->client = null;
        }

        return $this->client = new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);
    }
}
