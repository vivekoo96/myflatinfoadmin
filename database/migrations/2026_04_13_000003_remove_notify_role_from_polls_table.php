<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveNotifyRoleFromPollsTable extends Migration
{
    public function up()
    {
        Schema::table('polls', function (Blueprint $table) {
            $table->dropColumn('notify_role');
        });
    }

    public function down()
    {
        Schema::table('polls', function (Blueprint $table) {
            $table->enum('notify_role', ['all', 'president', 'building_admin', 'owner', 'tenant'])
                  ->default('all')
                  ->after('voting_type');
        });
    }
}
