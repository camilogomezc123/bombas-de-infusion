<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Doble verificación para fármacos de alto riesgo (vasoactivos, opioides, sedación):
 * la bomba ya nos dice quién anuló un límite (overridden_by_operator), pero no quién
 * verificó la programación normal — ese es un paso de enfermería que se registra aquí
 * manualmente, no un dato que la bomba reporte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('infusion_events', function (Blueprint $table) {
            $table->foreignId('co_signed_by')->nullable()->after('overridden_by_operator')->constrained('users')->nullOnDelete();
            $table->dateTime('co_signed_at')->nullable()->after('co_signed_by');
        });
    }

    public function down(): void
    {
        Schema::table('infusion_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('co_signed_by');
            $table->dropColumn('co_signed_at');
        });
    }
};
