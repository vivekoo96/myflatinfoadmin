<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDateTimeToMeetingMinutesTable extends Migration
{
    public function up()
    {
        Schema::table('meeting_minutes', function (Blueprint $table) {
            $table->date('date')->nullable()->after('description');
            $table->time('time')->nullable()->after('date');
            $table->index('date');
        });
    }

    public function down()
    {
        Schema::table('meeting_minutes', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropColumn(['date', 'time']);
        });
    }
}
