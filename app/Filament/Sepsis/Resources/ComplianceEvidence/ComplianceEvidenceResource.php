<?php

namespace App\Filament\Sepsis\Resources\ComplianceEvidence;

use App\Enums\EvidenceStatus;
use App\Filament\Sepsis\Resources\ComplianceEvidence\Pages\CreateComplianceEvidence;
use App\Filament\Sepsis\Resources\ComplianceEvidence\Pages\EditComplianceEvidence;
use App\Filament\Sepsis\Resources\ComplianceEvidence\Pages\ListComplianceEvidence;
use App\Filament\Sepsis\Resources\ComplianceEvidence\Pages\ViewComplianceEvidence;
use App\Filament\Sepsis\Resources\ComplianceEvidence\RelationManagers\AcknowledgementsRelationManager;
use App\Filament\Sepsis\Resources\ProgramDocuments\ProgramDocumentResource;
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

class ComplianceEvidenceResource extends Resource
{
    protected static ?string $model = EvidenceDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string|\UnitEnum|null $navigationGroup = 'Calidad y mejora';

    protected static ?string $navigationLabel = 'Evidencias';

    protected static ?string $modelLabel = 'evidencia de cumplimiento';

    protected static ?string $pluralModelLabel = 'Evidencias de cumplimiento';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('program', fn (Builder $query) => $query->where('code', ProgramAccess::currentCode()))
            ->whereNotIn('evidence_type', ProgramDocumentResource::DOCUMENT_TYPES)
            ->with(['elements.standard.chapter', 'program']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Evidencia de cumplimiento')
                ->columns(2)
                ->schema([
                    Select::make('elements')
                        ->label('Elementos evaluables')
                        ->multiple()
                        ->relationship(
                            'elements',
                            'code',
                            fn (Builder $query) => $query->whereHas(
                                'standard.chapter.framework.program',
                                fn (Builder $query) => $query->where('code', ProgramAccess::currentCode()),
                            ),
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn ($record): string => "{$record->code} · {$record->description}",
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('title')->label('Título')->required()->maxLength(255)->columnSpan(1),
                    TextInput::make('institutional_code')->label('Código institucional')->maxLength(60),
                    Textarea::make('description')->label('Descripción')->rows(4)->columnSpanFull(),
                    Select::make('evidence_type')
                        ->label('Tipo de evidencia')
                        ->options([
                            'minutes' => 'Acta',
                            'indicator' => 'Indicador',
                            'report' => 'Informe',
                            'audit' => 'Auditoría',
                            'training_record' => 'Registro de capacitación',
                            'clinical_evidence' => 'Evidencia clínica',
                            'other' => 'Otro',
                        ])
                        ->helperText('Guías, protocolos, políticas y documentos administrativos se gestionan en Programa → Guías y documentos.')
                        ->required(),
                    Select::make('status')
                        ->label('Estado')
                        ->options(collect(EvidenceStatus::cases())
                            ->mapWithKeys(fn (EvidenceStatus $status): array => [$status->value => $status->label()]))
                        ->default(EvidenceStatus::Draft->value)
                        ->required(),
                    TextInput::make('document_reference')
                        ->label('Referencia o ubicación controlada')
                        ->required()
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    TextInput::make('version')->label('Versión')->maxLength(30),
                    DatePicker::make('approved_on')->label('Fecha de aprobación'),
                    DatePicker::make('expires_on')->label('Fecha de vencimiento')->afterOrEqual('approved_on'),
                    DatePicker::make('next_review_on')->label('Próxima revisión programada')
                        ->helperText('Puede ser anterior al vencimiento: confirma que el contenido sigue vigente.'),
                    Select::make('socialization_status')->label('Estado de socialización')
                        ->options(EvidenceDocument::SOCIALIZATION_STATUSES)
                        ->default('pending')
                        ->required(),
                    Select::make('owner_id')
                        ->label('Responsable')
                        ->relationship('owner', 'name')
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
                TextColumn::make('elements.code')->label('Elementos')->badge(),
                TextColumn::make('title')->label('Evidencia')->searchable()->wrap(),
                TextColumn::make('evidence_type')->label('Tipo')->badge(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (EvidenceStatus $state): string => $state->label())
                    ->badge(),
                TextColumn::make('expires_on')->label('Vigente hasta')->date('d/m/Y')->placeholder('Sin vencimiento'),
                TextColumn::make('next_review_on')->label('Próxima revisión')->date('d/m/Y')->placeholder('Sin fecha')->toggleable(),
                TextColumn::make('socialization_status')->label('Socialización')
                    ->formatStateUsing(fn (string $state): string => EvidenceDocument::SOCIALIZATION_STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success', 'in_progress' => 'warning', default => 'gray',
                    }),
                TextColumn::make('owner.name')->label('Responsable')->placeholder('Sin asignar'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(EvidenceStatus::cases())
                        ->mapWithKeys(fn (EvidenceStatus $status): array => [$status->value => $status->label()])),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComplianceEvidence::route('/'),
            'create' => CreateComplianceEvidence::route('/create'),
            'view' => ViewComplianceEvidence::route('/{record}'),
            'edit' => EditComplianceEvidence::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [AcknowledgementsRelationManager::class];
    }
}
