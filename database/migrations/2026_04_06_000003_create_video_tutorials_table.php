<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideoTutorialsTable extends Migration
{
    public function up()
    {
        Schema::create('video_tutorials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('module_id');
            $table->string('title');
            $table->text('text')->nullable();
            $table->string('video_url');
            $table->string('video_type')->default('youtube');
            $table->json('interfaces')->nullable();
            $table->timestamps();

            $table->foreign('module_id')->references('id')->on('video_modules')->onDelete('cascade');
            $table->index('module_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('video_tutorials');
    }
}
