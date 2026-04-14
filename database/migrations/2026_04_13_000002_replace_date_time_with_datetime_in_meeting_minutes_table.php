<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ReplaceDateTimeWithDatetimeInMeetingMinutesTable extends Migration
{
    public function up()
    {
        Schema::table('meeting_minutes', function (Blueprint $table) {
            // Drop old columns if they exist
            if (Schema::hasColumn('meeting_minutes', 'date')) {
                $table->dropIndex(['date']);
                $table->dropColumn('date');
            }
            if (Schema::hasColumn('meeting_minutes', 'time')) {
                $table->dropColumn('time');
            }

            // Add new datetime column
            $table->dateTime('datetime')->nullable()->after('description');
            $table->index('datetime');
        });
    }

    public function down()
    {
        Schema::table('meeting_minutes', function (Blueprint $table) {
            if (Schema::hasColumn('meeting_minutes', 'datetime')) {
                $table->dropIndex(['datetime']);
                $table->dropColumn('datetime');
            }

            // Restore old columns
            $table->date('date')->nullable()->after('description');
            $table->time('time')->nullable()->after('date');
            $table->index('date');
        });
    }
}
