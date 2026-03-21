<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migra las columnas de hora de decimal (10.5 = 10:30) a smallint unsigned (630 = 10:30 minutos).
 * Aplica a las tablas horario_config y reservas de cada BD tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── reservas ──────────────────────────────────────────────────────
        Schema::table('reservas', function (Blueprint $table) {
            $table->unsignedSmallInteger('hora_inicio_min')->default(0)->after('hora_inicio');
            $table->unsignedSmallInteger('hora_fin_min')->default(0)->after('hora_fin');
        });

        DB::statement('UPDATE reservas SET hora_inicio_min = ROUND(hora_inicio * 60), hora_fin_min = ROUND(hora_fin * 60)');

        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn(['hora_inicio', 'hora_fin']);
        });

        Schema::table('reservas', function (Blueprint $table) {
            $table->renameColumn('hora_inicio_min', 'hora_inicio');
            $table->renameColumn('hora_fin_min', 'hora_fin');
        });

        // ── horario_config ─────────────────────────────────────────────────
        Schema::table('horario_config', function (Blueprint $table) {
            $table->unsignedSmallInteger('hora_apertura_min')->default(0)->after('hora_apertura');
            $table->unsignedSmallInteger('hora_cierre_min')->default(0)->after('hora_cierre');
        });

        DB::statement('UPDATE horario_config SET hora_apertura_min = ROUND(hora_apertura * 60), hora_cierre_min = ROUND(hora_cierre * 60)');

        Schema::table('horario_config', function (Blueprint $table) {
            $table->dropColumn(['hora_apertura', 'hora_cierre']);
        });

        Schema::table('horario_config', function (Blueprint $table) {
            $table->renameColumn('hora_apertura_min', 'hora_apertura');
            $table->renameColumn('hora_cierre_min', 'hora_cierre');
        });
    }

    public function down(): void
    {
        // ── reservas ──────────────────────────────────────────────────────
        Schema::table('reservas', function (Blueprint $table) {
            $table->decimal('hora_inicio_dec', 4, 2)->default(0)->after('hora_inicio');
            $table->decimal('hora_fin_dec', 4, 2)->default(0)->after('hora_fin');
        });

        DB::statement('UPDATE reservas SET hora_inicio_dec = hora_inicio / 60, hora_fin_dec = hora_fin / 60');

        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn(['hora_inicio', 'hora_fin']);
        });

        Schema::table('reservas', function (Blueprint $table) {
            $table->renameColumn('hora_inicio_dec', 'hora_inicio');
            $table->renameColumn('hora_fin_dec', 'hora_fin');
        });

        // ── horario_config ─────────────────────────────────────────────────
        Schema::table('horario_config', function (Blueprint $table) {
            $table->decimal('hora_apertura_dec', 4, 2)->default(0)->after('hora_apertura');
            $table->decimal('hora_cierre_dec', 4, 2)->default(0)->after('hora_cierre');
        });

        DB::statement('UPDATE horario_config SET hora_apertura_dec = hora_apertura / 60, hora_cierre_dec = hora_cierre / 60');

        Schema::table('horario_config', function (Blueprint $table) {
            $table->dropColumn(['hora_apertura', 'hora_cierre']);
        });

        Schema::table('horario_config', function (Blueprint $table) {
            $table->renameColumn('hora_apertura_dec', 'hora_apertura');
            $table->renameColumn('hora_cierre_dec', 'hora_cierre');
        });
    }
};
