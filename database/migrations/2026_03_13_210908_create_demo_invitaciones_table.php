<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('demo_invitaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->unique();
            $table->foreignId('creada_por')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expira_en');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('demo_invitaciones');
    }
};
