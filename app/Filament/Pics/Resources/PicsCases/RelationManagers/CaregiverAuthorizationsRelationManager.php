<?php

namespace App\Filament\Pics\Resources\PicsCases\RelationManagers;

use App\Models\CaregiverAuthorization;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CaregiverAuthorizationsRelationManager extends RelationManager
{
    protected static string $relationship = 'caregiverAuthorizations';

    protected static ?string $title = 'Familia y cuidadores autorizados';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('caregiver_id')
                ->label('Cuidador')
                ->relationship('caregiver', 'name')
                ->searchable(['name', 'email'])
                ->preload()
                ->required()
                ->createOptionForm([
                    TextInput::make('name')->label('Nombre completo')->required()->maxLength(150),
                    TextInput::make('email')->label('Correo electrónico')->email()->required()->unique('caregivers', 'email'),
                    TextInput::make('phone')->label('Teléfono')->tel(),
                ]),
            TextInput::make('relationship')->label('Parentesco / relación con el paciente')->maxLength(80),
            Toggle::make('is_primary')->label('Cuidador principal')
                ->helperText('Identifica a la persona de referencia para coordinar el cuidado.'),
            Toggle::make('is_emergency_contact')->label('Contacto de emergencia'),
            Toggle::make('participates_in_decisions')->label('Participa en decisiones compartidas'),
            TextInput::make('preferred_language')->label('Idioma preferido')->maxLength(40),
            Select::make('support_level')->label('Nivel de acompañamiento')->options([
                'permanente' => 'Permanente',
                'frecuente' => 'Frecuente',
                'ocasional' => 'Ocasional',
                'remoto' => 'A distancia',
            ]),
            TextInput::make('communication_notes')->label('Necesidades de comunicación')->maxLength(500)
                ->columnSpanFull(),
            Toggle::make('can_write_diary')->label('Puede escribir en el diario')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('caregiver.name')->label('Nombre'),
                TextColumn::make('caregiver.email')->label('Correo'),
                TextColumn::make('relationship')->label('Parentesco')->placeholder('—'),
                IconColumn::make('is_primary')->label('Principal')->boolean(),
                IconColumn::make('is_emergency_contact')->label('Emergencia')->boolean(),
                IconColumn::make('participates_in_decisions')->label('Decisiones')->boolean(),
                TextColumn::make('preferred_language')->label('Idioma')->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('support_level')->label('Acompañamiento')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'permanente' => 'Permanente',
                        'frecuente' => 'Frecuente',
                        'ocasional' => 'Ocasional',
                        'remoto' => 'A distancia',
                        default => '—',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('can_write_diary')->label('Escribe diario')->boolean(),
                TextColumn::make('authorized_at')->label('Autorizado')->dateTime('d/m/Y H:i'),
                IconColumn::make('active')->label('Activo')->boolean()
                    ->getStateUsing(fn (CaregiverAuthorization $record): bool => $record->isActive()),
                TextColumn::make('caregiver.last_login_at')->label('Último ingreso')->dateTime('d/m/Y H:i')->placeholder('Nunca'),
            ])
            ->headerActions([
                CreateAction::make()->mutateFormDataUsing(function (array $data): array {
                    $data['authorized_by'] = auth('web')->id();
                    $data['authorized_at'] = now();

                    return $data;
                }),
            ])
            ->recordActions([
                Action::make('invite')
                    ->label('Enviar invitación')
                    ->icon('heroicon-m-envelope')
                    ->color('primary')
                    ->visible(fn (CaregiverAuthorization $record): bool => $record->isActive())
                    ->requiresConfirmation()
                    ->modalDescription('Se generará una contraseña temporal nueva y se enviará por correo al cuidador.')
                    ->action(function (CaregiverAuthorization $record): void {
                        $record->caregiver->sendPortalInvitation();
                        Notification::make()->success()->title('Invitación enviada')->send();
                    }),
                Action::make('revoke')
                    ->label('Revocar acceso')
                    ->icon('heroicon-m-no-symbol')
                    ->color('danger')
                    ->visible(fn (CaregiverAuthorization $record): bool => $record->isActive())
                    ->requiresConfirmation()
                    ->action(function (CaregiverAuthorization $record): void {
                        $record->update(['revoked_by' => auth('web')->id(), 'revoked_at' => now()]);
                        Notification::make()->success()->title('Acceso revocado')->send();
                    }),
            ])
            ->defaultSort('authorized_at', 'desc');
    }

    public function canEdit($record): bool
    {
        return false;
    }

    public function canDelete($record): bool
    {
        return false;
    }
}
