<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddGateIdToGuardPatrolAssignments extends Migration
{
    public function up()
    {
        Schema::table('guard_patrol_assignments', function (Blueprint $table) {
            // Make patrol_location_id nullable since we're moving to gate_id
            DB::statement('ALTER TABLE guard_patrol_assignments MODIFY patrol_location_id BIGINT UNSIGNED NULL');

            $table->unsignedBigInteger('gate_id')->nullable()->after('building_shift_id');
            $table->index('gate_id');
        });
    }

    public function down()
    {
        Schema::table('guard_patrol_assignments', function (Blueprint $table) {
            $table->dropIndex(['gate_id']);
            $table->dropColumn('gate_id');
        });
    }
}
