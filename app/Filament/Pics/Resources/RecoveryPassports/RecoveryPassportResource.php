<?php

namespace App\Filament\Pics\Resources\RecoveryPassports;

use App\Filament\Pics\Resources\RecoveryPassports\Pages\CreateRecoveryPassport;
use App\Filament\Pics\Resources\RecoveryPassports\Pages\EditRecoveryPassport;
use App\Filament\Pics\Resources\RecoveryPassports\Pages\ListRecoveryPassports;
use App\Filament\Pics\Resources\RecoveryPassports\RelationManagers\ItemsRelationManager;
use App\Models\RecoveryPassport;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RecoveryPassportResource extends Resource
{
    protected static ?string $model = RecoveryPassport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Pasaporte de recuperación';

    protected static ?string $modelLabel = 'pasaporte de recuperación';

    protected static ?string $pluralModelLabel = 'Pasaportes de recuperación';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('pics_case_id')
                ->label('Caso')
                ->relationship('case', 'case_number')
                ->searchable()->preload()->required()
                ->disabled(fn (string $operation): bool => $operation === 'edit'),

            Tabs::make('Pasaporte')->columnSpanFull()->tabs([
                Tab::make('Antes de la UCI')->columns(2)->schema([
                    Textarea::make('mobility_before')->label('Movilidad antes del ingreso')->columnSpanFull(),
                    Textarea::make('autonomy_before')->label('Autonomía antes del ingreso')->columnSpanFull(),
                    Textarea::make('habitual_activities')->label('Actividades habituales')->columnSpanFull(),
                    Textarea::make('supports_before')->label('Apoyos con los que contaba')->columnSpanFull(),
                ]),
                Tab::make('Situación actual')->schema([
                    Textarea::make('current_situation')->label('Situación actual')->rows(4)->columnSpanFull(),
                ]),
                Tab::make('Barreras')->columns(2)->schema([
                    Textarea::make('home_barriers')->label('Barreras del hogar'),
                    Textarea::make('transport_barriers')->label('Barreras de transporte'),
                    Textarea::make('companion_barriers')->label('Barreras de acompañamiento'),
                    Textarea::make('access_barriers')->label('Barreras de acceso a servicios'),
                ]),
                Tab::make('Responsable y confirmación')->columns(2)->schema([
                    Select::make('responsible_user_id')->label('Profesional/equipo responsable')->relationship('responsible', 'name')->searchable()->preload(),
                    Section::make('')->schema([
                        Placeholder::make('reported')
                            ->label('Reportado por')
                            ->content(fn (?RecoveryPassport $record): string => $record?->reportedBy
                                ? ($record->reportedBy->name ?? $record->reportedBy->full_name).' · '.$record->reported_at?->format('d/m/Y H:i')
                                : 'Sin diligenciar todavía'),
                        Placeholder::make('confirmed')
                            ->label('Confirmación profesional')
                            ->content(fn (?RecoveryPassport $record): string => $record?->is_confirmed
                                ? 'Confirmado por '.$record->confirmedBy?->name.' · '.$record->confirmed_at?->format('d/m/Y H:i')
                                : 'Pendiente de confirmar'),
                    ]),
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
                IconColumn::make('is_confirmed')->label('Confirmado')->boolean(),
                TextColumn::make('reported_at')->label('Reportado')->dateTime('d/m/Y H:i')->placeholder('Sin diligenciar'),
                TextColumn::make('responsible.name')->label('Responsable')->placeholder('Sin asignar'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecoveryPassports::route('/'),
            'create' => CreateRecoveryPassport::route('/create'),
            'edit' => EditRecoveryPassport::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }
}
