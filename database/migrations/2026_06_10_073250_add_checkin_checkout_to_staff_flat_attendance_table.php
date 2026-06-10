<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckinCheckoutToStaffFlatAttendanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('staff_flat_attendance', function (Blueprint $table) {
            $table->date('date')->nullable()->after('flat_id');
            $table->timestamp('check_in_time')->nullable()->after('date');
            $table->timestamp('check_out_time')->nullable()->after('check_in_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('staff_flat_attendance', function (Blueprint $table) {
            $table->dropColumn(['date', 'check_in_time', 'check_out_time']);
        });
    }
}
