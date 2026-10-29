<?php

namespace App\Filament\Infarto\Resources\AcsCases\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RehabilitationReferralsRelationManager extends RelationManager
{
    protected static string $relationship = 'rehabilitationReferrals';

    protected static ?string $title = 'Rehabilitación cardiaca';

    public function form(Schema $s): Schema
    {
        return $s->columns(3)->components([DatePicker::make('referred_on')->label('Remisión'), DatePicker::make('contacted_on')->label('Contacto'), DatePicker::make('started_on')->label('Inicio'), Select::make('modality')->label('Modalidad')->options(['onsite' => 'Presencial', 'home' => 'Domiciliaria', 'hybrid' => 'Híbrida']), TextInput::make('sessions_planned')->label('Sesiones programadas')->numeric(), TextInput::make('sessions_completed')->label('Sesiones realizadas')->numeric(), Select::make('status')->label('Estado')->options(['referred' => 'Remitido', 'contacted' => 'Contactado', 'active' => 'En curso', 'completed' => 'Finalizado', 'declined' => 'Rechazado', 'lost' => 'No localizado']), Textarea::make('barriers')->label('Barreras')->columnSpanFull()]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('referred_on')->label('Remisión')->date('d/m/Y'), TextColumn::make('started_on')->label('Inicio')->date('d/m/Y'), TextColumn::make('modality')->label('Modalidad')->badge(), TextColumn::make('status')->label('Estado')->badge(), TextColumn::make('sessions_completed')->label('Sesiones')])->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
