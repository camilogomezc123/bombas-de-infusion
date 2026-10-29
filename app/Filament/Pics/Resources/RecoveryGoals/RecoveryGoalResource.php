<?php

namespace App\Filament\Pics\Resources\RecoveryGoals;

use App\Filament\Pics\Resources\RecoveryGoals\Pages\CreateRecoveryGoal;
use App\Filament\Pics\Resources\RecoveryGoals\Pages\EditRecoveryGoal;
use App\Filament\Pics\Resources\RecoveryGoals\Pages\ListRecoveryGoals;
use App\Filament\Pics\Resources\RecoveryGoals\RelationManagers\ProgressReportsRelationManager;
use App\Models\RecoveryGoal;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RecoveryGoalResource extends Resource
{
    protected static ?string $model = RecoveryGoal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'Metas de recuperación';

    protected static ?string $modelLabel = 'meta de recuperación';

    protected static ?string $pluralModelLabel = 'Metas de recuperación';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Meta')->columns(2)->schema([
                Select::make('pics_case_id')
                    ->label('Caso')
                    ->relationship('case', 'case_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('domain')
                    ->label('Dominio')
                    ->options([
                        'movilidad' => 'Movilidad',
                        'fuerza' => 'Fuerza',
                        'deglucion' => 'Deglución',
                        'cognicion' => 'Cognición',
                        'animo' => 'Ánimo / bienestar emocional',
                        'actividad_cotidiana' => 'Actividad cotidiana',
                        'otro' => 'Otro',
                    ])
                    ->required(),
                Textarea::make('description')->label('Descripción (en lenguaje comprensible)')->required()->columnSpanFull(),
                TextInput::make('measure')->label('Medida'),
                TextInput::make('unit')->label('Unidad'),
                TextInput::make('assistance_level')->label('Nivel de ayuda'),
                DatePicker::make('target_date')->label('Fecha objetivo'),
                Select::make('responsible_user_id')->label('Profesional responsable')->relationship('responsible', 'name')->searchable()->preload(),
                Select::make('status')->label('Estado')->options(RecoveryGoal::STATUSES)->default('active')->required(),
                Textarea::make('restrictions')->label('Restricciones indicadas')->columnSpanFull(),
                Textarea::make('review_criteria')->label('Criterio de revisión')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('case.case_number')->label('Caso')->searchable()->sortable(),
                TextColumn::make('domain')->label('Dominio')->badge(),
                TextColumn::make('description')->label('Descripción')->wrap()->limit(80),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => RecoveryGoal::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success', 'paused' => 'warning', default => 'gray',
                    }),
                TextColumn::make('responsible.name')->label('Responsable')->placeholder('Sin asignar'),
                TextColumn::make('target_date')->label('Fecha objetivo')->date('d/m/Y')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(RecoveryGoal::STATUSES),
                SelectFilter::make('domain')->label('Dominio')->options([
                    'movilidad' => 'Movilidad', 'fuerza' => 'Fuerza', 'deglucion' => 'Deglución',
                    'cognicion' => 'Cognición', 'animo' => 'Ánimo / bienestar emocional',
                    'actividad_cotidiana' => 'Actividad cotidiana', 'otro' => 'Otro',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecoveryGoals::route('/'),
            'create' => CreateRecoveryGoal::route('/create'),
            'edit' => EditRecoveryGoal::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ProgressReportsRelationManager::class,
        ];
    }
}
