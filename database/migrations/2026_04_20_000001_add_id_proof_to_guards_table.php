<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIdProofToGuardsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('guards', 'id_proof_type')) {
            DB::statement('ALTER TABLE guards ADD COLUMN id_proof_type VARCHAR(255) NULL');
            DB::statement('ALTER TABLE guards ADD COLUMN id_proof_number VARCHAR(255) NULL');
        }
    }

    public function down()
    {
        if (Schema::hasColumn('guards', 'id_proof_type')) {
            DB::statement('ALTER TABLE guards DROP COLUMN id_proof_type');
        }
        if (Schema::hasColumn('guards', 'id_proof_number')) {
            DB::statement('ALTER TABLE guards DROP COLUMN id_proof_number');
        }
    }
}
