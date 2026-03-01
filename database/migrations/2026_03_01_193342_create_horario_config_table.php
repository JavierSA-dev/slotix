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
        Schema::create('horario_config', function (Blueprint $table) {
            $table->id();
            $table->json('dias_semana');
            $table->decimal('hora_apertura', 4, 2);
            $table->decimal('hora_cierre', 4, 2);
            $table->integer('duracion_tramo');
            $table->integer('aforo_por_tramo');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horario_config');
    }
};
