<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDutyCheckinsTable extends Migration
{
    public function up()
    {
        Schema::create('duty_checkins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('guard_user_id');
            $table->unsignedBigInteger('gate_id')->nullable();
            $table->unsignedBigInteger('building_shift_id')->nullable();
            $table->enum('status', ['on_time', 'delayed', 'missed'])->default('on_time');
            $table->dateTime('scheduled_at');
            $table->dateTime('checked_in_at');
            $table->timestamps();

            $table->index(['building_id', 'guard_user_id']);
            $table->index(['building_id', 'gate_id']);
            $table->index(['checked_in_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('duty_checkins');
    }
}
