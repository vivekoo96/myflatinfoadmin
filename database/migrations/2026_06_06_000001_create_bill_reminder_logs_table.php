<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillReminderLogsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bill_reminder_logs')) {
            Schema::create('bill_reminder_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('building_id')->index();
                $table->unsignedBigInteger('flat_id')->index();
                $table->enum('bill_type', ['maintenance', 'essential']);
                $table->unsignedBigInteger('sent_by')->nullable();
                $table->unsignedInteger('recipients_count')->default(0);
                $table->timestamps();

                $table->index(['building_id', 'flat_id', 'bill_type', 'created_at'], 'reminder_cooldown_idx');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('bill_reminder_logs');
    }
}
