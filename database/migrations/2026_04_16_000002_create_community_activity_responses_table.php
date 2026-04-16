<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommunityActivityResponsesTable extends Migration
{
    public function up()
    {
        Schema::create('community_activity_responses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('community_activity_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('flat_id');
            $table->enum('response', ['interested', 'not_interested', 'attending', 'maybe', 'not_attending']);
            $table->timestamps();

            $table->index('community_activity_id');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('community_activity_responses');
    }
}
