<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDutyCheckinIntervalToBuildingsTable extends Migration
{
    public function up()
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->unsignedInteger('duty_checkin_interval_minutes')->default(30)->after('building_policy');
        });
    }

    public function down()
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('duty_checkin_interval_minutes');
        });
    }
}
