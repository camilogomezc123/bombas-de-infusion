<?php

namespace App\Filament\Pics\Resources\PicsCases\RelationManagers;

use App\Models\PicsFollowup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FollowupsRelationManager extends RelationManager
{
    protected static string $relationship = 'followups';

    protected static ?string $title = 'Seguimientos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Seguimiento')->columnSpanFull()->persistTabInQueryString('seguimiento_tab')->tabs([
                Tab::make('Contacto')->columns(3)->schema([
                    Select::make('checkpoint')->label('Checkpoint')->options(PicsFollowup::CHECKPOINTS)->required()->live(),
                    Select::make('respondent_type')->label('Respondido por')->options(PicsFollowup::RESPONDENT_TYPES)->default('paciente')->required()->live(),
                    Select::make('contact_method')->label('Método de contacto')->options([
                        'presencial' => 'Presencial', 'telefonico' => 'Telefónico', 'telemedicina' => 'Telemedicina',
                    ]),
                    Toggle::make('contact_achieved')->label('Contacto logrado'),
                    DateTimePicker::make('followed_up_at')->label('Fecha de seguimiento')->seconds(false),
                    Select::make('responsible_user_id')->label('Responsable')->relationship('responsible', 'name')->searchable()->preload(),
                ]),

                Tab::make('Disfagia')->visible(fn ($get): bool => $get('checkpoint') === '48_72h')->schema([
                    Select::make('disfagia')->label('Tamizaje de disfagia')->options(PicsFollowup::DISFAGIA_OPTIONS),
                ]),

                Tab::make('PICS-F (cuidador)')->visible(fn ($get): bool => $get('respondent_type') === 'familia')->schema([
                    Group::make()->statePath('picsf_respuestas')->columns(1)
                        ->schema(collect(PicsFollowup::PICSF_ITEMS)->map(
                            fn (array $item, int $i) => Select::make((string) $i)->label($item[0])
                                ->options(array_combine(range(0, count($item[1]) - 1), $item[1]))
                        )->values()->all()),
                ]),

                Tab::make('Pfeiffer / AMT')->schema([
                    Section::make('Marca "Correcto" si respondió bien. El puntaje son los errores (0-10).')
                        ->schema([
                            Group::make()->statePath('amt_respuestas')->columns(2)
                                ->schema(collect(PicsFollowup::AMT_ITEMS)->map(
                                    fn (string $label, int $i) => Toggle::make((string) $i)->label($label)->inline(false)->live()
                                )->values()->all()),
                        ]),
                ]),

                Tab::make('MoCA')
                    ->visible(function ($get): bool {
                        $respuestas = $get('amt_respuestas');
                        if (! is_array($respuestas) || count($respuestas) !== count(PicsFollowup::AMT_ITEMS)) {
                            return false;
                        }
                        $errores = count(PicsFollowup::AMT_ITEMS) - count(array_filter($respuestas));

                        return $errores >= 3;
                    })
                    ->schema([
                        Section::make('Solo se administra cuando el Pfeiffer/AMT tiene 3 o más errores.')
                            ->schema([
                                Group::make()->statePath('moca_respuestas')->columns(3)
                                    ->schema(collect(PicsFollowup::MOCA_DOMAINS)->map(
                                        fn (string $label, string $key) => TextInput::make($key)->label($label)->numeric()->minValue(0)
                                    )->values()->all()),
                            ]),
                    ]),

                Tab::make('HADS-A (ansiedad)')->schema([
                    Group::make()->statePath('hads_respuestas')->columns(1)
                        ->schema(collect(PicsFollowup::HADS_ITEMS)->map(
                            fn (array $item, int $i) => Select::make((string) $i)->label($item[0])
                                ->options(array_combine(range(0, count($item[1]) - 1), $item[1]))
                        )->values()->all()),
                ]),

                Tab::make('PHQ-9 (depresión)')->schema([
                    Group::make()->statePath('phq9_respuestas')->columns(1)
                        ->schema(collect(PicsFollowup::PHQ9_ITEMS)->map(
                            fn (string $label, int $i) => Select::make((string) $i)->label($label)->options([
                                0 => 'Nunca', 1 => 'Varios días', 2 => 'La mitad de los días o más', 3 => 'Casi todos los días',
                            ])
                        )->values()->all()),
                ]),

                Tab::make('PC-PTSD-5')->schema([
                    Group::make()->statePath('pcptsd_respuestas')->columns(1)
                        ->schema(collect(PicsFollowup::PCPTSD_ITEMS)->map(
                            fn (string $label, int $i) => Toggle::make((string) $i)->label($label)->inline(false)
                        )->values()->all()),
                ]),

                Tab::make('Fatiga y dolor')->columns(3)->schema([
                    TextInput::make('fatigue_score')->label('Fatiga (NRS 0-10)')->numeric()->step('0.1')->minValue(0)->maxValue(10),
                    TextInput::make('pain_rest')->label('Dolor en reposo (NRS 0-10)')->numeric()->step('0.1')->minValue(0)->maxValue(10),
                    TextInput::make('pain_movement')->label('Dolor con movimiento (NRS 0-10)')->numeric()->step('0.1')->minValue(0)->maxValue(10),
                    TextInput::make('functional_capacity')->label('Capacidad funcional'),
                    TextInput::make('mobility')->label('Movilidad'),
                    TextInput::make('strength')->label('Fuerza'),
                    TextInput::make('sleep_quality')->label('Calidad del sueño'),
                ]),

                Tab::make('PTG-SF')->visible(fn ($get): bool => in_array($get('checkpoint'), PicsFollowup::PTG_CHECKPOINTS, true))->schema([
                    Group::make()->statePath('ptg_respuestas')->columns(1)
                        ->schema(collect(PicsFollowup::PTG_ITEMS)->map(
                            fn (string $label, int $i) => Select::make((string) $i)->label($label)->options([
                                0 => '0 - Nada', 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5 - En gran medida',
                            ])
                        )->values()->all()),
                ]),

                Tab::make('Resultado')->columns(3)->schema([
                    Toggle::make('readmission')->label('Reingreso'),
                    Toggle::make('return_to_work')->label('Retorno laboral'),
                    Textarea::make('medications_review')->label('Conciliación de medicamentos')->columnSpanFull(),
                    Textarea::make('notes')->label('Notas')->columnSpanFull(),
                ]),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('checkpoint')->label('Checkpoint')
                    ->formatStateUsing(fn (string $state): string => PicsFollowup::CHECKPOINTS[$state] ?? $state)
                    ->badge(),
                TextColumn::make('respondent_type')->label('Respondido por')
                    ->formatStateUsing(fn (string $state): string => PicsFollowup::RESPONDENT_TYPES[$state] ?? $state),
                TextColumn::make('followed_up_at')->label('Fecha')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('amt_score')->label('AMT (errores)')->badge()
                    ->color(fn (?int $state, PicsFollowup $record): string => match ($record->semaforoAmt()) {
                        'verde' => 'success', 'amarillo' => 'warning', 'rojo' => 'danger', default => 'gray',
                    })->placeholder('—'),
                TextColumn::make('hads_ansiedad')->label('HADS-A')->badge()
                    ->color(fn (PicsFollowup $record): string => match ($record->semaforoAnsiedad()) {
                        'verde' => 'success', 'amarillo' => 'warning', 'rojo' => 'danger', default => 'gray',
                    })->placeholder('—'),
                TextColumn::make('phq9_score')->label('PHQ-9')->badge()
                    ->color(fn (PicsFollowup $record): string => match ($record->semaforoDepresion()) {
                        'verde' => 'success', 'amarillo' => 'warning', 'rojo' => 'danger', default => 'gray',
                    })->placeholder('—'),
                TextColumn::make('pcptsd_score')->label('PC-PTSD-5')->badge()
                    ->color(fn (PicsFollowup $record): string => match ($record->semaforoPtsd()) {
                        'verde' => 'success', 'amarillo' => 'warning', 'rojo' => 'danger', default => 'gray',
                    })->placeholder('—'),
                IconColumn::make('is_self_submitted')->label('Origen portal')->boolean()
                    ->getStateUsing(fn (PicsFollowup $record): bool => $record->isSelfSubmitted()),
                IconColumn::make('confirmed_at')->label('Confirmado')->boolean()
                    ->getStateUsing(fn (PicsFollowup $record): bool => $record->isConfirmed()),
            ])
            ->defaultSort('followed_up_at')
            ->recordActions([
                Action::make('confirm')
                    ->label('Confirmar')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (PicsFollowup $record): bool => $record->isSelfSubmitted() && ! $record->isConfirmed())
                    ->action(function (PicsFollowup $record): void {
                        $record->update(['confirmed_by' => auth('web')->id(), 'confirmed_at' => now()]);
                    }),
                EditAction::make()->mutateFormDataUsing(fn (array $data): array => PicsFollowup::computeScores($data)),
            ])
            ->headerActions([
                CreateAction::make()->mutateFormDataUsing(fn (array $data): array => PicsFollowup::computeScores($data)),
            ]);
    }
}
