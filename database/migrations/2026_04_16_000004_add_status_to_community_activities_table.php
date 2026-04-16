<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToCommunityActivitiesTable extends Migration
{
    public function up()
    {
        Schema::table('community_activities', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('max_slots');
        });
    }

    public function down()
    {
        Schema::table('community_activities', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
