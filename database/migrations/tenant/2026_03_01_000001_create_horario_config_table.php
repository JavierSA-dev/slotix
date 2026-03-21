<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horario_config', function (Blueprint $table) {
            $table->id();
            $table->json('dias_semana');
            $table->decimal('hora_apertura', 4, 2);
            $table->decimal('hora_cierre', 4, 2);
            $table->integer('duracion_tramo');
            $table->integer('aforo_por_tramo');
            $table->boolean('activo')->default(true);
            $table->boolean('en_mantenimiento')->default(false);
            $table->unsignedSmallInteger('horas_min_reserva')->default(0);
            $table->unsignedSmallInteger('horas_min_cancelacion')->default(0);
            $table->unsignedSmallInteger('semanas_max_reserva')->default(4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horario_config');
    }
};
