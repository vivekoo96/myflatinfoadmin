<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuardPatrolsTable extends Migration
{
    public function up()
    {
        Schema::create('guard_patrols', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('guard_user_id');
            $table->unsignedBigInteger('patrol_location_id');
            $table->enum('checkin_type', ['photo', 'qr']);
            $table->enum('shift', ['Day', 'Night']);
            $table->string('photo_url')->nullable();
            $table->string('qr_scanned_value')->nullable();
            $table->dateTime('checked_in_at');
            $table->timestamps();
            $table->index('building_id');
            $table->index('guard_user_id');
            $table->index('patrol_location_id');
            $table->index('checked_in_at');
            $table->index('shift');
        });
    }

    public function down()
    {
        Schema::dropIfExists('guard_patrols');
    }
}
