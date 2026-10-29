<?php

namespace App\Filament\InfusionMonitoring\Resources\InfusionEvents;

use App\Enums\ProgramPermission;
use App\Filament\InfusionMonitoring\Resources\InfusionEvents\Pages\ListInfusionEvents;
use App\Filament\InfusionMonitoring\Resources\InfusionEvents\Pages\ViewInfusionEvent;
use App\Models\DrugInfusionProfile;
use App\Models\InfusionEvent;
use App\Models\PumpDevice;
use App\Support\CsvExporter;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InfusionEventResource extends Resource
{
    protected static ?string $model = InfusionEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Bitácora de eventos';

    protected static ?string $modelLabel = 'evento de bomba';

    protected static ?string $pluralModelLabel = 'Bitácora de eventos';

    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool
    {
        return auth()->check() && ProgramAccess::can(auth()->user(), ProgramPermission::ViewCases, 'BOMBAS');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dispositivo e infusión')->columns(3)->schema([
                Placeholder::make('hospital_device_id')->label('ID del dispositivo hospitalario')
                    ->content(fn (InfusionEvent $record): string => $record->pumpDevice?->hospital_device_id ?? '—'),
                Placeholder::make('serial_number')->label('Número de serie')
                    ->content(fn (InfusionEvent $record): string => $record->pumpDevice?->serial_number ?? '—'),
                Placeholder::make('vendor_infusion_id')->label('ID de la infusión')
                    ->content(fn (InfusionEvent $record): string => $record->vendor_infusion_id ?? '—'),
                Placeholder::make('location')->label('Ubicación / punto de acceso')
                    ->content(fn (InfusionEvent $record): string => $record->pumpDevice?->current_bed_label ?? '—'),
                Placeholder::make('model')->label('Modelo y tipo de dispositivo')
                    ->content(fn (InfusionEvent $record): string => $record->pumpDevice?->model ?? '—'),
                Placeholder::make('device_group')->label('Grupo de la empresa')
                    ->content(fn (InfusionEvent $record): string => $record->pumpDevice?->device_group ?? '—'),
            ]),
            Section::make('Parámetros del evento de límite')->columns(3)->schema([
                Placeholder::make('existing_dose_rate')->label('Tasa de dosis existente')
                    ->content(fn (InfusionEvent $record): string => $record->existing_dose_rate_value !== null ? $record->existing_dose_rate_value.' '.$record->existing_dose_rate_unit : '—'),
                Placeholder::make('attempted_value')->label('Intento de valor')
                    ->content(fn (InfusionEvent $record): string => $record->attempted_value !== null ? $record->attempted_value.' '.$record->attempted_unit : '—'),
                Placeholder::make('next_drug')->label('Next drug')
                    ->content(fn (InfusionEvent $record): string => $record->next_drug ?? '—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('occurred_at')->label('Fecha/hora')->dateTime('d-M-Y, H:i:s')->sortable(),
            TextColumn::make('medication_name_raw')->label('Nombre del medicamento')->searchable(),
            TextColumn::make('concentration_raw')->label('Concentración')->placeholder('—'),
            TextColumn::make('limit_type')->label('Tipo de límite')->placeholder('—'),
            TextColumn::make('alert_level')->label('Alertas')->badge()
                ->color(fn (?string $state): string => match (true) {
                    str_contains((string) $state, 'Duro') => 'danger',
                    str_contains((string) $state, 'Blando') => 'warning',
                    default => 'gray',
                })->placeholder('—'),
            TextColumn::make('next_action')->label('Próxima acción')->placeholder('—'),
            TextColumn::make('care_area')->label('Área de atención')->placeholder('—'),
            TextColumn::make('icuStay.patient.full_name')->label('Paciente')->placeholder('Sin resolver'),
            TextColumn::make('channel_number')->label('Canal')->badge()->color('gray'),
            TextColumn::make('device_status')->label('Estado dispositivo')->badge()
                ->formatStateUsing(fn (?string $state): string => PumpDevice::STATUSES[$state] ?? '—')
                ->color(fn (?string $state): string => PumpDevice::statusColor($state))
                ->placeholder('—'),
            TextColumn::make('battery_pct')->label('Batería')->placeholder('—')
                ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : "{$state}%"),
            IconColumn::make('used_drug_library')->label('Librería')->boolean()
                ->trueIcon('heroicon-o-check-badge')->falseIcon('heroicon-o-bolt')
                ->trueColor('success')->falseColor('warning'),
            TextColumn::make('overridden_by_operator')->label('Operador')->placeholder('—'),
            TextColumn::make('co_signed_by')->label('Co-firma')->badge()
                ->getStateUsing(function (InfusionEvent $record): string {
                    if (! $record->requiresCoSign()) {
                        return 'N/A';
                    }

                    return $record->co_signed_at ? 'Firmada: '.$record->coSignedBy?->name : 'Pendiente';
                })
                ->color(function (InfusionEvent $record): string {
                    if (! $record->requiresCoSign()) {
                        return 'gray';
                    }

                    return $record->co_signed_at ? 'success' : 'warning';
                }),
            TextColumn::make('attempted_value')->label('Intento de valor')
                ->formatStateUsing(fn (InfusionEvent $record): string => $record->attempted_value !== null ? $record->attempted_value.' '.$record->attempted_unit : '—'),
            TextColumn::make('soft_limit_low')->label('Límites excedidos')
                ->formatStateUsing(function (InfusionEvent $record): string {
                    if ($record->hard_limit_low !== null || $record->hard_limit_high !== null) {
                        return "Duro: {$record->hard_limit_low} - {$record->hard_limit_high} {$record->attempted_unit}";
                    }

                    if ($record->soft_limit_low !== null || $record->soft_limit_high !== null) {
                        return "Blando: {$record->soft_limit_low} - {$record->soft_limit_high} {$record->attempted_unit}";
                    }

                    return '—';
                }),
        ])->recordActions([
            Action::make('coSign')->label('Co-firmar')->icon('heroicon-m-pencil-square')->color('success')
                ->requiresConfirmation()
                ->modalDescription('Confirmas que verificaste esta programación de alto riesgo (segunda persona, independiente de quien la programó).')
                ->visible(fn (InfusionEvent $record): bool => $record->requiresCoSign() && ! $record->co_signed_at)
                ->action(fn (InfusionEvent $record) => $record->update(['co_signed_by' => auth()->id(), 'co_signed_at' => now()])),
        ])->filters([
            SelectFilter::make('drug_infusion_profile_id')->label('Medicamento')
                ->options(fn () => DrugInfusionProfile::query()->pluck('name', 'id')),
            SelectFilter::make('alert_level')->label('Alerta')->options([
                'DERS - Blando' => 'DERS - Blando',
                'DERS - Duro' => 'DERS - Duro',
                'PUMP - Duro' => 'PUMP - Duro',
            ]),
            SelectFilter::make('device_status')->label('Estado del dispositivo')->options(PumpDevice::STATUSES),
        ])->headerActions([
            Action::make('exportCsv')->label('Exportar CSV')->icon('heroicon-m-arrow-down-tray')->color('gray')
                ->action(fn ($livewire) => CsvExporter::stream(
                    'bitacora-bombas-'.now()->format('Y-m-d').'.csv',
                    ['Fecha/hora', 'Medicamento', 'Concentración', 'Alerta', 'Próxima acción', 'Área', 'Paciente', 'Intento de valor', 'Unidad'],
                    $livewire->getFilteredTableQuery()->with('icuStay.patient')->get()->map(fn (InfusionEvent $event): array => [
                        $event->occurred_at->format('d/m/Y H:i:s'),
                        $event->medication_name_raw,
                        $event->concentration_raw,
                        $event->alert_level,
                        $event->next_action,
                        $event->care_area,
                        $event->icuStay?->patient?->full_name,
                        $event->attempted_value,
                        $event->attempted_unit,
                    ]),
                )),
        ])->defaultSort('occurred_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInfusionEvents::route('/'),
            'view' => ViewInfusionEvent::route('/{record}'),
        ];
    }
}
