<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LEADER_EMAIL = 'programa.acv@clinicadeoccidente.com';

    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('users')
                ->where('username', 'MARIA.JOSE.CARVAJAL')
                ->update([
                    'email' => null,
                    'role' => UserRole::Auditor->value,
                    'is_active' => false,
                    'updated_at' => now(),
                ]);

            DB::table('users')
                ->where('username', 'LAURA.GALARZA')
                ->update([
                    'email' => self::LEADER_EMAIL,
                    'role' => UserRole::Leader->value,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('users')
                ->where('username', 'LAURA.GALARZA')
                ->update([
                    'email' => null,
                    'role' => UserRole::Auditor->value,
                    'updated_at' => now(),
                ]);

            DB::table('users')
                ->where('username', 'MARIA.JOSE.CARVAJAL')
                ->update([
                    'email' => self::LEADER_EMAIL,
                    'role' => UserRole::Auditor->value,
                    'is_active' => false,
                    'updated_at' => now(),
                ]);
        });
    }
};
