<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatrolDailyLogsTable extends Migration
{
    public function up()
    {
        Schema::create('patrol_daily_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('patrol_schedule_id'); // patrol_locations.id where gate_id is set
            $table->unsignedBigInteger('patrol_location_id'); // patrol_locations.id where gate_id is null
            $table->unsignedBigInteger('guard_user_id');
            $table->date('patrol_date');
            $table->enum('checkin_type', ['photo', 'qr']);
            $table->string('photo_url')->nullable();
            $table->string('qr_scanned_value')->nullable();
            $table->datetime('checked_at');
            $table->timestamps();

            $table->index(['patrol_schedule_id', 'patrol_date']);
            $table->index(['guard_user_id', 'patrol_date']);
            $table->index(['building_id', 'patrol_date']);
            // Prevent duplicate check-in for same location+schedule+guard+date
            $table->unique(
                ['patrol_schedule_id', 'patrol_location_id', 'guard_user_id', 'patrol_date'],
                'unique_patrol_daily_checkin'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('patrol_daily_logs');
    }
}
