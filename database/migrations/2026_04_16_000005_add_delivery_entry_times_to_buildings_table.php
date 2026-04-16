<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryEntryTimesToBuildingsTable extends Migration
{
    public function up()
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->time('delivery_entry_start_time')->nullable()->default('08:00')->after('max_activity_posts');
            $table->time('delivery_entry_end_time')->nullable()->default('21:00')->after('delivery_entry_start_time');
        });
    }

    public function down()
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn(['delivery_entry_start_time', 'delivery_entry_end_time']);
        });
    }
}
