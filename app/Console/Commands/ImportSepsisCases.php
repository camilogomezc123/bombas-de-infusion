<?php

namespace App\Console\Commands;

use App\Enums\CaseStatus;
use App\Models\Patient;
use App\Models\SepsisCase;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class ImportSepsisCases extends Command
{
    protected $signature = 'agora:import-sepsis {file=bdSepsis.xlsx} {--dry-run : Valida el archivo sin guardar cambios}';

    protected $description = 'Importa los casos del Centro de Excelencia de Sepsis desde el archivo Excel.';

    /** @var array<string, int> */
    private array $headers = [];

    public function handle(): int
    {
        $path = base_path($this->argument('file'));

        if (! is_file($path)) {
            $this->error("No se encontró el archivo: {$path}");

            return self::FAILURE;
        }

        $sheet = IOFactory::load($path)->getSheetByName('BD') ?? IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, true, false, false);

        // El encabezado real está en la segunda fila.
        $this->headers = [];
        foreach (($rows[1] ?? []) as $i => $header) {
            $header = trim((string) $header);
            if ($header !== '') {
                $this->headers[$header] = $i;
            }
        }

        $created = 0;
        $errors = 0;
        $recurrences = 0;

        for ($r = 2; $r < count($rows); $r++) {
            $row = $rows[$r];
            $admission = $this->str($row, 'Numero Ingreso');
            $identification = $this->str($row, 'Documento');

            if ($admission === null && $identification === null) {
                continue;
            }

            try {
                $identification ??= 'SIN-ID-'.$admission;
                if ($this->option('dry-run')) {
                    $created++;

                    continue;
                }

                $patient = Patient::query()->updateOrCreate(
                    ['identification' => $identification],
                    ['full_name' => $this->str($row, 'Nombre paciente') ?? 'Paciente sin nombre'],
                );

                $existing = SepsisCase::query()->where('admission_number', $admission)->first();
                $discharged = $this->date($row, 'Fecha de egreso');

                $isRecurrence = SepsisCase::query()
                    ->where('patient_id', $patient->id)
                    ->when($existing, fn ($q) => $q->whereKeyNot($existing->id))
                    ->exists();

                if ($isRecurrence) {
                    $recurrences++;
                }

                SepsisCase::query()->updateOrCreate(
                    ['admission_number' => $admission],
                    [
                        'patient_id' => $patient->id,
                        'case_sequence' => $existing->case_sequence ?? $this->nextSequence(),
                        'case_number' => $existing->case_number ?? ('SP'.($existing->case_sequence ?? $this->peekSequence())),
                        'status' => CaseStatus::Completed,
                        'month' => $this->month($row, $discharged),
                        'registered_on' => $this->date($row, 'Fecha'),
                        'activation_at' => $this->date($row, 'identificación de la sepsis'),
                        'antibiotic_at' => $this->date($row, 'administración de antibiótico'),
                        'fluids_at' => $this->date($row, 'administración de LEV'),
                        'drainage_at' => $this->date($row, 'control de la fuente'),
                        'admission_at' => $this->date($row, 'Fecha y hora de ingreso'),
                        'discharged_at' => $discharged,
                        'uci_transfer_at' => $this->date($row, 'traslado a UCI'),
                        'death_at' => $this->date($row, 'defunción'),
                        'er_stay_days' => $this->decimal($row, 'Estancia en urgencias'),
                        'clinic_stay_days' => $this->decimal($row, 'Estancia en clínica en días'),
                        'uci_stay_days' => $this->decimal($row, 'Estancia en UCI'),
                        'culture_taken' => $this->bool($row, 'Se tomaron cultivos'),
                        'culture_before_ab' => $this->bool($row, 'Cultivo previo a antibiótico'),
                        'ab_compliance' => $this->bool($row, 'Cumplimiento AB empírico'),
                        'ab_adjusted' => $this->bool($row, 'Ajuste de AB de acuerdo a cultivo'),
                        'code_activated' => $this->bool($row, 'Se activó Código Sepsis'),
                        'septic_shock' => $this->bool($row, 'Choque septico'),
                        'uci' => $this->bool($row, 'Se trasladó a UCI'),
                        'deceased' => $this->bool($row, 'Fallecido'),
                        'infection_focus' => $this->focus($this->str($row, 'Foco infeccioso identificado')),
                        'outcome_state' => $this->str($row, 'Estado al egreso'),
                        'is_valid' => $this->bool($row, 'Valido Sepsis') ?? true,
                        'is_recurrence' => $isRecurrence,
                        'completed_at' => $discharged,
                        'clinical_data' => ['excel' => $this->serializable($row)],
                    ],
                );

                $created++;
            } catch (Throwable $e) {
                $errors++;
                $this->error("Fila {$r}: ".$e->getMessage());
            }
        }

        $this->info("Casos procesados: {$created}. Recurrencias: {$recurrences}. Errores: {$errors}.");

        return self::SUCCESS;
    }

    private function index(string $key): ?int
    {
        if (isset($this->headers[$key])) {
            return $this->headers[$key];
        }

        foreach ($this->headers as $header => $i) {
            if (Str::contains($header, $key, ignoreCase: true)) {
                return $i;
            }
        }

        return null;
    }

    private function raw(array $row, string $key): mixed
    {
        $i = $this->index($key);

        return $i === null ? null : ($row[$i] ?? null);
    }

    private function str(array $row, string $key): ?string
    {
        $value = trim((string) $this->raw($row, $key));

        return $value === '' ? null : $value;
    }

    private function bool(array $row, string $key): ?bool
    {
        $value = $this->str($row, $key);

        if ($value === null) {
            return null;
        }

        return match (mb_strtolower($value)) {
            'sí', 'si', 'true', '1', 'x' => true,
            'no', 'false', '0' => false,
            default => null,
        };
    }

    private function decimal(array $row, string $key): ?float
    {
        $value = $this->raw($row, $key);

        return is_numeric($value) ? round((float) $value, 3) : null;
    }

    private function date(array $row, string $key): ?Carbon
    {
        $value = $this->raw($row, $key);

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

    private function month(array $row, ?Carbon $fallback): ?string
    {
        $value = $this->str($row, 'Mes');

        if ($value !== null && preg_match('/^(\d{4})\/?(\d{2})$/', $value, $m)) {
            return "{$m[1]}/{$m[2]}";
        }

        return $fallback?->format('Y/m');
    }

    private function focus(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Categoría corta: lo anterior al primer paréntesis.
        return trim(Str::before($value, '(')) ?: $value;
    }

    private function serializable(array $row): array
    {
        $out = [];
        foreach ($this->headers as $header => $i) {
            $value = $row[$i] ?? null;
            $out[$header] = $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value;
        }

        return $out;
    }

    private function nextSequence(): int
    {
        return ((int) SepsisCase::query()->max('case_sequence')) + 1;
    }

    private function peekSequence(): int
    {
        return $this->nextSequence();
    }
}
