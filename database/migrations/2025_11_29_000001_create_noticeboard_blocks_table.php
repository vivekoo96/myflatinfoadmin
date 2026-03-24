<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('noticeboard_blocks')) {
            Schema::create('noticeboard_blocks', function (Blueprint $table) {
                $table->unsignedBigInteger('noticeboard_id');
                $table->unsignedBigInteger('block_id');
                $table->primary(['noticeboard_id', 'block_id']);
                $table->foreign('noticeboard_id')->references('id')->on('noticeboards')->onDelete('cascade');
                $table->foreign('block_id')->references('id')->on('blocks')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('noticeboard_blocks')) {
            Schema::dropIfExists('noticeboard_blocks');
        }
    }
};