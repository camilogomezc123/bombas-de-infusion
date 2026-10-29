<?php

namespace App\Filament\Pics\Resources\MedicationReconciliations\RelationManagers;

use App\Models\MedicationReconciliationItem;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Medicamentos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('medication_name')->label('Medicamento')->required()->maxLength(150)->columnSpanFull(),
            TextInput::make('dose')->label('Dosis'),
            Select::make('route')->label('Vía')->options(MedicationReconciliationItem::ROUTES),
            TextInput::make('frequency')->label('Frecuencia'),
            Repeater::make('schedule_times')
                ->label('Horarios (opcional, para que aparezca en el calendario del paciente)')
                ->simple(TimePicker::make('time')->seconds(false)->required())
                ->addActionLabel('Agregar hora')
                ->columnSpanFull(),
            Select::make('status')->label('Decisión de conciliación')->options(MedicationReconciliationItem::STATUSES)->default('continua')->required(),
            Textarea::make('reconciliation_notes')->label('Notas de conciliación (motivo del cambio/suspensión)')->columnSpanFull(),
            Textarea::make('patient_instructions')->label('Instrucciones para el paciente/familia')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('medication_name')->label('Medicamento')->searchable(),
                TextColumn::make('dose')->label('Dosis')->placeholder('—'),
                TextColumn::make('route')->label('Vía')
                    ->formatStateUsing(fn (?string $state): string => MedicationReconciliationItem::ROUTES[$state] ?? '—'),
                TextColumn::make('frequency')->label('Frecuencia')->placeholder('—'),
                TextColumn::make('status')->label('Decisión')->badge()
                    ->formatStateUsing(fn (MedicationReconciliationItem $record): string => $record->statusLabel())
                    ->color(fn (string $state): string => match ($state) {
                        'suspendida' => 'danger',
                        'ajustada' => 'warning',
                        'nueva' => 'info',
                        default => 'success',
                    }),
            ])
            ->defaultSort('sort_order')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
