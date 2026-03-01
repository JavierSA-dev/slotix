<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('horario_config', function (Blueprint $table) {
            $table->unsignedSmallInteger('horas_min_reserva')->default(0)->after('aforo_por_tramo');
            $table->unsignedSmallInteger('horas_min_cancelacion')->default(0)->after('horas_min_reserva');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horario_config', function (Blueprint $table) {
            $table->dropColumn(['horas_min_reserva', 'horas_min_cancelacion']);
        });
    }
};
