<?php

namespace App\Filament\Sepsis\Resources\ProtocolGaps;

use App\Filament\Sepsis\Resources\ProtocolGaps\Pages\CreateProtocolGap;
use App\Filament\Sepsis\Resources\ProtocolGaps\Pages\EditProtocolGap;
use App\Filament\Sepsis\Resources\ProtocolGaps\Pages\ListProtocolGaps;
use App\Models\ProtocolGap;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Actions\EditAction;
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
use Illuminate\Database\Eloquent\Builder;

class ProtocolGapResource extends Resource
{
    protected static ?string $model = ProtocolGap::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string|\UnitEnum|null $navigationGroup = 'Programa';

    protected static ?string $navigationLabel = 'Brechas y ajustes del protocolo';

    protected static ?string $modelLabel = 'brecha del protocolo';

    protected static ?string $pluralModelLabel = 'Brechas y ajustes del protocolo';

    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('clinical_program_id', ProgramAccess::program()?->id)
            ->orderBy('sort_order');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Brecha identificada')
                ->columns(2)
                ->schema([
                    Textarea::make('description')->label('Descripción')->required()->columnSpanFull(),
                    TextInput::make('protocol_section')->label('Sección del protocolo'),
                    Select::make('risk')->label('Riesgo')->options([
                        'low' => 'Bajo', 'medium' => 'Medio', 'high' => 'Alto',
                    ]),
                    Select::make('priority')->label('Prioridad')->options([
                        'low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta',
                    ])->default('medium')->required(),
                    Select::make('responsible_user_id')->label('Responsable')->relationship('responsible', 'name')->searchable()->preload(),
                ]),
            Section::make('Revisión del Comité Institucional de Sepsis')
                ->columns(2)
                ->schema([
                    Select::make('status')->label('Estado')->options([
                        'under_review' => 'En revisión',
                        'approved' => 'Aprobada por el comité',
                        'rejected' => 'No procede',
                        'implemented' => 'Ajuste implementado',
                    ])->default('under_review')->required(),
                    DatePicker::make('closed_on')->label('Fecha de cierre'),
                    Textarea::make('decision')->label('Decisión del comité')->columnSpanFull()
                        ->helperText('No se modifican recomendaciones clínicas automáticamente: esta brecha se muestra para revisión y aprobación por el Comité Institucional de Sepsis.'),
                    Textarea::make('proposed_adjustment')->label('Ajuste propuesto')->columnSpanFull(),
                    TextInput::make('evidence_reference')->label('Evidencia')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')->label('Brecha')->wrap()->limit(100),
                TextColumn::make('protocol_section')->label('Sección')->placeholder('—'),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', default => '—',
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'high' => 'danger', 'medium' => 'warning', default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'under_review' => 'En revisión',
                        'approved' => 'Aprobada',
                        'rejected' => 'No procede',
                        'implemented' => 'Implementada',
                        default => $state,
                    })
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options([
                    'under_review' => 'En revisión', 'approved' => 'Aprobada',
                    'rejected' => 'No procede', 'implemented' => 'Implementada',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProtocolGaps::route('/'),
            'create' => CreateProtocolGap::route('/create'),
            'edit' => EditProtocolGap::route('/{record}/edit'),
        ];
    }
}
