<?php

namespace App\Filament\Pics\Resources\PicsCases\Tables;

use App\Filament\Pics\Resources\PicsCases\PicsCaseResource;
use App\Models\PicsCase;
use App\Support\CsvExporter;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PicsCasesTable
{
    public static function configure(Table $table): Table
    {
        $perPage = auth()->user()?->recordsPerPage() ?? 50;

        return $table
            ->columns([
                TextColumn::make('case_number')->label('Caso')->searchable()->sortable()->copyable(),
                TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->description(fn (PicsCase $record): string => $record->patient->identification)
                    ->searchable(['full_name', 'identification'])
                    ->sortable(),
                TextColumn::make('enrollment_source')->label('Origen')
                    ->formatStateUsing(fn (?string $state): string => PicsCase::ENROLLMENT_SOURCES[$state] ?? '—')
                    ->badge(),
                TextColumn::make('assignedAuditor.name')->label('Responsable')->placeholder('Sin asignar'),
                TextColumn::make('risk_level')
                    ->label('Riesgo PICS')
                    ->badge()
                    ->formatStateUsing(fn (PicsCase $record): string => $record->riskLevelLabel() ?? 'Sin calcular')
                    ->color(fn (PicsCase $record): string => $record->riskLevelColor()),
                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state): string => $state?->label() ?? 'Sin estado')
                    ->badge()
                    ->color(fn ($state): string => match ($state?->value) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'pending' => 'warning',
                        default => 'primary',
                    }),
                TextColumn::make('site.name')->label('Sede')->placeholder('—')->toggleable(),
                TextColumn::make('month')->label('Período')->placeholder('En proceso')->sortable(),
            ])
            ->filters([
                SelectFilter::make('enrollment_source')
                    ->label('Origen del ingreso')
                    ->options(PicsCase::ENROLLMENT_SOURCES),
                SelectFilter::make('month')
                    ->label('Período')
                    ->options(fn (): array => PicsCase::query()
                        ->whereNotNull('month')->where('month', '!=', '')->distinct()->orderByDesc('month')
                        ->pluck('month', 'month')->all())
                    ->searchable(),
                SelectFilter::make('site_id')
                    ->label('Sede')
                    ->relationship('site', 'name'),
                SelectFilter::make('risk_level')
                    ->label('Riesgo PICS')
                    ->options(PicsCase::RISK_LEVELS),
            ])
            ->recordActions([
                Action::make('view')->label('Ver')->icon('heroicon-m-eye')
                    ->url(fn (PicsCase $record): string => PicsCaseResource::getUrl('view', ['record' => $record])),
                Action::make('edit')->label('Editar')->icon('heroicon-m-pencil-square')
                    ->visible(fn (PicsCase $record): bool => PicsCaseResource::canEdit($record))
                    ->url(fn (PicsCase $record): string => PicsCaseResource::getUrl('edit', ['record' => $record])),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('Exportar CSV')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('gray')
                    ->action(fn ($livewire) => CsvExporter::stream(
                        'casos-pics-'.now()->format('Y-m-d').'.csv',
                        ['Caso', 'Paciente', 'Documento', 'Origen', 'Responsable', 'Estado', 'Período'],
                        $livewire->getFilteredTableQuery()->with('patient', 'assignedAuditor')->get()->map(fn (PicsCase $case): array => [
                            $case->case_number,
                            $case->patient?->full_name,
                            $case->patient?->identification,
                            PicsCase::ENROLLMENT_SOURCES[$case->enrollment_source] ?? '',
                            $case->assignedAuditor?->name,
                            $case->status?->label(),
                            $case->month,
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
