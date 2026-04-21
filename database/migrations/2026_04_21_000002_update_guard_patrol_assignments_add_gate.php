<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateGuardPatrolAssignmentsAddGate extends Migration
{
    public function up()
    {
        Schema::table('guard_patrol_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('gate_id')->nullable()->after('building_shift_id');
            $table->index('gate_id');
            $table->change('patrol_location_id')->nullable()->change();
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
