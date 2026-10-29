<?php

namespace App\Filament\Acv\Pages;

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Filament\Acv\Support\CaseListPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class ActiveCases extends CaseListPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Activos';

    protected static ?string $title = 'Casos activos (en clínica)';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'activos';

    public static function canAccess(): bool
    {
        return ! in_array(auth()->user()?->role, [UserRole::Followup], true);
    }

    protected function scopeQuery(Builder $query): Builder
    {
        return $query->where('status', CaseStatus::Hospitalized);
    }

    /**
     * En "Activos" el caso aún no está diligenciado: solo mostramos lo que siempre existe.
     */
    protected function tableColumns(): array
    {
        return [
            TextColumn::make('case_number')->label('Caso')->searchable()->sortable()->copyable(),
            TextColumn::make('patient.full_name')
                ->label('Paciente')
                ->description(fn ($record): string => $record->patient->identification
                    .($record->is_recurrence ? '  ·  Recurrente' : ''))
                ->searchable(['full_name', 'identification'])
                ->sortable(),
            TextColumn::make('admission_number')->label('N.º ingreso')->searchable(),
            TextColumn::make('eapb')->label('EAPB')->placeholder('—')->searchable(),
            TextColumn::make('created_at')->label('Registrado')->dateTime('d/m/Y H:i')->sortable(),
        ];
    }
}
