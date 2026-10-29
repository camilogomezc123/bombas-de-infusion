<?php

namespace App\Filament\Sepsis\Resources\SepsisCases\RelationManagers;

use App\Models\SepsisPostsepsisFollowup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostsepsisFollowupsRelationManager extends RelationManager
{
    protected static string $relationship = 'postsepsisFollowups';

    protected static ?string $title = 'Recuperación postsepsis';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Seguimiento')->columnSpanFull()->tabs([
                Tab::make('Contacto')->columns(3)->schema([
                    Select::make('checkpoint')->label('Hito')->options(SepsisPostsepsisFollowup::CHECKPOINTS)->required(),
                    DatePicker::make('scheduled_on')->label('Fecha programada'),
                    DateTimePicker::make('contacted_at')->label('Fecha de contacto')->seconds(false),
                    Toggle::make('contact_achieved')->label('Contacto logrado'),
                    Select::make('responsible_user_id')->label('Responsable')->relationship('responsible', 'name')->searchable()->preload(),
                ]),
                Tab::make('Estado clínico y funcional')->columns(3)->schema([
                    Textarea::make('clinical_status')->label('Estado clínico')->columnSpanFull(),
                    TextInput::make('functional_status')->label('Estado funcional'),
                    TextInput::make('mobility')->label('Movilidad'),
                    TextInput::make('cognitive_status')->label('Estado cognitivo (resultado)'),
                    Select::make('cognitive_instrument')->label('Instrumento usado')->options(SepsisPostsepsisFollowup::COGNITIVE_INSTRUMENTS),
                    TextInput::make('emotional_status')->label('Estado emocional'),
                    TextInput::make('nutrition_status')->label('Nutrición'),
                    Toggle::make('medications_reviewed')->label('Medicamentos conciliados'),
                    Toggle::make('rehabilitation_needed')->label('Requiere rehabilitación'),
                    Textarea::make('social_needs')->label('Necesidades sociales')->columnSpanFull(),
                ]),
                Tab::make('Resultado')->columns(3)->schema([
                    Toggle::make('readmission')->label('Reingreso')->live(),
                    Select::make('readmission_reason')->label('Motivo del reingreso')
                        ->options(SepsisPostsepsisFollowup::READMISSION_REASONS)
                        ->visible(fn ($get): bool => (bool) $get('readmission')),
                    Toggle::make('reconsultation')->label('Reconsulta'),
                    Toggle::make('mortality')->label('Mortalidad'),
                    TextInput::make('adherence')->label('Adherencia'),
                    Select::make('recurrence_risk')->label('Riesgo de recurrencia')->options([
                        'low' => 'Bajo', 'medium' => 'Medio', 'high' => 'Alto',
                    ]),
                    Toggle::make('needs_intervention')->label('Necesita intervención'),
                    Textarea::make('barriers')->label('Barreras')->columnSpanFull(),
                ]),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('checkpoint')->label('Hito')
                    ->formatStateUsing(fn (string $state): string => SepsisPostsepsisFollowup::CHECKPOINTS[$state] ?? $state)
                    ->badge(),
                TextColumn::make('scheduled_on')->label('Programado')->date('d/m/Y')->placeholder('—'),
                IconColumn::make('contact_achieved')->label('Contacto logrado')->boolean(),
                IconColumn::make('readmission')->label('Reingreso')->boolean(),
                IconColumn::make('mortality')->label('Mortalidad')->boolean(),
            ])
            ->defaultSort('scheduled_on')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
