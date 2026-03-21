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
        Schema::connection('central')->create('admin_notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('empresa_id');
            $table->string('tipo'); // nueva_reserva | cancelacion | cambio_estado | cambio_fecha
            $table->json('datos');  // nombre, fecha, hora, personas, token, etc.
            $table->boolean('leida')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'leida']);
            $table->index(['empresa_id', 'leida']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('central')->dropIfExists('admin_notificaciones');
    }
};
