<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdProofToGuardsTable extends Migration
{
    public function up()
    {
        Schema::table('guards', function (Blueprint $table) {
            $table->string('id_proof_type')->nullable()->after('status');
            $table->string('id_proof_number')->nullable()->after('id_proof_type');
        });
    }

    public function down()
    {
        Schema::table('guards', function (Blueprint $table) {
            $table->dropColumn(['id_proof_type', 'id_proof_number']);
        });
    }
}
