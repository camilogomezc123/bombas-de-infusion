<?php

namespace App\Filament\IcuLiberation\Resources\IcuStays\RelationManagers;

use App\Models\IcuStayAudit;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    protected static ?string $title = 'Auditoría del caso';

    public function form(Schema $s): Schema
    {
        return $s->columns(2)->components([
            Select::make('auditor_id')->label('Auditor')->relationship('auditor', 'name')->searchable()->preload(),
            Select::make('status')->label('Estado')->options(IcuStayAudit::STATUSES)->default('pending')->required(),
            Textarea::make('barriers')->label('Barreras')->columnSpanFull(),
            Textarea::make('probable_cause')->label('Causa probable')->columnSpanFull(),
            Textarea::make('conclusion')->label('Conclusión')->columnSpanFull(),
            Textarea::make('recommendation')->label('Recomendación')->columnSpanFull(),
            Toggle::make('requires_phva')->label('Requiere plan de mejoramiento (PHVA)')->live(),
            Select::make('assessment_finding_id')
                ->label('Hallazgo formal de causa raíz (5 porqués)')
                ->relationship('finding', 'id')
                ->getOptionLabelFromRecordUsing(fn ($record): string => "Hallazgo #{$record->id}".($record->root_cause ? " — {$record->root_cause}" : ''))
                ->searchable()
                ->preload()
                ->columnSpanFull()
                ->visible(fn ($get): bool => (bool) $get('requires_phva'))
                ->helperText('Enlaza esta auditoría con un hallazgo formal en vez de duplicar el análisis causal aquí.'),
            DateTimePicker::make('completed_at')->label('Cierre')->seconds(false),
        ]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([
            TextColumn::make('auditor.name')->label('Auditor')->placeholder('Sin asignar'),
            TextColumn::make('status')->label('Estado')->formatStateUsing(fn (string $s) => IcuStayAudit::STATUSES[$s] ?? $s)->badge(),
            IconColumn::make('requires_phva')->label('Requiere PHVA')->boolean(),
            TextColumn::make('finding.id')->label('Hallazgo')->placeholder('—'),
            TextColumn::make('completed_at')->label('Cierre')->dateTime('d/m/Y H:i')->placeholder('—'),
        ])->defaultSort('id', 'desc')->headerActions([CreateAction::make()])->recordActions([EditAction::make()]);
    }
}
