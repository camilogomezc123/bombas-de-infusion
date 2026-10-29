<?php

namespace App\Console\Commands;

use App\Enums\CaseStatus;
use App\Models\AcvCase;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

#[Signature('agora:import-acv
    {file=bdACV.xlsx : Ruta del archivo Excel}
    {--fresh : Elimina pacientes y casos ACV antes de importar}')]
#[Description('Importa la base histórica de casos ACV de ÁGORA')]
class ImportAcvCases extends Command
{
    public function handle(): int
    {
        $path = $this->absolutePath((string) $this->argument('file'));

        if (! is_file($path)) {
            $this->error("No se encontró el archivo: {$path}");

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            DB::transaction(function (): void {
                AcvCase::query()->delete();
                Patient::query()->delete();
            });
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $worksheet = $reader->load($path)->getActiveSheet();
        $highestColumn = Coordinate::columnIndexFromString($worksheet->getHighestColumn());
        $highestRow = $worksheet->getHighestDataRow();

        $headers = [];
        for ($column = 1; $column <= $highestColumn; $column++) {
            $headers[$column] = trim((string) $worksheet->getCell([$column, 1])->getValue());
        }

        $created = 0;
        $updated = 0;
        $failed = 0;
        $bar = $this->output->createProgressBar(max(0, $highestRow - 1));
        $bar->start();

        AcvCase::withoutEvents(function () use (
            $worksheet,
            $headers,
            $highestColumn,
            $highestRow,
            &$created,
            &$updated,
            &$failed,
            $bar,
        ): void {
            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    $values = [];
                    for ($column = 1; $column <= $highestColumn; $column++) {
                        $header = $headers[$column] ?: "Columna {$column}";
                        $values[$header] = $worksheet->getCell([$column, $row])->getValue();
                    }

                    $caseNumber = trim((string) ($values['Caso'] ?? ''));
                    $identification = trim((string) ($values['Identificación'] ?? ''));

                    if ($caseNumber === '' && $identification === '') {
                        $bar->advance();
                        continue;
                    }

                    $identification = $identification !== '' ? $identification : "SIN-ID-{$caseNumber}";
                    $patient = Patient::query()->updateOrCreate(
                        ['identification' => $identification],
                        [
                            'full_name' => trim((string) ($values['NOMBRES Y APELLIDOS'] ?? 'Paciente sin nombre')),
                            'sex' => $this->nullableString($values['Sexo'] ?? null),
                            'age' => $this->nullableInteger($values['Edad'] ?? null),
                        ],
                    );

                    $existing = AcvCase::query()->where('case_number', $caseNumber)->first();
                    $auditor = $this->findAuditor($values);
                    $arrival = $this->excelDate($values['Fecha y Hora de ingreso al hospital; Hora puerta // En hospitalizados hora de identificación del evento'] ?? null);
                    $discharged = $this->excelDate($values['Fecha y hora de egreso'] ?? null);

                    AcvCase::query()->updateOrCreate(
                        ['case_number' => $caseNumber],
                        [
                            'patient_id' => $patient->id,
                            'assigned_auditor_id' => $auditor?->id,
                            'case_sequence' => $this->caseSequence($caseNumber),
                            'admission_number' => $this->nullableString($values['No Ingreso'] ?? null),
                            'resq_code' => $this->nullableString($values['Codigo ResQ'] ?? null),
                            'status' => $discharged ? CaseStatus::Imported : CaseStatus::Hospitalized,
                            // El caso cuenta en el mes de su egreso; los aún hospitalizados no tienen mes.
                            'month' => $discharged?->format('Y/m'),
                            'eapb' => $this->nullableString($values['EAPB'] ?? null),
                            'health_regime' => $this->nullableString($values['Regimen de salud'] ?? null),
                            'stroke_type' => $this->nullableString($values['Tipo de ACV'] ?? null),
                            'arrival_at' => $arrival,
                            'last_known_well_at' => $this->excelDate($values['Última vez en buen estado'] ?? null),
                            'imaging_at' => $this->excelDate($values['Hora imagen'] ?? null),
                            'thrombolysis_at' => $this->excelDate($values['Fecha y hora de Trombolisis'] ?? null),
                            'groin_puncture_at' => $this->excelDate($values['Fecha y hora de puncion para Trombectomía'] ?? null),
                            'revascularization_at' => $this->excelDate($values['Fecha y hora de Revascularización'] ?? null),
                            'speech_therapy_at' => $this->excelDate($values['Hora y fecha de valoración por fonoaudiología'] ?? null),
                            'physiotherapy_at' => $this->excelDate($values['Hora y fecha de valoración por fisioterapia'] ?? null),
                            'discharged_at' => $discharged,
                            'thrombolysed' => $this->boolean($values['Trombolizado'] ?? null),
                            'thrombectomy' => $this->boolean($values['Trombectomia'] ?? null),
                            'deceased' => $this->boolean($values['Fallecido'] ?? null),
                            'hemorrhagic_transformation' => $this->boolean($values['Transformación hermorrágica'] ?? null),
                            'is_recurrence' => AcvCase::query()
                                ->where('patient_id', $patient->id)
                                ->when($existing, fn ($query) => $query->whereKeyNot($existing->id))
                                ->where('is_cancelled', false)
                                ->exists(),
                            'clinical_data' => [
                                'imaging_type' => $values['Imagen realizada'] ?? null,
                                'nihss_arrival' => $values['NIHSS al ingreso'] ?? null,
                                'rankin_arrival' => $values['Rankin al ingreso'] ?? null,
                                'code_activated' => $this->boolean($values['Se activó código?'] ?? null),
                                'wake_up_stroke' => $this->boolean($values['ACV de despertar'] ?? null),
                                'inpatient_stroke' => $this->boolean($values['ACV estando hospitalizado'] ?? null),
                                'treatment_dose_mg' => $values['Dosis tratamiento en mg'] ?? null,
                                'tici' => $values['TICI'] ?? null,
                                'stroke_cause' => $values['Causa del ACV'] ?? null,
                                'three_month_contact_type' => $values['Tipo de Contacto a los 3 meses'] ?? null,
                                'rankin_three_months' => $values['Rankin a los 3 meses'] ?? null,
                                'observations' => $values['Observaciones'] ?? null,
                                'excel' => $this->serializableValues($values),
                            ],
                        ],
                    );

                    $existing ? $updated++ : $created++;
                } catch (Throwable $exception) {
                    $failed++;
                    $this->newLine();
                    $this->warn("Fila {$row}: {$exception->getMessage()}");
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Importación terminada: {$created} creados, {$updated} actualizados, {$failed} con error.");
        $this->line('Pacientes recurrentes detectados: '.AcvCase::query()->where('is_recurrence', true)->count());

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function absolutePath(string $path): string
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $path)
            ? $path
            : base_path($path);
    }

    private function caseSequence(string $caseNumber): int
    {
        if (preg_match('/(\d+)/', $caseNumber, $matches)) {
            return (int) $matches[1];
        }

        return ((int) AcvCase::query()->max('case_sequence')) + 1;
    }

    private function findAuditor(array $values): ?User
    {
        $email = $this->nullableString($values['correo'] ?? null);
        $name = $this->nullableString($values['Auditor asignado'] ?? null);

        return User::query()
            ->when($email, fn ($query) => $query->where('email', $email))
            ->when(! $email && $name, fn ($query) => $query->where('name', $name))
            ->first();
    }

    private function excelDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            }

            return Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    private function boolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match (mb_strtolower(trim((string) $value))) {
            'sí', 'si', 'true', '1', 'x' => true,
            'no', 'false', '0' => false,
            default => null,
        };
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function serializableValues(array $values): array
    {
        return array_map(function (mixed $value): mixed {
            if ($value instanceof \DateTimeInterface) {
                return $value->format(DATE_ATOM);
            }

            return is_scalar($value) || $value === null ? $value : (string) $value;
        }, $values);
    }
}
