<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGateShiftToPatrolLocations extends Migration
{
    public function up()
    {
        Schema::table('patrol_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('gate_id')->nullable()->after('building_id');
            $table->unsignedBigInteger('building_shift_id')->nullable()->after('gate_id');
            $table->time('patrol_time')->nullable()->after('building_shift_id');
            $table->index('gate_id');
            $table->index('building_shift_id');
        });
    }

    public function down()
    {
        Schema::table('patrol_locations', function (Blueprint $table) {
            $table->dropIndex(['gate_id']);
            $table->dropIndex(['building_shift_id']);
            $table->dropColumn('gate_id');
            $table->dropColumn('building_shift_id');
            $table->dropColumn('patrol_time');
        });
    }
}
