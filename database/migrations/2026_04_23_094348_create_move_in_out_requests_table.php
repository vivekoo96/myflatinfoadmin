<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMoveInOutRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('move_in_out_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('flat_id');
            $table->unsignedBigInteger('user_id')->nullable(); // existing user (owner/tanent)
            $table->enum('type', ['Move-In', 'Move-Out']);
            $table->enum('person_type', ['Owner', 'Tanent']);
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('id_proof')->nullable(); // filename
            $table->date('date_of_entry_exit');
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->string('passcode')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Completed'])->default('Pending');
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('visited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('move_in_out_requests');
    }
}
