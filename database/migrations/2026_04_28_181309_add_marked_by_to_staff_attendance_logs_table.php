<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMarkedByToStaffAttendanceLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('staff_attendance_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('marked_by')->nullable()->after('status');
            $table->string('source')->nullable()->after('marked_by'); // admin, gate, department, self
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('staff_attendance_logs', function (Blueprint $table) {
            $table->dropColumn(['marked_by', 'source']);
        });
    }
}
