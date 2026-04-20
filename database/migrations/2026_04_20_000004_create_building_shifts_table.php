<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuildingShiftsTable extends Migration
{
    public function up()
    {
        Schema::create('building_shifts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id');
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
            $table->index('building_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('building_shifts');
    }
}
