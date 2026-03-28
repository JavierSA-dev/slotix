<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paso 1: renombrar fecha → fecha_inicio (si aún no se hizo)
        if (Schema::hasColumn('dias_cerrados', 'fecha') && ! Schema::hasColumn('dias_cerrados', 'fecha_inicio')) {
            DB::statement('ALTER TABLE dias_cerrados CHANGE COLUMN `fecha` `fecha_inicio` DATE NOT NULL');
        }

        // Paso 2: quitar unique index si existe
        $indexes = DB::select("SHOW INDEX FROM dias_cerrados WHERE Key_name = 'dias_cerrados_fecha_unique'");
        if (! empty($indexes)) {
            DB::statement('ALTER TABLE dias_cerrados DROP INDEX dias_cerrados_fecha_unique');
        }

        // Paso 3: añadir fecha_fin si no existe
        if (! Schema::hasColumn('dias_cerrados', 'fecha_fin')) {
            DB::statement('ALTER TABLE dias_cerrados ADD COLUMN `fecha_fin` DATE NOT NULL DEFAULT "1970-01-01" AFTER `fecha_inicio`');
            DB::statement('UPDATE dias_cerrados SET fecha_fin = fecha_inicio WHERE fecha_fin = "1970-01-01"');
            DB::statement('ALTER TABLE dias_cerrados ALTER COLUMN fecha_fin DROP DEFAULT');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('dias_cerrados', 'fecha_fin')) {
            DB::statement('ALTER TABLE dias_cerrados DROP COLUMN `fecha_fin`');
        }

        if (Schema::hasColumn('dias_cerrados', 'fecha_inicio') && ! Schema::hasColumn('dias_cerrados', 'fecha')) {
            DB::statement('ALTER TABLE dias_cerrados CHANGE COLUMN `fecha_inicio` `fecha` DATE NOT NULL');
            DB::statement('ALTER TABLE dias_cerrados ADD UNIQUE INDEX dias_cerrados_fecha_unique (`fecha`)');
        }
    }
};
