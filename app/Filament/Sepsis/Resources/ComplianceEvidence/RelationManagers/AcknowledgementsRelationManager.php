<?php

namespace App\Filament\Sepsis\Resources\ComplianceEvidence\RelationManagers;

use App\Models\EvidenceAcknowledgement;
use App\Models\ProgramMember;
use App\Support\ProgramAccess;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trazabilidad de socialización: quién confirmó haber leído o recibido capacitación
 * sobre este documento/evidencia. No sustituye la competencia formal del equipo
 * (Competency/StaffCompetency); es la constancia puntual de socialización del documento.
 */
class AcknowledgementsRelationManager extends RelationManager
{
    protected static string $relationship = 'acknowledgements';

    protected static ?string $title = 'Socialización (lectura y capacitación)';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('program_member_id')
                ->label('Integrante')
                ->relationship(
                    'member',
                    'id',
                    fn (Builder $query) => $query->where('clinical_program_id', ProgramAccess::program()?->id)->where('is_active', true),
                )
                ->getOptionLabelFromRecordUsing(fn (ProgramMember $record): string => $record->user?->name ?? "Integrante #{$record->id}")
                ->searchable()
                ->preload()
                ->required(),
            Select::make('method')->label('Medio')->options(EvidenceAcknowledgement::METHODS)->default('read')->required(),
            DatePicker::make('acknowledged_on')->label('Fecha')->default(now())->required(),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.user.name')->label('Integrante')->placeholder('—'),
                TextColumn::make('method')->label('Medio')
                    ->formatStateUsing(fn (string $state): string => EvidenceAcknowledgement::METHODS[$state] ?? $state)
                    ->badge(),
                TextColumn::make('acknowledged_on')->label('Fecha')->date('d/m/Y'),
                TextColumn::make('notes')->label('Notas')->wrap()->limit(60)->placeholder('—'),
            ])
            ->defaultSort('acknowledged_on', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
