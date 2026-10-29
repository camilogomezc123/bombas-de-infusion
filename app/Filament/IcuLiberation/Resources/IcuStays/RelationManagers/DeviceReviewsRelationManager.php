<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers;

use App\Models\IcuDeviceReview;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeviceReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'deviceReviews';

    protected static ?string $title = 'Dispositivos';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([
            Select::make('device_type')->label('Dispositivo')->options(IcuDeviceReview::DEVICE_TYPES)->required(),
            DateTimePicker::make('placed_at')->label('Fecha de colocación')->seconds(false),
            DateTimePicker::make('reviewed_at')->label('Fecha de revisión')->seconds(false)->required(),
            Toggle::make('still_needed')->label('Sigue siendo necesario'),
            DateTimePicker::make('removed_at')->label('Fecha de retiro')->seconds(false),
            Toggle::make('unplanned_removal')->label('Retiro accidental'),
        ]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([
            TextColumn::make('device_type')->label('Dispositivo')->formatStateUsing(fn (string $s) => IcuDeviceReview::DEVICE_TYPES[$s] ?? $s)->badge(),
            TextColumn::make('reviewed_at')->label('Revisión')->dateTime('d/m/Y H:i')->sortable(),
            IconColumn::make('still_needed')->label('Necesario')->boolean(),
            IconColumn::make('unplanned_removal')->label('Retiro accidental')->boolean(),
        ])->defaultSort('reviewed_at', 'desc')->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
