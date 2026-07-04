<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRejectedCommentToMoveInOutRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('move_in_out_requests', function (Blueprint $table) {
            $table->text('rejected_comment')->nullable()->after('comment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('move_in_out_requests', function (Blueprint $table) {
            $table->dropColumn('rejected_comment');
        });
    }
}
