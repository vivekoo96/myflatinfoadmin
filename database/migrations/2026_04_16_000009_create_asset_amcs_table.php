<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetAmcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_amcs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('asset_id');
            $table->string('provider_name');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('service_frequency', ['Monthly', 'Quarterly', 'Half-Yearly', 'Yearly']);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('building_id');
            $table->index('asset_id');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asset_amcs');
    }
}
