<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePollsTable extends Migration
{
    public function up()
    {
        Schema::create('polls', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id');   // buildings.id is bigint unsigned
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['poll', 'survey'])->default('poll');
            $table->enum('structure', ['single', 'multiple'])->default('single');
            $table->enum('voting_type', ['flat_based', 'user_based'])->default('user_based');
            $table->enum('status', ['draft', 'active', 'closed', 'published'])->default('draft');
            $table->datetime('expiry_date')->nullable();
            $table->integer('created_by');               // users.id is int
            $table->string('created_by_role')->nullable();
            $table->datetime('result_released_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('building_id')->references('id')->on('buildings')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('polls');
    }
}
