<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staffs', function (Blueprint $table) {
            $table->id();
            $table->integer('building_id')->index();
            $table->string('staff_id', 6)->unique()->index();
            $table->string('name');
            $table->string('photo')->nullable();
            $table->string('phone', 20);
            $table->text('address')->nullable();
            $table->string('type', 50); // Maid, Cook, etc.
            $table->enum('category', ['flat_staff', 'building_staff', 'external_staff']);
            $table->boolean('is_open_to_all')->default(false);
            $table->string('document_verification')->nullable();
            $table->string('noc_police')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->integer('creator_id');
            $table->enum('creator_type', ['admin', 'flat_user']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('staffs');
    }
}
