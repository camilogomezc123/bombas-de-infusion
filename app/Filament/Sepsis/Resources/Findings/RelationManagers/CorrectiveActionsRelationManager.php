<?php

namespace App\Filament\Sepsis\Resources\Findings\RelationManagers;

use App\Models\CorrectiveAction;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CorrectiveActionsRelationManager extends RelationManager
{
    protected static string $relationship = 'correctiveActions';

    protected static ?string $title = 'Plan PHVA (acciones de mejora)';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('phase')->label('Etapa PHVA')->options(CorrectiveAction::PHASES)->default('do')->required()->live(),
            Select::make('status')->label('Estado')->options(CorrectiveAction::STATUSES)->default('open')->required()->live(),
            Textarea::make('action')
                ->label('Acción (debe iniciar con un verbo en infinitivo: Diseñar, Implementar, Medir, Ajustar…)')
                ->required()
                ->columnSpanFull()
                ->rule(
                    fn () => fn (string $attribute, $value, \Closure $fail) => CorrectiveAction::startsWithInfinitiveVerb($value)
                        ? null
                        : $fail('La acción debe iniciar con un verbo en infinitivo (ej: Diseñar, Implementar, Medir, Ajustar).'),
                ),
            Select::make('responsible_user_id')->label('Responsable')->relationship('responsible', 'name')->searchable()->preload(),
            TextInput::make('progress_percentage')->label('% de avance')->numeric()->minValue(0)->maxValue(100)->default(0)->suffix('%'),
            DatePicker::make('due_on')->label('Fecha compromiso'),
            TextInput::make('frequency')->label('Frecuencia')
                ->visible(fn ($get): bool => $get('phase') === 'check')
                ->required(fn ($get): bool => $get('phase') === 'check'),
            Select::make('indicator_definition_id')
                ->label('Indicador relacionado')
                ->relationship('indicatorDefinition', 'name')
                ->searchable()
                ->preload()
                ->visible(fn ($get): bool => $get('phase') === 'check')
                ->required(fn ($get): bool => $get('phase') === 'check')
                ->helperText('La fuente del indicador se toma de su ficha técnica.'),
            TextInput::make('target')->label('Meta')
                ->visible(fn ($get): bool => $get('phase') === 'check')
                ->required(fn ($get): bool => $get('phase') === 'check'),
            Textarea::make('evidence_expected')->label('Evidencia esperada')->columnSpanFull(),
            FileUpload::make('evidence_file_path')
                ->label('Evidencia cargada')
                ->disk('local')
                ->directory('phva-evidence')
                ->downloadable()
                ->openable()
                ->columnSpanFull()
                ->required(fn ($get): bool => in_array($get('status'), CorrectiveAction::CLOSED_STATUSES, true))
                ->helperText('Obligatoria para cerrar la acción (estado Efectiva o Cerrada).'),
            Textarea::make('effectiveness_verification')
                ->label('Verificación de efectividad')
                ->columnSpanFull()
                ->required(fn ($get): bool => in_array($get('status'), CorrectiveAction::CLOSED_STATUSES, true))
                ->helperText('No se puede cerrar el plan sin documentar la verificación de efectividad.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('phase')->label('Etapa')->formatStateUsing(fn (string $state): string => CorrectiveAction::PHASES[$state] ?? $state)->badge(),
                TextColumn::make('action')->label('Acción')->wrap()->limit(80),
                TextColumn::make('responsible.name')->label('Responsable')->placeholder('Sin asignar'),
                TextColumn::make('progress_percentage')->label('Avance')->suffix('%'),
                TextColumn::make('due_on')->label('Fecha')->date('d/m/Y')->placeholder('Sin fecha'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => CorrectiveAction::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'effective' => 'success',
                        'closed' => 'success',
                        'not_effective' => 'danger',
                        'in_execution' => 'info',
                        'pending_verification' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('due_on')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
