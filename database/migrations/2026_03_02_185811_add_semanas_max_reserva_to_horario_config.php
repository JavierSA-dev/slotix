<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horario_config', function (Blueprint $table) {
            $table->unsignedSmallInteger('semanas_max_reserva')->default(4)->after('horas_min_cancelacion');
        });
    }

    public function down(): void
    {
        Schema::table('horario_config', function (Blueprint $table) {
            $table->dropColumn('semanas_max_reserva');
        });
    }
};
