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
            $table->unsignedBigInteger('poll_id');           // polls.id is bigint unsigned
            $table->unsignedBigInteger('poll_question_id');  // poll_questions.id is bigint unsigned
            $table->unsignedBigInteger('poll_option_id');    // poll_options.id is bigint unsigned
            $table->integer('user_id');                      // users.id is int
            $table->integer('flat_id')->nullable();          // flats.id is int
            $table->timestamps();

            $table->foreign('poll_id')->references('id')->on('polls')->onDelete('cascade');
            $table->foreign('poll_question_id')->references('id')->on('poll_questions')->onDelete('cascade');
            $table->foreign('poll_option_id')->references('id')->on('poll_options')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('flat_id')->references('id')->on('flats')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('poll_votes');
    }
}
