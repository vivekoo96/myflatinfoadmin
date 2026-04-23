<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateMoveInOutRequestsStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE move_in_out_requests MODIFY COLUMN status ENUM('Pending', 'Pending Owner', 'Pending Accounts', 'Approved', 'Rejected', 'Completed') DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('move_in_out_requests', function (Blueprint $table) {
            //
        });
    }
}
