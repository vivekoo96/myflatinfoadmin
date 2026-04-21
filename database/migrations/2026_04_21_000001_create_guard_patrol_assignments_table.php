<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuardPatrolAssignmentsTable extends Migration
{
    public function up()
    {
        Schema::create('guard_patrol_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('guard_user_id');
            $table->unsignedBigInteger('patrol_location_id');
            $table->unsignedBigInteger('building_shift_id');
            $table->text('notes')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->unsignedBigInteger('assigned_by');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['building_id', 'guard_user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('guard_patrol_assignments');
    }
}
