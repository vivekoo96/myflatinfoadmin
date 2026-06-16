<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftHandoversTable extends Migration
{
    public function up()
    {
        Schema::create('shift_handovers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('gate_id')->nullable();
            $table->unsignedBigInteger('building_shift_id')->nullable();
            $table->date('shift_date');
            $table->unsignedBigInteger('outgoing_guard_id');
            $table->unsignedBigInteger('incoming_guard_id');
            $table->unsignedBigInteger('outgoing_log_id')->nullable(); // guard_shift_logs.id
            $table->unsignedBigInteger('incoming_log_id')->nullable(); // guard_shift_logs.id
            $table->dateTime('incoming_arrived_at')->nullable();
            $table->dateTime('outgoing_confirmed_at')->nullable();
            $table->dateTime('incoming_confirmed_at')->nullable();
            $table->enum('status', ['pending_incoming', 'pending_outgoing', 'completed'])->default('pending_incoming');
            $table->unsignedInteger('late_minutes')->default(0); // incoming late vs shift start
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['building_id', 'shift_date']);
            $table->index(['building_id', 'gate_id', 'shift_date']);
            $table->index(['incoming_guard_id', 'shift_date']);
            $table->index(['outgoing_guard_id', 'shift_date']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_handovers');
    }
}
