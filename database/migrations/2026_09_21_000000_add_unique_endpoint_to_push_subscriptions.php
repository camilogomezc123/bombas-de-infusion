<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un endpoint de Web Push identifica una única suscripción de navegador — le falta
 * una restricción real de unicidad (antes era `text`, que no admite índice único
 * portable entre motores). Se pasa a `string` con un límite generoso (los endpoints
 * reales rondan 150-250 caracteres) para poder indexarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->string('endpoint', 500)->change();
        });

        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->unique('endpoint');
        });
    }

    public function down(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropUnique(['endpoint']);
            $table->text('endpoint')->change();
        });
    }
};
