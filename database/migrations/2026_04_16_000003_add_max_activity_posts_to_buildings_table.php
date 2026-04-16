<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaxActivityPostsToBuildingsTable extends Migration
{
    public function up()
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->unsignedInteger('max_activity_posts')->default(5)->after('building_policy');
        });
    }

    public function down()
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('max_activity_posts');
        });
    }
}
