<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatrolLocationsTable extends Migration
{
    public function up()
    {
        Schema::create('patrol_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('qr_string', 64)->unique();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->softDeletes();
            $table->timestamps();
            $table->index('building_id');
            $table->index('qr_string');
        });
    }

    public function down()
    {
        Schema::dropIfExists('patrol_locations');
    }
}
