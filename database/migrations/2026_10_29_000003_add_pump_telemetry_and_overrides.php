<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telemetría operativa del dispositivo (estado, batería, canal, volumen/tiempo
 * restante) y trazabilidad de quién anuló un límite o programó fuera de la librería de
 * dosis segura — lo que las centrales tipo Fresenius Agilia Link+ / B.Braun OnlineSuite
 * ya muestran junto al dato puramente clínico que capturamos en el incremento anterior.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pump_devices', function (Blueprint $table) {
            $table->string('status')->nullable()->after('is_active'); // infusing | paused | occlusion | air_in_line | battery_low | completed | idle
            $table->unsignedTinyInteger('battery_pct')->nullable()->after('status');
            $table->unsignedTinyInteger('channel_count')->default(1)->after('battery_pct');
            $table->dateTime('last_status_at')->nullable()->after('channel_count');
        });

        Schema::table('infusion_events', function (Blueprint $table) {
            $table->unsignedTinyInteger('channel_number')->default(1)->after('pump_device_id');
            $table->string('device_status')->nullable()->after('channel_number');
            $table->unsignedTinyInteger('battery_pct')->nullable()->after('device_status');
            $table->decimal('volume_remaining_ml', 10, 2)->nullable()->after('battery_pct');
            $table->unsignedInteger('infusion_duration_planned_minutes')->nullable()->after('volume_remaining_ml');
            $table->boolean('used_drug_library')->default(true)->after('infusion_duration_planned_minutes');
            $table->string('overridden_by_operator')->nullable()->after('used_drug_library');
        });
    }

    public function down(): void
    {
        Schema::table('infusion_events', function (Blueprint $table) {
            $table->dropColumn([
                'channel_number', 'device_status', 'battery_pct', 'volume_remaining_ml',
                'infusion_duration_planned_minutes', 'used_drug_library', 'overridden_by_operator',
            ]);
        });

        Schema::table('pump_devices', function (Blueprint $table) {
            $table->dropColumn(['status', 'battery_pct', 'channel_count', 'last_status_at']);
        });
    }
};
