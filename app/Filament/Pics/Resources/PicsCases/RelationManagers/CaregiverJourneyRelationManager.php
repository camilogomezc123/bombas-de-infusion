<?php

namespace App\Filament\Pics\Resources\PicsCases\RelationManagers;

use App\Models\CaregiverJourneyStep;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CaregiverJourneyRelationManager extends RelationManager
{
    protected static string $relationship = 'caregiverJourneySteps';

    protected static ?string $title = 'Ruta del cuidador';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Paso')->required()->maxLength(150)->columnSpanFull(),
            Select::make('category')->label('Categoría')->options(CaregiverJourneyStep::CATEGORIES),
            Toggle::make('is_required')->label('Obligatorio')->default(true),
            Textarea::make('description')->label('Descripción / instrucciones')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                TextColumn::make('title')->label('Paso')->searchable(),
                TextColumn::make('category')->label('Categoría')
                    ->formatStateUsing(fn (?string $state): string => CaregiverJourneyStep::CATEGORIES[$state] ?? '—'),
                IconColumn::make('is_required')->label('Obligatorio')->boolean(),
                TextColumn::make('reported_at')->label('Completado por el cuidador')->dateTime('d/m/Y H:i')->placeholder('Pendiente'),
                TextColumn::make('confirmed_at')->label('Confirmado')->dateTime('d/m/Y H:i')->placeholder('Sin confirmar'),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()->mutateFormDataUsing(function (array $data): array {
                    $data['created_by'] = auth('web')->id();

                    return $data;
                }),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('confirm')
                    ->label('Confirmar')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (CaregiverJourneyStep $record): bool => $record->isCompleted() && ! $record->isConfirmed())
                    ->requiresConfirmation()
                    ->action(function (CaregiverJourneyStep $record): void {
                        $record->update(['confirmed_by' => auth('web')->id(), 'confirmed_at' => now()]);
                        Notification::make()->success()->title('Paso confirmado')->send();
                    }),
            ]);
    }
}
