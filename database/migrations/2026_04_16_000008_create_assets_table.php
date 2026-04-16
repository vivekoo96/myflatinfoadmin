<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id');
            $table->string('name');
            $table->string('category');
            $table->unsignedInteger('total_quantity')->default(1);
            $table->unsignedInteger('available_qty')->default(0);
            $table->unsignedInteger('used_qty')->default(0);
            $table->unsignedInteger('damaged_qty')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(2);
            $table->string('location')->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('vendor_contact')->nullable();
            $table->enum('status', ['active', 'under_repair', 'disposed'])->default('active');
            $table->softDeletes();
            $table->timestamps();
            $table->index('building_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assets');
    }
}
