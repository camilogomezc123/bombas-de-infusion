<?php

namespace App\Filament\Acv\Pages;

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Filament\Acv\Support\CaseListPage;
use App\Models\AcvCase;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Registro completo de casos ACV. Cada renglón es un caso (código S) con su propio
 * Código ResQ; un paciente recurrente aparece una vez por cada ingreso.
 */
class CaseRegistry extends CaseListPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $navigationLabel = 'Base de datos';

    protected static ?string $title = 'Base de datos de casos';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'base-datos';

    public static function canAccess(): bool
    {
        return ! in_array(auth()->user()?->role, [UserRole::Followup], true);
    }

    protected function scopeQuery(Builder $query): Builder
    {
        return $query;
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
            TextColumn::make('resq_code')
                ->label('Código ResQ')
                ->formatStateUsing(fn (?string $state): string => $state ? Str::before($state, '-') : '—')
                ->searchable(),
            TextColumn::make('eapb')->label('EAPB')->placeholder('—')->searchable(),
            TextColumn::make('stroke_type')->label('Tipo')->badge()->placeholder('—'),
            TextColumn::make('status')
                ->label('Estado')
                ->formatStateUsing(fn ($state): string => $state?->label() ?? 'Sin estado')
                ->badge()
                ->color(fn ($state): string => match ($state?->value) {
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    'pending' => 'warning',
                    'hospitalized' => 'info',
                    default => 'primary',
                }),
        ];
    }

    protected function tableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label('Estado')
                ->options(collect(CaseStatus::cases())->mapWithKeys(
                    fn (CaseStatus $status): array => [$status->value => $status->label()]
                )->all()),
            SelectFilter::make('eapb')
                ->label('EAPB')
                ->options(fn (): array => AcvCase::query()
                    ->whereNotNull('eapb')
                    ->distinct()
                    ->orderBy('eapb')
                    ->pluck('eapb', 'eapb')
                    ->all())
                ->searchable(),
            SelectFilter::make('month')
                ->label('Período')
                ->options(fn (): array => AcvCase::query()
                    ->whereNotNull('month')
                    ->distinct()
                    ->orderByDesc('month')
                    ->pluck('month', 'month')
                    ->all())
                ->searchable(),
            SelectFilter::make('stroke_type')
                ->label('Tipo de ACV')
                ->options(fn (): array => AcvCase::query()
                    ->whereNotNull('stroke_type')
                    ->distinct()
                    ->orderBy('stroke_type')
                    ->pluck('stroke_type', 'stroke_type')
                    ->all()),
        ];
    }
}
