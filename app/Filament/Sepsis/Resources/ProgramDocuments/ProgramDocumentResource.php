<?php

namespace App\Filament\Sepsis\Resources\ProgramDocuments;

use App\Enums\EvidenceStatus;
use App\Filament\Sepsis\Resources\ComplianceEvidence\RelationManagers\AcknowledgementsRelationManager;
use App\Filament\Sepsis\Resources\ProgramDocuments\Pages\CreateProgramDocument;
use App\Filament\Sepsis\Resources\ProgramDocuments\Pages\EditProgramDocument;
use App\Filament\Sepsis\Resources\ProgramDocuments\Pages\ListProgramDocuments;
use App\Filament\Sepsis\Resources\ProgramDocuments\Pages\ViewProgramDocument;
use App\Models\EvidenceDocument;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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

/**
 * Vista de "documentos vivos" del programa (guías clínicas, protocolos, políticas)
 * sobre el mismo EvidenceDocument que usa ComplianceEvidenceResource — distinta
 * únicamente por el subconjunto de evidence_type que muestra, para no duplicar la tabla.
 */
class ProgramDocumentResource extends Resource
{
    protected static ?string $model = EvidenceDocument::class;

    public const DOCUMENT_TYPES = ['protocol', 'clinical_guideline', 'policy', 'administrative_document'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Programa';

    protected static ?string $navigationLabel = 'Guías y documentos';

    protected static ?string $modelLabel = 'documento del programa';

    protected static ?string $pluralModelLabel = 'Guías y documentos';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('program', fn (Builder $query) => $query->where('code', ProgramAccess::currentCode()))
            ->whereIn('evidence_type', self::DOCUMENT_TYPES)
            ->with(['owner', 'replaces']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Documento del programa')
                ->columns(2)
                ->schema([
                    TextInput::make('title')->label('Título')->required()->maxLength(255),
                    TextInput::make('institutional_code')->label('Código institucional')->maxLength(60),
                    Textarea::make('description')->label('Descripción')->rows(3)->columnSpanFull(),
                    Select::make('evidence_type')
                        ->label('Tipo de documento')
                        ->options([
                            'clinical_guideline' => 'Guía clínica',
                            'protocol' => 'Protocolo',
                            'policy' => 'Política',
                            'administrative_document' => 'Documento administrativo',
                        ])
                        ->default('clinical_guideline')
                        ->required(),
                    Select::make('status')
                        ->label('Estado documental')
                        ->options(collect(EvidenceStatus::cases())
                            ->mapWithKeys(fn (EvidenceStatus $status): array => [$status->value => $status->label()]))
                        ->default(EvidenceStatus::Draft->value)
                        ->required()
                        ->helperText('Vigente = Verificada. Reemplazado = Archivada.'),
                    TextInput::make('version')->label('Versión')->maxLength(30),
                    TextInput::make('document_reference')
                        ->label('Referencia o ubicación controlada')
                        ->required()
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    DatePicker::make('approved_on')->label('Fecha de aprobación'),
                    DatePicker::make('expires_on')->label('Vigencia')->afterOrEqual('approved_on'),
                    DatePicker::make('next_review_on')->label('Próxima revisión programada')
                        ->helperText('Puede ser anterior a la vigencia: confirma que el contenido sigue vigente.'),
                    Select::make('socialization_status')->label('Estado de socialización')
                        ->options(EvidenceDocument::SOCIALIZATION_STATUSES)
                        ->default('pending')
                        ->required(),
                    Select::make('owner_id')->label('Responsable')->relationship('owner', 'name')->searchable()->preload(),
                    Select::make('replaces_evidence_document_id')
                        ->label('Documento que reemplaza')
                        ->relationship(
                            'replaces',
                            'title',
                            fn (Builder $query) => $query->whereIn('evidence_type', self::DOCUMENT_TYPES),
                        )
                        ->searchable()
                        ->preload(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('institutional_code')->label('Código')->placeholder('—')->toggleable(),
                TextColumn::make('title')->label('Documento')->searchable()->wrap(),
                TextColumn::make('evidence_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'clinical_guideline' => 'Guía clínica',
                        'protocol' => 'Protocolo',
                        'policy' => 'Política',
                        'administrative_document' => 'Documento administrativo',
                        default => $state,
                    })
                    ->badge(),
                TextColumn::make('version')->label('Versión'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (EvidenceStatus $state): string => $state->label())
                    ->badge(),
                TextColumn::make('expires_on')->label('Vigencia')->date('d/m/Y')->placeholder('Sin fecha'),
                TextColumn::make('next_review_on')->label('Próxima revisión')->date('d/m/Y')->placeholder('Sin fecha')->toggleable(),
                TextColumn::make('socialization_status')->label('Socialización')
                    ->formatStateUsing(fn (string $state): string => EvidenceDocument::SOCIALIZATION_STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success', 'in_progress' => 'warning', default => 'gray',
                    }),
                TextColumn::make('owner.name')->label('Responsable')->placeholder('Sin asignar'),
                TextColumn::make('replaces.title')->label('Reemplaza a')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('evidence_type')->label('Tipo')->options([
                    'clinical_guideline' => 'Guía clínica',
                    'protocol' => 'Protocolo',
                    'policy' => 'Política',
                    'administrative_document' => 'Documento administrativo',
                ]),
                SelectFilter::make('status')->label('Estado')->options(collect(EvidenceStatus::cases())
                    ->mapWithKeys(fn (EvidenceStatus $status): array => [$status->value => $status->label()])),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProgramDocuments::route('/'),
            'create' => CreateProgramDocument::route('/create'),
            'view' => ViewProgramDocument::route('/{record}'),
            'edit' => EditProgramDocument::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [AcknowledgementsRelationManager::class];
    }
}
