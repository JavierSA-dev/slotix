<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            // user_id referencia a la BD central (users). Sin FK a nivel BD por ser cross-database.
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('nombre');
            $table->string('email');
            $table->string('telefono')->nullable();
            $table->date('fecha');
            $table->decimal('hora_inicio', 4, 2);
            $table->decimal('hora_fin', 4, 2);
            $table->integer('num_personas');
            $table->string('token')->unique();
            $table->enum('estado', ['pendiente', 'confirmada', 'cancelada'])->default('pendiente');
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
