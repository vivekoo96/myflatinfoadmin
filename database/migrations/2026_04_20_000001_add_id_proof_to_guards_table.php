<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIdProofToGuardsTable extends Migration
{
    public function up()
    {
        try {
            if (!Schema::hasColumn('guards', 'id_proof_type')) {
                DB::statement('SET session sql_mode=""');
                DB::statement('ALTER TABLE guards ADD COLUMN id_proof_type VARCHAR(255) NULL');
                DB::statement('ALTER TABLE guards ADD COLUMN id_proof_number VARCHAR(255) NULL');
            }
        } catch (\Exception $e) {
            \Log::warning('Could not modify guards table in strict mode: ' . $e->getMessage());
            // Try without strict mode handling
            if (!Schema::hasColumn('guards', 'id_proof_type')) {
                DB::statement('ALTER TABLE `guards` ADD `id_proof_type` VARCHAR(255) NULL DEFAULT NULL');
                DB::statement('ALTER TABLE `guards` ADD `id_proof_number` VARCHAR(255) NULL DEFAULT NULL');
            }
        }
    }

    public function down()
    {
        try {
            if (Schema::hasColumn('guards', 'id_proof_type')) {
                DB::statement('ALTER TABLE `guards` DROP COLUMN `id_proof_type`');
            }
            if (Schema::hasColumn('guards', 'id_proof_number')) {
                DB::statement('ALTER TABLE `guards` DROP COLUMN `id_proof_number`');
            }
        } catch (\Exception $e) {
            \Log::warning('Could not drop columns from guards: ' . $e->getMessage());
        }
    }
}
