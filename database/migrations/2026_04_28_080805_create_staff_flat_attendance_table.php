<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffFlatAttendanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff_flat_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_log_id')->index();
            $table->unsignedBigInteger('staff_id')->index();
            $table->integer('flat_id')->index();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('staff_flat_attendance');
    }
}
