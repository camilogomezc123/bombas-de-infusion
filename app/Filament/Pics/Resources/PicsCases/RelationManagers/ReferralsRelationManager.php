<?php

namespace App\Filament\Pics\Resources\PicsCases\RelationManagers;

use App\Models\PicsReferral;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReferralsRelationManager extends RelationManager
{
    protected static string $relationship = 'referrals';

    protected static ?string $title = 'Remisiones';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('specialty')->label('Especialidad / servicio')->required()->maxLength(150)->columnSpanFull(),
            Select::make('status')->label('Estado')->options(PicsReferral::STATUSES)->default('open')->required(),
            DateTimePicker::make('referred_at')->label('Fecha de remisión')->seconds(false),
            DateTimePicker::make('scheduled_at')->label('Fecha agendada')->seconds(false),
            DateTimePicker::make('completed_at')->label('Fecha de atención')->seconds(false),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('specialty')->label('Especialidad')->searchable(),
                TextColumn::make('status')->label('Estado')->badge()
                    ->formatStateUsing(fn (string $state): string => PicsReferral::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'no_show' => 'danger',
                        'scheduled' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('referred_at')->label('Remitido')->dateTime('d/m/Y')->placeholder('—'),
                TextColumn::make('scheduled_at')->label('Agendado')->dateTime('d/m/Y')->placeholder('—'),
                TextColumn::make('completed_at')->label('Atendido')->dateTime('d/m/Y')->placeholder('—'),
            ])
            ->defaultSort('referred_at', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
