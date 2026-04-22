<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatrolTaskLogsTable extends Migration
{
    public function up()
    {
        Schema::create('patrol_task_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('patrol_task_id');
            $table->unsignedBigInteger('patrol_location_id');
            $table->unsignedBigInteger('guard_user_id');
            $table->enum('checkin_type', ['photo', 'qr']);
            $table->string('photo_url')->nullable();
            $table->string('qr_scanned_value')->nullable();
            $table->datetime('checked_at');
            $table->timestamps();

            $table->index(['patrol_task_id', 'guard_user_id']);
            $table->index(['building_id', 'guard_user_id']);

            $table->foreign('building_id')->references('id')->on('buildings');
            $table->foreign('patrol_task_id')->references('id')->on('patrol_locations');
            $table->foreign('patrol_location_id')->references('id')->on('patrol_locations');
            $table->foreign('guard_user_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('patrol_task_logs');
    }
}
