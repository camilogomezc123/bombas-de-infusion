<x-filament-panels::page>
    {{-- Overlay a pantalla completa: pensado para una TV/pantalla compartida de la
         unidad, no para convivir con el menú lateral del panel administrativo. --}}
    <div
        wire:poll.15s
        x-data="{
            dim: false,
            soundOn: false,
            critical: @entangle('criticalAlertCount').live,
            checkDim() { const h = new Date().getHours(); this.dim = h >= 22 || h < 6; },
            toggleSound() {
                this.soundOn = ! this.soundOn;
                try {
                    window.__vizAudioCtx = window.__vizAudioCtx || new (window.AudioContext || window.webkitAudioContext)();
                    if (this.soundOn) { window.__vizAudioCtx.resume(); this.beep(); }
                } catch (e) {}
            },
            beep() {
                try {
                    const ctx = window.__vizAudioCtx;
                    if (! ctx) return;
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.frequency.value = 880;
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    gain.gain.setValueAtTime(0.0001, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.15, ctx.currentTime + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.35);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.35);
                } catch (e) {}
            },
        }"
        x-init="checkDim(); setInterval(() => checkDim(), 60000)"
        x-effect="if (soundOn && critical > 0) beep()"
        x-bind:class="dim ? 'viz-dim' : ''"
        class="viz-root fixed inset-0 z-[60] flex flex-col overflow-y-auto print:static print:inset-auto"
        style="background:#0d0d0d;"
    >
        @verbatim
        <style>
            .viz-root { --surface-1: #1a1a19; --text-primary: #ffffff; --text-secondary: #c3c2b7; --text-muted: #898781; --ring-base: #383835;
                --vis-color: #3987e5; --dose-color: #c98500; --status-good: #0ca30c; --status-warning: #fab219; --status-info: #199e70; --status-critical: #d03b3b;
                transition: opacity 0.6s ease; }
            .viz-root.viz-dim { opacity: 0.72; }
            .viz-tile { background: var(--surface-1); border: 1px solid var(--ring-base); }
            .viz-tile[data-severity="critical"] { border-color: var(--status-critical); box-shadow: 0 0 0 1px var(--status-critical), 0 0 18px -4px var(--status-critical); animation: viz-pulse 1.6s ease-in-out infinite; }
            .viz-tile[data-severity="warning"] { border-color: var(--status-warning); box-shadow: 0 0 0 1px var(--status-warning); }
            .viz-tile[data-severity="info"] { border-color: var(--status-info); }
            @keyframes viz-pulse { 0%, 100% { box-shadow: 0 0 0 1px var(--status-critical), 0 0 10px -4px var(--status-critical); } 50% { box-shadow: 0 0 0 1px var(--status-critical), 0 0 22px -2px var(--status-critical); } }
            @media print {
                .viz-root { position: static !important; background: #ffffff !important; color: #000 !important; opacity: 1 !important; }
                .viz-root, .viz-root * { color: #000 !important; }
                .viz-tile { background: #fff !important; border: 1px solid #999 !important; box-shadow: none !important; animation: none !important; }
                .viz-no-print { display: none !important; }
            }
        </style>
        @endverbatim

        <div class="viz-no-print flex items-center justify-between border-b px-5 py-3" style="border-color:#2c2c2a;">
            <div class="flex items-center gap-3">
                <span class="h-2.5 w-2.5 rounded-full" style="background:var(--status-good)"></span>
                <h1 class="text-lg font-bold tracking-wide" style="color:var(--text-primary)">Central de Monitoreo · Bombas de Infusión</h1>
            </div>
            <div class="flex items-center gap-4 text-sm" style="color:var(--text-secondary)">
                <span>{{ count($this->tiles) }} cubículo(s) con bomba activa</span>
                <span class="font-mono text-base" style="color:var(--text-primary)" x-data x-init="setInterval(() => $el.textContent = new Date().toLocaleTimeString('es-CO', {hour: '2-digit', minute:'2-digit', second:'2-digit'}), 1000)">{{ now()->format('H:i:s') }}</span>
                <button type="button" onclick="window.print()" class="rounded-lg border px-3 py-1 font-bold" style="border-color:var(--ring-base); color:var(--text-secondary)">🖨 Imprimir</button>
                <a href="{{ \App\Filament\InfusionMonitoring\Pages\PumpCensus::getUrl(panel: 'infusion-monitoring') }}" class="rounded-lg border px-3 py-1 font-bold" style="border-color:var(--ring-base); color:var(--text-secondary)">Salir ✕</a>
            </div>
        </div>

        <div class="viz-no-print flex flex-wrap items-center justify-between gap-3 px-5 py-2" style="border-bottom:1px solid #2c2c2a;">
            <div class="flex items-center gap-3 text-xs font-bold">
                <span class="flex items-center gap-1" style="color:var(--status-critical)"><span class="h-2 w-2 rounded-full" style="background:var(--status-critical)"></span>{{ $this->summaryCounts['critical'] }} crítica(s)</span>
                <span class="flex items-center gap-1" style="color:var(--status-warning)"><span class="h-2 w-2 rounded-full" style="background:var(--status-warning)"></span>{{ $this->summaryCounts['warning'] }} en alerta</span>
                <span class="flex items-center gap-1" style="color:var(--status-info)"><span class="h-2 w-2 rounded-full" style="background:var(--status-info)"></span>{{ $this->summaryCounts['info'] }} informativa(s)</span>
                <span class="flex items-center gap-1" style="color:var(--status-good)"><span class="h-2 w-2 rounded-full" style="background:var(--status-good)"></span>{{ $this->summaryCounts['good'] }} estable(s)</span>
            </div>
            <div class="flex items-center gap-4 text-xs" style="color:var(--text-secondary)">
                <label class="flex items-center gap-1.5">
                    <input type="checkbox" wire:model.live="onlyAlerts" />
                    Solo con alertas
                </label>
                <label class="flex items-center gap-1.5">
                    <input type="checkbox" wire:model.live="sortByUrgency" />
                    Ordenar por urgencia
                </label>
                <button type="button" x-on:click="toggleSound()" class="rounded-lg border px-2 py-1 font-bold" style="border-color:var(--ring-base);" x-bind:style="soundOn ? 'color:var(--status-good)' : 'color:var(--text-secondary)'">
                    <span x-text="soundOn ? '🔔 Sonido activado' : '🔕 Activar sonido'"></span>
                </button>
            </div>
        </div>

        @if (count($this->compareTiles) === 2)
            <div class="viz-no-print border-b px-5 py-3" style="border-color:#2c2c2a;">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wide" style="color:var(--text-muted)">Comparación VIS lado a lado</p>
                    <button type="button" wire:click="$set('compareIds', [])" class="text-xs font-bold" style="color:var(--text-muted)">✕ Cerrar comparación</button>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($this->compareTiles as $index => $compareTile)
                        @php
                            $compareColor = $index === 0 ? 'var(--vis-color)' : '#d55181';
                            $compareSeries = $compareTile['vis_series'];
                            $comparePoints = '';
                            if (count($compareSeries) >= 2) {
                                $cMin = min($compareSeries); $cMax = max($compareSeries);
                                $cRange = max($cMax - $cMin, 0.01);
                                $cStepX = 100 / (count($compareSeries) - 1);
                                $cCoords = [];
                                foreach ($compareSeries as $i => $v) {
                                    $cCoords[] = round($i * $cStepX, 1).','.round(26 - (($v - $cMin) / $cRange) * 22, 1);
                                }
                                $comparePoints = implode(' ', $cCoords);
                            }
                        @endphp
                        <div class="rounded-lg p-2" style="border:1px solid {{ $compareColor }}44;">
                            <p class="text-xs font-bold" style="color:{{ $compareColor }}">{{ $compareTile['stay']->bed_label }} · {{ $compareTile['stay']->patient?->full_name ?? 'Sin paciente' }}</p>
                            <div class="flex items-center gap-3">
                                <p class="text-2xl font-black" style="color:{{ $compareColor }}">{{ $compareTile['vis_current']?->vis_score ?? '—' }}</p>
                                <svg viewBox="0 0 100 30" class="h-8 w-28 shrink-0">
                                    @if ($comparePoints)
                                        <polyline points="{{ $comparePoints }}" fill="none" stroke="{{ $compareColor }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    @endif
                                </svg>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <p class="viz-no-print px-5 pt-3 text-xs" style="color:var(--text-muted)">
            Apoyo visual de trazabilidad — no sustituye el monitoreo clínico directo del paciente ni la vigilancia de enfermería.
        </p>

        <div class="grid flex-1 content-start gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @forelse ($this->tiles as $tile)
                @php
                    $stay = $tile['stay'];
                    $vis = $tile['vis_current'];
                    $series = $tile['vis_series'];
                    $alerts = $tile['open_alerts'];
                    $deviceStatusLabel = \App\Models\PumpDevice::STATUSES[$tile['device_status']] ?? null;
                    $deviceStatusVar = match ($tile['device_status']) {
                        'infusing' => 'var(--status-good)',
                        'battery_low' => 'var(--status-warning)',
                        'occlusion', 'air_in_line' => 'var(--status-critical)',
                        default => 'var(--text-muted)',
                    };
                @endphp

                @php
                    $points = '';
                    if (count($series) >= 2) {
                        $min = min($series); $max = max($series);
                        $range = max($max - $min, 0.01);
                        $stepX = 100 / (count($series) - 1);
                        $coords = [];
                        foreach ($series as $i => $v) {
                            $x = round($i * $stepX, 1);
                            $y = round(26 - (($v - $min) / $range) * 22, 1);
                            $coords[] = "{$x},{$y}";
                        }
                        $points = implode(' ', $coords);
                        $lastCoord = explode(',', $coords[count($coords) - 1]);
                    }
                @endphp

                <div class="viz-tile flex flex-col gap-2 rounded-xl p-3" data-severity="{{ $tile['severity'] }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-base font-black leading-tight" style="color:var(--text-primary)">{{ $stay->bed_label ?? '—' }}</p>
                            <p class="truncate text-xs" style="color:var(--text-secondary)">{{ $stay->patient?->full_name ?? 'Sin paciente' }} · {{ $stay->case_number }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <div class="flex items-center gap-1">
                                @if ($alerts->isNotEmpty())
                                    <span class="flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold" style="background:{{ $tile['severity'] === 'critical' ? 'var(--status-critical)' : 'var(--status-warning)' }}22; color:{{ $tile['severity'] === 'critical' ? 'var(--status-critical)' : 'var(--status-warning)' }}">
                                        🔔 {{ $alerts->count() }}
                                    </span>
                                @endif
                                @if (! $tile['has_weight'])
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold" style="background:#2c2c2a; color:var(--text-muted)" title="Sin peso registrado">✕ peso</span>
                                @endif
                                <button type="button" wire:click="toggleCompare({{ $stay->id }})" class="viz-no-print rounded-full px-1.5 py-0.5 text-[11px] font-bold" style="background:{{ in_array($stay->id, $this->compareIds) ? 'var(--vis-color)33' : '#2c2c2a' }}; color:{{ in_array($stay->id, $this->compareIds) ? 'var(--vis-color)' : 'var(--text-muted)' }}" title="Comparar con otra cama">📊</button>
                            </div>
                            @if ($deviceStatusLabel)
                                <div class="flex items-center gap-1.5 text-[11px] font-bold" style="color:{{ $deviceStatusVar }}">
                                    <span class="h-1.5 w-1.5 rounded-full" style="background:{{ $deviceStatusVar }}"></span>
                                    {{ $deviceStatusLabel }}
                                    @if ($tile['battery_pct'] !== null)
                                        <span style="color:{{ $tile['battery_pct'] <= 20 ? 'var(--status-warning)' : 'var(--text-muted)' }}">🔋{{ $tile['battery_pct'] }}%</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-end justify-between gap-2">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wide" style="color:var(--text-muted)">VIS</p>
                            <div class="flex items-baseline gap-1.5">
                                <p class="text-3xl font-semibold leading-none" style="color:var(--vis-color)">{{ $vis?->vis_score ?? '—' }}</p>
                                @if ($tile['vis_delta'] !== null && $tile['vis_delta'] != 0)
                                    <span class="text-xs font-bold" style="color:{{ $tile['vis_delta'] > 0 ? 'var(--status-warning)' : 'var(--status-good)' }}">
                                        {{ $tile['vis_delta'] > 0 ? '▲' : '▼' }} {{ number_format(abs($tile['vis_delta']), 1) }}
                                    </span>
                                @elseif ($tile['vis_delta'] === 0.0)
                                    <span class="text-xs font-bold" style="color:var(--text-muted)">▬ estable</span>
                                @endif
                            </div>
                            @if ($tile['vis_vs_baseline'] !== null)
                                <p class="text-[10px]" style="color:var(--text-muted)">
                                    vs. ingreso ({{ $tile['vis_baseline']->vis_score }}):
                                    <span style="color:{{ $tile['vis_vs_baseline'] > 0 ? 'var(--status-warning)' : ($tile['vis_vs_baseline'] < 0 ? 'var(--status-good)' : 'var(--text-muted)') }}">
                                        {{ $tile['vis_vs_baseline'] > 0 ? '+' : '' }}{{ number_format($tile['vis_vs_baseline'], 1) }}
                                    </span>
                                </p>
                            @endif
                        </div>
                        <svg viewBox="0 0 100 30" class="h-8 w-20 shrink-0">
                            @if ($points)
                                <polyline points="{{ $points }}" fill="none" stroke="var(--vis-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="{{ $lastCoord[0] }}" cy="{{ $lastCoord[1] }}" r="2.5" fill="var(--vis-color)" stroke="var(--surface-1)" stroke-width="1.5" />
                            @else
                                <text x="0" y="20" fill="var(--text-muted)" font-size="9">sin tendencia</text>
                            @endif
                        </svg>
                    </div>

                    @foreach ($tile['channels'] as $channel)
                        @php
                            $event = $channel['event'];
                            $dose = $channel['dose_summary'];
                            $remainingMinutes = $event->infusion_duration_planned_minutes;
                            $categoryIcon = match ($channel['profile_category']) {
                                'antibiotic' => '💊',
                                'vasoactive' => '🩸',
                                'sedation' => '😴',
                                'analgesia' => '💉',
                                default => null,
                            };
                            $drugAlerts = $channel['drug_alerts'];
                            $isStale = $event->occurred_at && $event->occurred_at->lt(now()->subHours(3));
                            $needsCoSign = $event->requiresCoSign() && ! $event->co_signed_at;
                            $rateSeries = $channel['rate_series'];
                            $ratePoints = '';
                            if (count($rateSeries) >= 2) {
                                $rMin = min($rateSeries); $rMax = max($rateSeries);
                                $rRange = max($rMax - $rMin, 0.01);
                                $rStepX = 100 / (count($rateSeries) - 1);
                                $rCoords = [];
                                foreach ($rateSeries as $i => $v) {
                                    $rCoords[] = round($i * $rStepX, 1).','.round(16 - (($v - $rMin) / $rRange) * 13, 1);
                                }
                                $ratePoints = implode(' ', $rCoords);
                            }
                        @endphp
                        <div class="border-t pt-2 text-xs" style="border-color:#2c2c2a; color:var(--text-secondary)">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate font-semibold" style="color:var(--text-primary)">
                                    @if ($tile['channels']->count() > 1)
                                        <span style="color:var(--text-muted)">Canal {{ $channel['channel_number'] }} ·</span>
                                    @endif
                                    @if ($categoryIcon) {{ $categoryIcon }} @endif
                                    {{ $event->medication_name_raw }}
                                </p>
                                <div class="flex shrink-0 items-center gap-1">
                                    @if ($ratePoints)
                                        <svg viewBox="0 0 100 20" class="h-4 w-10" title="Tendencia de tasa real infundida">
                                            <polyline points="{{ $ratePoints }}" fill="none" stroke="var(--dose-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    @endif
                                    @if ($needsCoSign)
                                        <span class="rounded px-1.5 py-0.5 text-[10px] font-bold" style="background:var(--status-warning)22; color:var(--status-warning)" title="Fármaco de alto riesgo sin doble verificación — co-firmar en Bitácora de eventos">⏳ sin co-firma</span>
                                    @endif
                                    @if (! $event->used_drug_library)
                                        <span class="rounded px-1.5 py-0.5 text-[10px] font-bold" style="background:var(--status-warning)22; color:var(--status-warning)" title="Programada fuera de la librería de dosis segura">⚡ sin librería</span>
                                    @endif
                                </div>
                            </div>
                            <p>{{ $event->existing_dose_rate_value !== null ? $event->existing_dose_rate_value.' '.$event->existing_dose_rate_unit : ($event->attempted_value.' '.$event->attempted_unit) }}
                                @if ($dose)
                                    <span style="color:var(--text-muted)">· hoy: {{ $dose->cumulative_dose }} {{ $dose->dose_unit }}</span>
                                @endif
                                <span style="color:{{ $isStale ? 'var(--status-warning)' : 'var(--text-muted)' }}" @if($isStale) title="Sin telemetría nueva de esta bomba hace más de 3h" @endif>· {{ $event->occurred_at?->diffForHumans() }}{{ $isStale ? ' ⚠' : '' }}</span></p>
                            @if ($remainingMinutes !== null)
                                <p style="color:var(--text-muted)">
                                    Restante: {{ $event->volume_remaining_ml !== null ? $event->volume_remaining_ml.' ml · ' : '' }}
                                    @if ($remainingMinutes < 30)
                                        <span style="color:var(--status-warning)">~{{ $remainingMinutes }} min</span>
                                    @else
                                        ~{{ intdiv($remainingMinutes, 60) }}h {{ $remainingMinutes % 60 }}min
                                    @endif
                                </p>
                            @endif
                            @if ($drugAlerts->isNotEmpty())
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach ($drugAlerts as $drugAlert)
                                        @php
                                            $alertColor = $drugAlert->severity === 'critical' ? 'var(--status-critical)' : 'var(--status-warning)';
                                        @endphp
                                        <span class="flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-bold" style="background:{{ $alertColor }}22; color:{{ $alertColor }}" title="{{ $drugAlert->message }}">
                                            ⚠ {{ \App\Models\InfusionClinicalAlert::TYPES[$drugAlert->type] ?? $drugAlert->type }}
                                            @if ($drugAlert->status === 'open')
                                                <button type="button" wire:click="acknowledgeAlert({{ $drugAlert->id }})" class="viz-no-print ml-0.5 rounded px-1 font-black" style="background:{{ $alertColor }}33" title="Reconocer alerta">✓</button>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @empty
                <p class="col-span-full py-10 text-center text-sm" style="color:var(--text-muted)">No hay cubículos con bombas activas registradas todavía.</p>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
