<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdProofToGuardsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('guards', 'id_proof_type')) {
            Schema::table('guards', function (Blueprint $table) {
                $table->string('id_proof_type')->nullable();
                $table->string('id_proof_number')->nullable();
            });
        }
    }

    public function down()
    {
        Schema::table('guards', function (Blueprint $table) {
            if (Schema::hasColumn('guards', 'id_proof_type')) {
                $table->dropColumn('id_proof_type');
            }
            if (Schema::hasColumn('guards', 'id_proof_number')) {
                $table->dropColumn('id_proof_number');
            }
        });
    }
}
