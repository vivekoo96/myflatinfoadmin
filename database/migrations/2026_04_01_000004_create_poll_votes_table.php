<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePollVotesTable extends Migration
{
    public function up()
    {
        Schema::create('poll_votes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('poll_id');
            $table->unsignedBigInteger('poll_question_id');
            $table->unsignedBigInteger('poll_option_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('flat_id')->nullable();
            $table->timestamps();

            $table->index('poll_id');
            $table->index('poll_question_id');
            $table->index('poll_option_id');
            $table->index('user_id');
            $table->index('flat_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('poll_votes');
    }
}
