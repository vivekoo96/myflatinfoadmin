<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePollOptionsTable extends Migration
{
    public function up()
    {
        Schema::create('poll_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('poll_question_id');  // poll_questions.id is bigint unsigned
            $table->string('option_text');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('poll_question_id')->references('id')->on('poll_questions')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('poll_options');
    }
}
