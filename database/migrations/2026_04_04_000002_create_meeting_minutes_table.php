<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeetingMinutesTable extends Migration
{
    public function up()
    {
        Schema::create('meeting_minutes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id');
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('created_by');
            $table->string('created_by_role')->nullable();
            $table->timestamps();

            $table->index('building_id');
            $table->index('created_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('meeting_minutes');
    }
}
