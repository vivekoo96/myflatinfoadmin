<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePollQuestionsTable extends Migration
{
    public function up()
    {
        Schema::create('poll_questions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('poll_id');       // polls.id is bigint unsigned
            $table->string('question');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('poll_id')->references('id')->on('polls')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('poll_questions');
    }
}
