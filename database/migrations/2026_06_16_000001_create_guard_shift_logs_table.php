<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuardShiftLogsTable extends Migration
{
    public function up()
    {
        Schema::create('guard_shift_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('guard_user_id');
            $table->unsignedBigInteger('gate_id')->nullable();
            $table->unsignedBigInteger('building_shift_id')->nullable();
            $table->unsignedBigInteger('assignment_id')->nullable(); // guard_patrol_assignments.id
            $table->date('shift_date');
            $table->enum('status', ['pending', 'active', 'handover_pending', 'completed', 'late', 'absent'])->default('pending');
            $table->dateTime('checked_in_at')->nullable();
            $table->dateTime('checked_out_at')->nullable();
            $table->unsignedInteger('late_minutes')->default(0);           // checked_in_at vs shift start
            $table->unsignedInteger('early_arrival_minutes')->default(0);  // for incoming guard
            $table->unsignedBigInteger('handover_confirmed_by')->nullable(); // incoming guard user_id
            $table->dateTime('handover_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['building_id', 'shift_date']);
            $table->index(['building_id', 'guard_user_id']);
            $table->index(['building_id', 'gate_id', 'shift_date']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('guard_shift_logs');
    }
}
