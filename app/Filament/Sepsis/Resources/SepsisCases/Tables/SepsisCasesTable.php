<?php

namespace App\Filament\Sepsis\Resources\SepsisCases\Tables;

use App\Filament\Sepsis\Resources\SepsisCases\SepsisCaseResource;
use App\Models\SepsisCase;
use App\Support\CsvExporter;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SepsisCasesTable
{
    public static function configure(Table $table): Table
    {
        $perPage = auth()->user()?->recordsPerPage() ?? 50;

        return $table
            ->columns([
                TextColumn::make('case_number')->label('Caso')->searchable()->sortable()->copyable(),
                TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->description(fn (SepsisCase $record): string => $record->patient->identification)
                    ->searchable(['full_name', 'identification'])
                    ->sortable(),
                TextColumn::make('admission_number')->label('N.º ingreso')->searchable(),
                TextColumn::make('infection_focus')->label('Foco')->placeholder('—')->badge(),
                TextColumn::make('assignedAuditor.name')->label('Auditor')->placeholder('Sin asignar'),
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
                TextColumn::make('site.name')->label('Sede')->placeholder('—')->toggleable(),
                TextColumn::make('month')->label('Período')->placeholder('En proceso')->sortable(),
            ])
            ->filters([
                SelectFilter::make('infection_focus')
                    ->label('Foco infeccioso')
                    ->options(fn (): array => SepsisCase::query()
                        ->whereNotNull('infection_focus')->distinct()->orderBy('infection_focus')
                        ->pluck('infection_focus', 'infection_focus')->all()),
                SelectFilter::make('month')
                    ->label('Período')
                    ->options(fn (): array => SepsisCase::query()
                        ->whereNotNull('month')->where('month', '!=', '')->distinct()->orderByDesc('month')
                        ->pluck('month', 'month')->all())
                    ->searchable(),
                SelectFilter::make('site_id')
                    ->label('Sede')
                    ->relationship('site', 'name'),
            ])
            ->recordActions([
                Action::make('view')->label('Ver')->icon('heroicon-m-eye')
                    ->url(fn (SepsisCase $record): string => SepsisCaseResource::getUrl('view', ['record' => $record])),
                Action::make('edit')->label('Editar')->icon('heroicon-m-pencil-square')
                    ->visible(fn (SepsisCase $record): bool => SepsisCaseResource::canEdit($record))
                    ->url(fn (SepsisCase $record): string => SepsisCaseResource::getUrl('edit', ['record' => $record])),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('Exportar CSV')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('gray')
                    ->action(fn ($livewire) => CsvExporter::stream(
                        'casos-sepsis-'.now()->format('Y-m-d').'.csv',
                        ['Caso', 'Paciente', 'Documento', 'N.º ingreso', 'Servicio de origen', 'Foco', 'Auditor', 'Estado', 'Período', 'Choque séptico', 'Fallecido'],
                        $livewire->getFilteredTableQuery()->with('patient', 'assignedAuditor')->get()->map(fn (SepsisCase $case): array => [
                            $case->case_number,
                            $case->patient?->full_name,
                            $case->patient?->identification,
                            $case->admission_number,
                            $case->origin_service,
                            $case->infection_focus,
                            $case->assignedAuditor?->name,
                            $case->status?->label(),
                            $case->month,
                            $case->septic_shock ? 'Sí' : 'No',
                            $case->deceased ? 'Sí' : 'No',
                        ]),
                    )),
            ])
            ->searchDebounce('400ms')
            ->paginationPageOptions([$perPage])
            ->defaultPaginationPageOption($perPage)
            ->defaultSort('case_sequence', 'desc')
            ->striped();
    }
}
