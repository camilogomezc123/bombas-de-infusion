<?php

namespace App\Filament\Programs\Shared\Resources\ClinicalRules;

use App\Enums\ProgramPermission;
use App\Filament\Programs\Shared\Resources\ClinicalRules\Pages\CreateClinicalRule;
use App\Filament\Programs\Shared\Resources\ClinicalRules\Pages\EditClinicalRule;
use App\Filament\Programs\Shared\Resources\ClinicalRules\Pages\ListClinicalRules;
use App\Models\ClinicalRule;
use App\Support\ProgramAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClinicalRuleResource extends Resource
{
    protected static ?string $model = ClinicalRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Reglas clínicas';

    protected static ?string $modelLabel = 'regla clínica';

    protected static ?string $pluralModelLabel = 'Reglas clínicas versionadas';

    protected static ?int $navigationSort = 75;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('clinical_program_id', ProgramAccess::program()?->id)
            ->orderBy('code')->orderByDesc('version');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificación y versión')->columns(3)->schema([
                TextInput::make('code')->label('Código')->required(),
                TextInput::make('name')->label('Nombre')->required()->columnSpan(2),
                TextInput::make('version')->label('Versión')->required()->default('1.0'),
                Select::make('status')->label('Estado')->options([
                    'draft' => 'Borrador', 'under_review' => 'En revisión',
                    'approved' => 'Aprobada', 'retired' => 'Retirada',
                ])->required()->default('draft'),
                DatePicker::make('effective_from')->label('Vigente desde'),
                DatePicker::make('effective_until')->label('Vigente hasta'),
                Textarea::make('description')->label('Descripción')->columnSpanFull(),
            ]),
            Section::make('Configuración auditable')->schema([
                KeyValue::make('configuration')->label('Parámetros')
                    ->keyLabel('Parámetro')->valueLabel('Valor')
                    ->helperText('Los parámetros se aplican solo después de aprobación institucional.'),
            ]),
            Section::make('Fuente')->columns(2)->schema([
                TextInput::make('source_document')->label('Documento fuente'),
                TextInput::make('source_section')->label('Sección / página'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label('Código')->searchable(),
            TextColumn::make('name')->label('Regla')->wrap()->searchable(),
            TextColumn::make('version')->label('Versión')->badge(),
            TextColumn::make('status')->label('Estado')->badge(),
            TextColumn::make('effective_from')->label('Vigente desde')->date('d/m/Y')->placeholder('Sin definir'),
            TextColumn::make('approver.name')->label('Aprobó')->placeholder('Pendiente'),
        ])->recordActions([
            Action::make('approve')->label('Aprobar')->icon('heroicon-m-check-badge')->color('success')
                ->requiresConfirmation()
                ->visible(fn (ClinicalRule $record): bool => $record->status !== 'approved'
                    && (auth()->user() && ProgramAccess::can(auth()->user(), ProgramPermission::ManageProgram)))
                ->action(fn (ClinicalRule $record) => $record->update([
                    'status' => 'approved', 'approved_by' => auth()->id(),
                    'approved_at' => now(), 'effective_from' => $record->effective_from ?? today(),
                ])),
            EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClinicalRules::route('/'),
            'create' => CreateClinicalRule::route('/create'),
            'edit' => EditClinicalRule::route('/{record}/edit'),
        ];
    }
}
