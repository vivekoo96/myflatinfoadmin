<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSecurityNotesTable extends Migration
{
    public function up()
    {
        Schema::create('security_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('guard_user_id');
            $table->unsignedBigInteger('gate_id')->nullable();
            $table->string('title');
            $table->text('body');
            $table->dateTime('noted_at');
            $table->timestamps();

            $table->index('building_id');
            $table->index('guard_user_id');
            $table->index('noted_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('security_notes');
    }
}
