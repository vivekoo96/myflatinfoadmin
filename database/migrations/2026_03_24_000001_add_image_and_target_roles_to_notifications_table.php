<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageAndTargetRolesToNotificationsTable extends Migration
{
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('image')->nullable()->after('body');
            $table->json('target_roles')->nullable()->after('image');
        });
    }

    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['image', 'target_roles']);
        });
    }
}
