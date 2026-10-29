<?php

namespace App\Filament\InfusionMonitoring\Resources\InfusionClinicalAlerts;

use App\Enums\ProgramPermission;
use App\Filament\InfusionMonitoring\Resources\InfusionClinicalAlerts\Pages\ListInfusionClinicalAlerts;
use App\Models\InfusionClinicalAlert;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InfusionClinicalAlertResource extends Resource
{
    protected static ?string $model = InfusionClinicalAlert::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?string $navigationLabel = 'Alertas clínicas';

    protected static ?string $modelLabel = 'alerta';

    protected static ?string $pluralModelLabel = 'Alertas clínicas';

    protected static ?int $navigationSort = 15;

    public static function canViewAny(): bool
    {
        return auth()->check() && ProgramAccess::can(auth()->user(), ProgramPermission::ViewCases, 'BOMBAS');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Generada')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('icuStay.case_number')->label('Caso')->placeholder('—'),
            TextColumn::make('icuStay.patient.full_name')->label('Paciente')->placeholder('—'),
            TextColumn::make('icuStay.bed_label')->label('Cama')->placeholder('—'),
            TextColumn::make('type')->label('Tipo')
                ->formatStateUsing(fn (string $s) => InfusionClinicalAlert::TYPES[$s] ?? $s)->badge(),
            TextColumn::make('severity')->label('Severidad')
                ->formatStateUsing(fn (string $s) => InfusionClinicalAlert::SEVERITIES[$s] ?? $s)
                ->badge()->color(fn (string $state): string => match ($state) {
                    'critical' => 'danger', 'warning' => 'warning', default => 'gray',
                }),
            TextColumn::make('message')->label('Mensaje')->wrap()->limit(140),
            TextColumn::make('status')->label('Estado')
                ->formatStateUsing(fn (string $s) => InfusionClinicalAlert::STATUSES[$s] ?? $s)
                ->badge()->color(fn (string $state): string => match ($state) {
                    'open' => 'danger', 'acknowledged' => 'warning', 'resolved' => 'success', default => 'gray',
                }),
        ])->recordActions([
            Action::make('acknowledge')->label('Reconocer')->icon('heroicon-m-check')->color('warning')
                ->visible(fn (InfusionClinicalAlert $record): bool => $record->status === 'open')
                ->action(fn (InfusionClinicalAlert $record) => $record->update([
                    'status' => 'acknowledged', 'acknowledged_by' => auth()->id(), 'acknowledged_at' => now(),
                ])),
            Action::make('resolve')->label('Resolver')->icon('heroicon-m-check-circle')->color('success')
                ->requiresConfirmation()
                ->visible(fn (InfusionClinicalAlert $record): bool => $record->status !== 'resolved')
                ->action(fn (InfusionClinicalAlert $record) => $record->update([
                    'status' => 'resolved', 'resolved_at' => now(), 'updated_by' => auth()->id(),
                ])),
        ])->filters([
            SelectFilter::make('status')->label('Estado')->options(InfusionClinicalAlert::STATUSES),
            SelectFilter::make('type')->label('Tipo')->options(InfusionClinicalAlert::TYPES),
            SelectFilter::make('severity')->label('Severidad')->options(InfusionClinicalAlert::SEVERITIES),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInfusionClinicalAlerts::route('/'),
        ];
    }
}
