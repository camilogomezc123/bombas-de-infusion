<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                Select::make('records_per_page')
                    ->label('Casos por página en los listados')
                    ->helperText('Cuántos casos se muestran por página en Base de datos, Activos y Egresados.')
                    ->options([25 => 25, 50 => 50, 100 => 100, 200 => 200])
                    ->default(50)
                    ->selectablePlaceholder(false)
                    ->native(false)
                    ->required(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }
}
