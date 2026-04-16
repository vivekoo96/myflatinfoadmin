<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommunityActivitiesTable extends Migration
{
    public function up()
    {
        Schema::create('community_activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('flat_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('activity_datetime');
            $table->enum('response_type', ['simple', 'detailed']);
            $table->enum('post_type', ['slot', 'unlimited']);
            $table->unsignedInteger('max_slots')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('building_id');
            $table->index('user_id');
            $table->index('activity_datetime');
        });
    }

    public function down()
    {
        Schema::dropIfExists('community_activities');
    }
}
