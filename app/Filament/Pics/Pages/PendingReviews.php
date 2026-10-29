<?php

namespace App\Filament\Pics\Pages;

use App\Models\GoalProgressReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * Vista básica de coordinación: reportes de avance y entradas de diario que
 * necesitan atención del equipo, sin importar a qué caso pertenecen.
 */
class PendingReviews extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.case-list';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Seguimiento POSUCI';

    protected static ?string $title = 'Reportes pendientes de revisión';

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'seguimiento';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                GoalProgressReport::query()->whereNull('validated_at')->with(['goal.case', 'reporter'])
            )
            ->columns([
                TextColumn::make('goal.case.case_number')->label('Caso'),
                TextColumn::make('goal.description')->label('Meta')->wrap()->limit(60),
                TextColumn::make('reporter.name')
                    ->label('Reportado por')
                    ->getStateUsing(fn (GoalProgressReport $record): string => $record->reporter?->name ?? $record->reporter?->full_name ?? '—'),
                TextColumn::make('reported_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('notes')->label('Notas')->wrap()->limit(100)->placeholder('—'),
                IconColumn::make('had_difficulty')->label('Dificultad')->boolean(),
            ])
            ->recordActions([
                Action::make('validate')
                    ->label('Validar')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->schema([
                        Textarea::make('validation_notes')->label('Notas de validación'),
                    ])
                    ->action(function (GoalProgressReport $record, array $data): void {
                        $record->update([
                            'validated_by' => auth('web')->id(),
                            'validated_at' => now(),
                            'validation_notes' => $data['validation_notes'] ?? null,
                        ]);

                        Notification::make()->success()->title('Reporte validado')->send();
                    }),
            ])
            ->defaultSort('reported_at')
            ->emptyStateHeading('No hay reportes pendientes de revisión');
    }
}
