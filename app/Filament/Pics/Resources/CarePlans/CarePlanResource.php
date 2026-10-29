<?php

namespace App\Filament\Pics\Resources\CarePlans;

use App\Filament\Pics\Resources\CarePlans\Pages\CreateCarePlan;
use App\Filament\Pics\Resources\CarePlans\Pages\EditCarePlan;
use App\Filament\Pics\Resources\CarePlans\Pages\ListCarePlans;
use App\Filament\Pics\Resources\CarePlans\RelationManagers\VersionsRelationManager;
use App\Models\CarePlan;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CarePlanResource extends Resource
{
    protected static ?string $model = CarePlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Plan interdisciplinario';

    protected static ?string $modelLabel = 'plan interdisciplinario';

    protected static ?string $pluralModelLabel = 'Planes interdisciplinarios';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('pics_case_id')
                ->label('Caso')
                ->relationship('case', 'case_number')
                ->searchable()->preload()->required()
                ->disabled(fn (string $operation): bool => $operation === 'edit'),

            Tabs::make('Plan')->columnSpanFull()->tabs([
                Tab::make('Objetivo y criterios de egreso')->schema([
                    Textarea::make('general_objective')->label('Objetivo general del plan')->rows(3)->columnSpanFull(),
                    Textarea::make('discharge_criteria')->label('Criterios de egreso')->rows(3)->columnSpanFull(),
                ]),
                Tab::make('Plan por disciplina')->schema([
                    Repeater::make('disciplines')
                        ->label('')
                        ->schema([
                            Select::make('discipline')->label('Disciplina')->options(CarePlan::DISCIPLINES)->required(),
                            Select::make('responsible_user_id')->label('Responsable')
                                ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                                ->searchable(),
                            Textarea::make('objective')->label('Objetivo de la disciplina')->rows(2)->columnSpanFull(),
                            Textarea::make('interventions')->label('Intervenciones')->rows(2)->columnSpanFull(),
                            TextInput::make('frequency')->label('Frecuencia'),
                        ])
                        ->columns(2)
                        ->addActionLabel('Agregar disciplina')
                        ->columnSpanFull(),
                ]),
                Tab::make('Versión')->columns(2)->schema([
                    Placeholder::make('version_display')->label('Versión actual')
                        ->content(fn (?CarePlan $record): string => $record ? (string) $record->version : '1'),
                    Placeholder::make('effective_from_display')->label('Vigente desde')
                        ->content(fn (?CarePlan $record): string => $record?->effective_from?->format('d/m/Y') ?? 'Sin definir'),
                    Placeholder::make('updated_by_display')->label('Última modificación')->columnSpanFull()
                        ->content(fn (?CarePlan $record): string => $record?->updatedBy
                            ? $record->updatedBy->name.' · '.$record->updated_at?->format('d/m/Y H:i')
                            : 'Sin modificaciones todavía'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('case.case_number')->label('Caso')->searchable()->sortable(),
                TextColumn::make('case.patient.full_name')->label('Paciente'),
                TextColumn::make('version')->label('Versión')->badge(),
                TextColumn::make('effective_from')->label('Vigente desde')->date('d/m/Y')->placeholder('Sin definir'),
                TextColumn::make('updatedBy.name')->label('Última modificación por')->placeholder('—'),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCarePlans::route('/'),
            'create' => CreateCarePlan::route('/create'),
            'edit' => EditCarePlan::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            VersionsRelationManager::class,
        ];
    }
}
