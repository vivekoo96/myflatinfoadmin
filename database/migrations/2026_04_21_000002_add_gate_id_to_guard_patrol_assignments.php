<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGateIdToGuardPatrolAssignments extends Migration
{
    public function up()
    {
        Schema::table('guard_patrol_assignments', function (Blueprint $table) {
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
