<?php

namespace App\Filament\Acv\Pages;

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Filament\Acv\Support\CaseListPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class DischargedCases extends CaseListPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowRightOnRectangle;

    protected static ?string $navigationLabel = 'Pendientes de análisis';

    protected static ?string $title = 'Pendientes de análisis';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'egresados';

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [
            UserRole::Administrator,
            UserRole::Leader,
            UserRole::Auditor,
        ], true);
    }

    protected function auditorCanEdit(): bool
    {
        return true;
    }

    protected function scopeQuery(Builder $query): Builder
    {
        return $query
            ->whereNotNull('discharged_at')
            ->whereIn('status', CaseStatus::pendingAnalysis())
            ->orderByDesc('discharged_at');
    }

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
            TextColumn::make('eapb')->label('EAPB')->placeholder('—')->searchable(),
            TextColumn::make('stroke_type')->label('Tipo')->badge()->placeholder('—'),
            TextColumn::make('assignedAuditor.name')->label('Auditor')->placeholder('Sin asignar'),
            TextColumn::make('status')
                ->label('Estado')
                ->formatStateUsing(fn ($state): string => $state?->label() ?? 'Sin estado')
                ->badge()
                ->color(fn ($state): string => match ($state?->value) {
                    'pending' => 'warning',
                    'in_review' => 'info',
                    default => 'primary',
                }),
        ];
    }
}
