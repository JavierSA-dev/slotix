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
        Schema::create('empresa_modulo', function (Blueprint $table) {
            $table->id();
            $table->string('empresa_id');
            $table->foreignId('modulo_id')->constrained('modulos')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'modulo_id']);
            $table->foreign('empresa_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa_modulo');
    }
};
