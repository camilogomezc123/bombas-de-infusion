<?php

namespace App\Console\Commands;

use App\Enums\CaseStatus;
use App\Models\AcvCase;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class MergeAcvCases extends Command
{
    protected $signature = 'agora:merge-acv {file=bdACV23-24.xlsx}';

    protected $description = 'Fusiona una base ACV maestra: rellena datos faltantes de casos existentes (sin pisar auditor/estado) y crea los casos nuevos por número de caso (S).';

    /** @var array<string,int> */
    private array $headers = [];

    /** Campos promovidos: columna_db => [substring del encabezado, tipo]. */
    private const FIELDS = [
        'arrival_at' => ['Hora puerta', 'date'],
        'last_known_well_at' => ['Última vez en buen estado', 'date'],
        'imaging_at' => ['Hora imagen', 'date'],
        'thrombolysis_at' => ['Fecha y hora de Trombolisis', 'date'],
        'groin_puncture_at' => ['puncion para Trombect', 'date'],
        'revascularization_at' => ['Revascularización', 'date'],
        'speech_therapy_at' => ['valoración por fonoaudiología', 'date'],
        'physiotherapy_at' => ['valoración por fisioterapia', 'date'],
        'discharged_at' => ['Fecha y hora de egreso', 'date'],
        'thrombolysed' => ['Trombolizado', 'bool'],
        'thrombectomy' => ['Trombectomia', 'bool'],
        'deceased' => ['Fallecido', 'bool'],
        'hemorrhagic_transformation' => ['ransformaci', 'bool'],
        'eapb' => ['EAPB', 'string'],
        'health_regime' => ['Regimen de salud', 'string'],
        'stroke_type' => ['Tipo de ACV', 'string'],
    ];

    public function handle(): int
    {
        $path = base_path($this->argument('file'));
        if (! is_file($path)) {
            $this->error("No se encontró el archivo: {$path}");

            return self::FAILURE;
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $rows = $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);

        $this->headers = [];
        foreach ($rows[0] as $i => $h) {
            $h = trim((string) $h);
            if ($h !== '') {
                $this->headers[$h] = $i;
            }
        }

        $created = 0;
        $filled = 0;
        $errors = 0;

        for ($k = 1; $k < count($rows); $k++) {
            $row = $rows[$k];
            $caso = trim((string) ($row[0] ?? '')); // columna 0 = Caso (S)
            $identification = trim((string) ($row[$this->headers['Identificación'] ?? -1] ?? ''));

            if ($caso === '') {
                continue;
            }

            try {
                $identification = $identification !== '' ? $identification : "SIN-ID-{$caso}";
                $patient = Patient::query()->updateOrCreate(
                    ['identification' => $identification],
                    array_filter([
                        'full_name' => trim((string) ($row[$this->headers['NOMBRES Y APELLIDOS'] ?? -1] ?? '')) ?: null,
                        'sex' => $this->str($row, 'Sexo'),
                        'age' => $this->int($row, 'Edad'),
                    ], fn ($v) => $v !== null),
                );

                $excel = $this->serializable($row);
                $existing = AcvCase::query()->where('case_number', $caso)->first();

                if ($existing) {
                    $this->fillExisting($existing, $row, $excel) && $filled++;

                    continue;
                }

                $this->createNew($caso, $patient->id, $row, $excel);
                $created++;
            } catch (Throwable $e) {
                $errors++;
                $this->error("Caso {$caso}: ".$e->getMessage());
            }
        }

        $recurrences = $this->recomputeRecurrences();

        $this->info("Casos nuevos creados: {$created}.");
        $this->info("Casos existentes completados: {$filled}.");
        $this->info("Recurrencias recalculadas: {$recurrences}.");
        $this->info('Total de casos ACV: '.AcvCase::count().". Errores: {$errors}.");

        return self::SUCCESS;
    }

    private function fillExisting(AcvCase $case, array $row, array $excel): bool
    {
        $dirty = false;

        // Rellenar SOLO columnas vacías (no pisa el trabajo del auditor/líder).
        foreach (self::FIELDS as $col => [$sub, $type]) {
            if (filled($case->{$col})) {
                continue;
            }
            $value = $this->parse($row, $sub, $type);
            if ($value !== null) {
                $case->{$col} = $value;
                $dirty = true;
            }
        }

        if (blank($case->month) && $case->discharged_at) {
            $case->month = $case->discharged_at->format('Y/m');
            $dirty = true;
        }

        // Completar clinical_data.excel: añade columnas nuevas, conserva las existentes con valor.
        $clinical = $case->clinical_data ?? [];
        $existingExcel = $clinical['excel'] ?? [];
        $mergedExcel = $existingExcel;
        foreach ($excel as $key => $val) {
            if (! array_key_exists($key, $mergedExcel) || $mergedExcel[$key] === null || $mergedExcel[$key] === '') {
                $mergedExcel[$key] = $val;
            }
        }
        if ($mergedExcel != $existingExcel) {
            $clinical['excel'] = $mergedExcel;
            $case->clinical_data = $clinical;
            $dirty = true;
        }

        if ($dirty) {
            $case->saveQuietly();
        }

        return $dirty;
    }

    private function createNew(string $caso, int $patientId, array $row, array $excel): void
    {
        $sequence = preg_match('/(\d+)/', $caso, $m) ? (int) $m[1] : ((int) AcvCase::max('case_sequence') + 1);
        $discharged = $this->parse($row, 'Fecha y hora de egreso', 'date');

        $attributes = [
            'patient_id' => $patientId,
            'case_number' => $caso,
            'case_sequence' => $sequence,
            'admission_number' => $this->str($row, 'INGRESO'),
            'resq_code' => $this->str($row, 'ResQ'),
            'status' => CaseStatus::Completed,
            'month' => $this->month($row, $discharged),
            'completed_at' => $discharged,
            'is_cancelled' => false,
            'clinical_data' => ['excel' => $excel],
        ];

        foreach (self::FIELDS as $col => [$sub, $type]) {
            $attributes[$col] = $this->parse($row, $sub, $type);
        }

        $case = new AcvCase($attributes);
        $case->saveQuietly();
    }

    /** Marca como recurrencia todos los casos de un paciente salvo el más antiguo. */
    private function recomputeRecurrences(): int
    {
        $count = 0;
        AcvCase::query()->where('is_cancelled', false)
            ->orderBy('patient_id')->orderByRaw('arrival_at is null, arrival_at')
            ->get(['id', 'patient_id', 'arrival_at', 'is_recurrence'])
            ->groupBy('patient_id')
            ->each(function ($cases) use (&$count): void {
                foreach ($cases->values() as $i => $case) {
                    $shouldBe = $i > 0;
                    if ($case->is_recurrence !== $shouldBe) {
                        AcvCase::whereKey($case->id)->update(['is_recurrence' => $shouldBe]);
                        $count++;
                    }
                }
            });

        return $count;
    }

    private function index(string $sub): ?int
    {
        if (isset($this->headers[$sub])) {
            return $this->headers[$sub];
        }
        foreach ($this->headers as $h => $i) {
            if (Str::contains($h, $sub, ignoreCase: true)) {
                return $i;
            }
        }

        return null;
    }

    private function raw(array $row, string $sub): mixed
    {
        $i = $this->index($sub);

        return $i === null ? null : ($row[$i] ?? null);
    }

    private function parse(array $row, string $sub, string $type): mixed
    {
        return match ($type) {
            'date' => $this->date($row, $sub),
            'bool' => $this->bool($row, $sub),
            'int' => $this->int($row, $sub),
            default => $this->str($row, $sub),
        };
    }

    private function str(array $row, string $sub): ?string
    {
        $v = trim((string) $this->raw($row, $sub));

        return $v === '' ? null : $v;
    }

    private function int(array $row, string $sub): ?int
    {
        $v = $this->raw($row, $sub);

        return is_numeric($v) ? (int) $v : null;
    }

    private function bool(array $row, string $sub): ?bool
    {
        $v = $this->str($row, $sub);
        if ($v === null) {
            return null;
        }

        return match (mb_strtolower($v)) {
            'sí', 'si', 'true', '1', 'x' => true,
            'no', 'false', '0' => false,
            default => null,
        };
    }

    private function date(array $row, string $sub): ?Carbon
    {
        $v = $this->raw($row, $sub);
        if ($v === null || $v === '') {
            return null;
        }
        try {
            return is_numeric($v)
                ? Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v))
                : Carbon::parse((string) $v);
        } catch (Throwable) {
            return null;
        }
    }

    private function month(array $row, ?Carbon $discharged): ?string
    {
        $mes = $this->str($row, 'MES');
        if ($mes !== null && preg_match('/^(\d{4})\/?(\d{2})$/', $mes, $m)) {
            return "{$m[1]}/{$m[2]}";
        }

        return $discharged?->format('Y/m');
    }

    private function serializable(array $row): array
    {
        $out = [];
        foreach ($this->headers as $h => $i) {
            $v = $row[$i] ?? null;
            $out[$h] = $v instanceof \DateTimeInterface ? $v->format('Y-m-d H:i:s') : $v;
        }

        return $out;
    }
}
