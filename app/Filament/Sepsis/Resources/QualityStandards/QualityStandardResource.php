<?php

namespace App\Filament\Sepsis\Resources\QualityStandards;

use App\Filament\Sepsis\Resources\QualityStandards\Pages\ListQualityStandards;
use App\Filament\Sepsis\Resources\QualityStandards\Pages\ViewQualityStandard;
use App\Models\AccreditationStandard;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QualityStandardResource extends Resource
{
    protected static ?string $model = AccreditationStandard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Calidad y mejora';

    protected static ?string $navigationLabel = 'Estándares';

    protected static ?string $modelLabel = 'estándar de calidad';

    protected static ?string $pluralModelLabel = 'Estándares de calidad';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('chapter.framework.program', fn (Builder $query) => $query->where('code', ProgramAccess::currentCode()))
            ->with([
                'chapter.framework',
                'elements.evidenceDocuments',
                'elements.assessments.findings.correctiveActions',
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Estándar de calidad')
                ->columns(2)
                ->schema([
                    TextEntry::make('chapter.name')->label('Capítulo')->badge(),
                    TextEntry::make('code')->label('Código')->badge(),
                    TextEntry::make('name')->label('Título')->columnSpanFull(),
                    TextEntry::make('description')->label('Descripción')->columnSpanFull(),
                    TextEntry::make('chapter.framework.name')->label('Fuente normativa')->columnSpanFull(),
                    RepeatableEntry::make('elements')
                        ->label('Elementos evaluables')
                        ->schema([
                            TextEntry::make('code')->label('Código')->badge(),
                            TextEntry::make('description')->label('Descripción'),
                            TextEntry::make('compliance_status')->label('Cumplimiento')->badge(),
                            TextEntry::make('evidenceDocuments.title')
                                ->label('Evidencias relacionadas')
                                ->bulleted()
                                ->listWithLineBreaks()
                                ->placeholder('Sin evidencias'),
                            TextEntry::make('assessments.findings.description')
                                ->label('Hallazgos')
                                ->bulleted()
                                ->listWithLineBreaks()
                                ->placeholder('Sin hallazgos'),
                            TextEntry::make('assessments.findings.correctiveActions.action')
                                ->label('Acciones correctivas')
                                ->bulleted()
                                ->listWithLineBreaks()
                                ->placeholder('Sin acciones'),
                            TextEntry::make('assessments.findings.correctiveActions.effectiveness_verification')
                                ->label('Evidencia de cierre')
                                ->bulleted()
                                ->listWithLineBreaks()
                                ->placeholder('Sin evidencia de cierre'),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('chapter.name')->label('Capítulo')->badge()->sortable(),
                TextColumn::make('code')->label('Código')->searchable()->sortable(),
                TextColumn::make('name')->label('Estándar')->searchable()->wrap(),
                TextColumn::make('elements_count')->label('Elementos')->counts('elements'),
                TextColumn::make('chapter.framework.name')->label('Fuente normativa')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('accreditation_chapter_id')
                    ->label('Capítulo')
                    ->relationship('chapter', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQualityStandards::route('/'),
            'view' => ViewQualityStandard::route('/{record}'),
        ];
    }
}
