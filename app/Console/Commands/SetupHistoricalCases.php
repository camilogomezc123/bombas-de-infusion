<?php

namespace App\Console\Commands;

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Models\AcvCase;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetupHistoricalCases extends Command
{
    protected $signature = 'agora:setup-historical';

    protected $description = 'Crea los usuarios auditores del histórico, asigna cada caso a su auditor y marca los casos importados como finalizados.';

    public function handle(): int
    {
        $cases = AcvCase::query()->where('is_cancelled', false)->get();

        // 1) Resolver/crear un usuario por cada auditor distinto del histórico.
        $auditorsByName = [];

        foreach ($cases as $case) {
            $name = trim((string) data_get($case->clinical_data, 'excel.Auditor asignado'));
            $email = trim((string) data_get($case->clinical_data, 'excel.correo'));

            if ($name === '' || isset($auditorsByName[$name])) {
                continue;
            }

            $auditorsByName[$name] = $this->resolveAuditor($name, $email);
        }

        $created = collect($auditorsByName)->where('was_created', true)->count();
        $this->info("Auditores: {$created} creados, ".(count($auditorsByName) - $created).' ya existían.');

        // 2) Asignar cada caso a su auditor y marcar importados como finalizados.
        $assigned = 0;
        $finalized = 0;

        foreach ($cases as $case) {
            $name = trim((string) data_get($case->clinical_data, 'excel.Auditor asignado'));
            $auditorId = $name !== '' ? ($auditorsByName[$name]['id'] ?? null) : null;
            $dirty = false;

            if ($auditorId && $case->assigned_auditor_id !== $auditorId) {
                $case->assigned_auditor_id = $auditorId;
                $case->assigned_at ??= $case->arrival_at ?? $case->created_at;
                $dirty = true;
                $assigned++;
            }

            if ($case->status === CaseStatus::Imported) {
                $case->status = CaseStatus::Completed;
                $case->completed_at ??= $case->discharged_at ?? now();
                $case->auditor_finalized_at ??= $case->discharged_at ?? $case->completed_at;
                $dirty = true;
                $finalized++;
            }

            if ($dirty) {
                $case->saveQuietly();
            }
        }

        $this->info("Casos asignados a su auditor: {$assigned}.");
        $this->info("Casos importados marcados como Finalizado: {$finalized}.");

        return self::SUCCESS;
    }

    /**
     * @return array{id: int, was_created: bool}
     */
    private function resolveAuditor(string $name, string $email): array
    {
        // Coincidencia exacta por nombre (idempotente y reúsa cuentas existentes).
        $user = User::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        // El líder/administrador que también auditó (p. ej. "Alexander Torres" → admin).
        if (! $user) {
            $user = User::query()
                ->whereIn('role', [UserRole::Administrator->value, UserRole::Leader->value])
                ->where('name', 'like', $name.'%')
                ->first();
        }

        if ($user) {
            return ['id' => $user->id, 'was_created' => false];
        }

        $user = User::query()->create([
            'name' => $name,
            'username' => $this->uniqueUsername($name),
            'email' => $this->availableEmail($email),
            'role' => UserRole::Auditor,
            'is_active' => true,
            'must_change_password' => true,
            'password' => Str::random(20),
        ]);

        return ['id' => $user->id, 'was_created' => true];
    }

    private function uniqueUsername(string $name): string
    {
        $base = mb_strtoupper(Str::slug($name, '.')) ?: 'AUDITOR';
        $username = $base;
        $i = 1;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.'.'.(++$i);
        }

        return $username;
    }

    private function availableEmail(string $email): ?string
    {
        if ($email === '') {
            return null;
        }

        // El correo es único; si ya está en uso (p. ej. buzón compartido), se deja sin correo.
        return User::query()->where('email', $email)->exists() ? null : $email;
    }
}
