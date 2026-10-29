<?php

namespace App\Filament\Sepsis\Resources\Findings;

use App\Filament\Sepsis\Resources\Findings\Pages\CreateFinding;
use App\Filament\Sepsis\Resources\Findings\Pages\EditFinding;
use App\Filament\Sepsis\Resources\Findings\Pages\ListFindings;
use App\Filament\Sepsis\Resources\Findings\RelationManagers\CorrectiveActionsRelationManager;
use App\Models\AssessmentFinding;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FindingResource extends Resource
{
    protected static ?string $model = AssessmentFinding::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|\UnitEnum|null $navigationGroup = 'Calidad y mejora';

    protected static ?string $navigationLabel = 'Hallazgos y acciones';

    protected static ?string $modelLabel = 'hallazgo';

    protected static ?string $pluralModelLabel = 'Hallazgos y acciones';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas(
                'assessment.element.standard.chapter.framework.program',
                fn (Builder $query) => $query->where('code', ProgramAccess::currentCode()),
            )
            ->with(['assessment.element', 'correctiveActions']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('compliance_assessment_id')
                ->label('Evaluación de cumplimiento')
                ->relationship(
                    'assessment',
                    'id',
                    fn (Builder $query) => $query->whereHas(
                        'element.standard.chapter.framework.program',
                        fn (Builder $query) => $query->where('code', ProgramAccess::currentCode()),
                    ),
                )
                ->getOptionLabelFromRecordUsing(fn ($record): string => $record->element?->code.' · '.$record->element?->name)
                ->searchable()
                ->preload()
                ->required()
                ->columnSpanFull(),
            Select::make('severity')->label('Severidad')->options([
                'low' => 'Leve', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica',
            ])->required(),
            Select::make('status')->label('Estado')->options([
                'open' => 'Abierto', 'in_review' => 'En revisión', 'closed' => 'Cerrado',
            ])->default('open')->required(),
            Textarea::make('description')->label('Descripción del hallazgo')->required()->columnSpanFull(),

            Section::make('Análisis de los 5 Porqués')
                ->columnSpanFull()
                ->columns(1)
                ->schema([
                    Textarea::make('why_1')->label('¿Por qué 1? — ¿Por qué existe esta brecha?'),
                    Textarea::make('why_2')->label('¿Por qué 2? — ¿Por qué ocurre lo anterior?'),
                    Textarea::make('why_3')->label('¿Por qué 3? — ¿Por qué sucede eso?'),
                    Textarea::make('why_4')->label('¿Por qué 4? — ¿Por qué ocurre esa situación estructural o sistémica?'),
                    Textarea::make('why_5')->label('¿Por qué 5? — ¿Por qué se presenta finalmente esa condición?'),
                ]),

            Section::make('Causa raíz')
                ->columnSpanFull()
                ->schema([
                    Textarea::make('root_cause')
                        ->label('Causa raíz')
                        ->helperText('¿Qué proceso falla? ¿Cuál es el origen real? ¿Qué factor lo genera? ¿Dónde debe intervenirse?')
                        ->columnSpanFull(),
                ]),

            Section::make('Validaciones obligatorias')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Checkbox::make('validation_explains_gap')->label('La causa raíz explica la brecha'),
                    Checkbox::make('validation_logical_continuity')->label('Existe continuidad lógica entre los porqués'),
                    Checkbox::make('validation_no_repeated_causes')->label('No se repiten causas'),
                    Checkbox::make('validation_not_symptom')->label('No se confunden síntomas con causas'),
                    Checkbox::make('validation_intervention_reduces_gap')->label('La intervención sobre la causa raíz reduciría la brecha'),
                    Checkbox::make('validation_not_only_consequence')->label('Las acciones no atacan únicamente consecuencias'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('assessment.element.code')->label('Elemento')->badge(),
                TextColumn::make('description')->label('Hallazgo')->wrap()->limit(80),
                TextColumn::make('severity')
                    ->label('Severidad')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Leve', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica', default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger', 'high' => 'warning', 'medium' => 'info', default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Abierto', 'in_review' => 'En revisión', 'closed' => 'Cerrado', default => $state,
                    })
                    ->badge(),
                TextColumn::make('correctiveActions_count')->label('Acciones')->counts('correctiveActions'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options([
                    'open' => 'Abierto', 'in_review' => 'En revisión', 'closed' => 'Cerrado',
                ]),
                SelectFilter::make('severity')->label('Severidad')->options([
                    'low' => 'Leve', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [CorrectiveActionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFindings::route('/'),
            'create' => CreateFinding::route('/create'),
            'edit' => EditFinding::route('/{record}/edit'),
        ];
    }
}
