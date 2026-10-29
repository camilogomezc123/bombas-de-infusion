<?php

namespace App\Filament\Pics\Resources\DischargeReadinessChecks\RelationManagers;

use App\Models\DischargeReadinessItem;
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

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Temas del recorrido de comprensión';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('topic')->label('Tema')->options(DischargeReadinessItem::TOPICS)->required()->live(),
            TextInput::make('custom_topic')->label('Especifique')
                ->visible(fn ($get): bool => $get('topic') === 'otro'),
            Select::make('verification_method')->label('Método de verificación')->options(DischargeReadinessItem::VERIFICATION_METHODS),
            Textarea::make('staff_instructions')->label('Instrucciones para el paciente/familia')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('topic')->label('Tema')
                    ->formatStateUsing(fn (DischargeReadinessItem $record): string => $record->topicLabel()),
                IconColumn::make('reviewed_at')->label('Revisado por familia')->boolean()
                    ->getStateUsing(fn (DischargeReadinessItem $record): bool => $record->reviewed_at !== null),
                IconColumn::make('understood')->label('Comprensión verificada')->boolean(),
                TextColumn::make('verifiedBy.name')->label('Verificado por')->placeholder('—'),
            ])
            ->defaultSort('sort_order')
            ->headerActions([CreateAction::make()])
            ->recordActions([
                EditAction::make(),
                Action::make('verify')
                    ->label('Confirmar comprensión')
                    ->icon('heroicon-m-academic-cap')
                    ->color('success')
                    ->schema([
                        Toggle::make('understood')->label('El paciente/familia demuestra comprensión')->default(true),
                    ])
                    ->action(function (DischargeReadinessItem $record, array $data): void {
                        $record->update([
                            'understood' => $data['understood'],
                            'verified_by' => auth('web')->id(),
                            'verified_at' => now(),
                        ]);
                        Notification::make()->success()->title('Comprensión registrada')->send();
                    }),
            ]);
    }
}
