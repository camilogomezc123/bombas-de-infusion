<?php

namespace App\Services;

use App\Models\PicsAgendaItem;
use App\Models\PicsReferral;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Agenda coordinada interna: une remisiones (PicsReferral) e ítems de agenda
 * (PicsAgendaItem) en una sola lista cronológica simple — no un calendario, porque no
 * hay librería JS disponible en este entorno (sin Node.js) ni la vale la pena para
 * lo que pide el alcance.
 */
class PicsAgendaService
{
    /**
     * @return Collection<int, array{type: string, model: PicsReferral|PicsAgendaItem, case: mixed, title: string, when: ?Carbon, responsible: ?string, status_label: string, status_color: string}>
     */
    public function upcoming(int $days = 14, bool $includeOverdue = true): Collection
    {
        $windowStart = $includeOverdue ? null : now();
        $windowEnd = now()->addDays($days);

        $referrals = PicsReferral::query()
            ->whereIn('status', ['open', 'scheduled'])
            ->with(['case.patient'])
            ->get()
            ->toBase()
            ->map(function (PicsReferral $referral): array {
                return [
                    'type' => 'referral',
                    'model' => $referral,
                    'case' => $referral->case,
                    'title' => 'Remisión: '.$referral->specialty,
                    'when' => $referral->scheduled_at ?? $referral->referred_at,
                    'responsible' => null,
                    'status_label' => $referral->statusLabel(),
                    'status_color' => match ($referral->status) {
                        'scheduled' => 'warning',
                        default => 'gray',
                    },
                ];
            });

        $agendaItems = PicsAgendaItem::query()
            ->where('status', 'pendiente')
            ->with(['case.patient', 'responsible'])
            ->get()
            ->toBase()
            ->map(fn (PicsAgendaItem $item): array => [
                'type' => 'agenda_item',
                'model' => $item,
                'case' => $item->case,
                'title' => $item->title,
                'when' => $item->scheduled_at,
                'responsible' => $item->responsible?->name,
                'status_label' => $item->statusLabel(),
                'status_color' => 'warning',
            ]);

        return $referrals->merge($agendaItems)
            ->filter(fn (array $row): bool => $row['when'] !== null)
            ->filter(function (array $row) use ($windowStart, $windowEnd): bool {
                $when = Carbon::parse($row['when']);

                if ($windowStart !== null && $when->lt($windowStart)) {
                    return false;
                }

                return $when->lte($windowEnd);
            })
            ->sortBy(fn (array $row) => Carbon::parse($row['when']))
            ->values();
    }
}
