<?php

namespace App\Filament\Acv\Resources\AcvCases\Schemas;

use App\Filament\Acv\Resources\AcvCases\AcvCaseResource;
use App\Models\AcvCase;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class AcvCaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Caso ACV')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('case_number')->label('Caso')->badge(),
                        TextEntry::make('status')->label('Estado')->formatStateUsing(fn ($state): string => $state?->label() ?? 'Sin estado')->badge(),
                        TextEntry::make('assignedAuditor.name')->label('Auditor')->placeholder('Sin asignar'),
                        TextEntry::make('admission_number')->label('Número de ingreso')->placeholder('Sin dato'),
                        TextEntry::make('resq_code')->label('Código ResQ')->placeholder('Sin dato'),
                        IconEntry::make('is_recurrence')->label('Recurrencia')->boolean(),
                    ]),
                Section::make('Recurrencia del paciente')
                    ->description('Este paciente tiene otros ingresos registrados. Cada ingreso es un caso independiente.')
                    ->visible(fn (AcvCase $record): bool => self::previousCases($record)->isNotEmpty())
                    ->schema([
                        TextEntry::make('previous_cases')
                            ->hiddenLabel()
                            ->state(fn (AcvCase $record): string => self::previousCases($record)
                                ->map(fn (AcvCase $case): string => sprintf(
                                    '<a href="%s" class="text-primary-600 underline">%s — %s</a>',
                                    AcvCaseResource::getUrl('view', ['record' => $case]),
                                    e($case->case_number),
                                    optional($case->arrival_at ?? $case->discharged_at)?->format('d/m/Y') ?? 'sin fecha',
                                ))
                                ->implode('<br>'))
                            ->html()
                            ->columnSpanFull(),
                    ]),
                Section::make('Paciente y atención')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('patient.full_name')->label('Paciente'),
                        TextEntry::make('patient.identification')->label('Identificación'),
                        TextEntry::make('patient.sex')->label('Sexo')->placeholder('Sin dato'),
                        TextEntry::make('stroke_type')->label('Tipo de ACV')->badge()->placeholder('Sin clasificar'),
                        TextEntry::make('arrival_at')->label('Ingreso')->dateTime('d/m/Y H:i')->placeholder('Sin dato'),
                        TextEntry::make('discharged_at')->label('Egreso')->dateTime('d/m/Y H:i')->placeholder('Sin dato'),
                        TextEntry::make('eapb')->label('EAPB')->placeholder('Sin dato'),
                        TextEntry::make('health_regime')->label('Régimen')->placeholder('Sin dato'),
                        TextEntry::make('discharge_destination')->label('Destino egreso')->placeholder('Sin especificar'),
                        IconEntry::make('deceased')->label('Fallecido')->boolean(),
                    ]),
                Section::make('Tratamiento')
                    ->columns(3)
                    ->schema([
                        IconEntry::make('thrombolysed')->label('Trombolizado')->boolean(),
                        TextEntry::make('thrombolysis_at')->label('Trombólisis')->dateTime('d/m/Y H:i')->placeholder('No aplica'),
                        IconEntry::make('thrombectomy')->label('Trombectomía')->boolean(),
                        TextEntry::make('groin_puncture_at')->label('Punción inguinal')->dateTime('d/m/Y H:i')->placeholder('No aplica'),
                        TextEntry::make('revascularization_at')->label('Revascularización')->dateTime('d/m/Y H:i')->placeholder('No aplica'),
                        IconEntry::make('hemorrhagic_transformation')->label('Transformación hemorrágica')->boolean(),
                    ]),
            ]);
    }

    /**
     * Otros ingresos del mismo paciente (excluye el caso actual y los anulados).
     *
     * @return Collection<int, AcvCase>
     */
    private static function previousCases(AcvCase $record): Collection
    {
        return AcvCase::query()
            ->where('patient_id', $record->patient_id)
            ->whereKeyNot($record->getKey())
            ->where('is_cancelled', false)
            ->orderByDesc('arrival_at')
            ->get();
    }
}
