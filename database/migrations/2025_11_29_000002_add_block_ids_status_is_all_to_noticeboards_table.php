<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('noticeboards')) {
            Schema::table('noticeboards', function (Blueprint $table) {
                if (! Schema::hasColumn('noticeboards', 'block_ids')) {
                    $table->text('block_ids')->nullable()->after('desc');
                }
                if (! Schema::hasColumn('noticeboards', 'status')) {
                    $table->string('status')->nullable()->after('block_ids');
                }
                if (! Schema::hasColumn('noticeboards', 'is_all_blocks')) {
                    $table->boolean('is_all_blocks')->default(false)->after('status');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('noticeboards')) {
            Schema::table('noticeboards', function (Blueprint $table) {
                if (Schema::hasColumn('noticeboards', 'block_ids')) {
                    $table->dropColumn('block_ids');
                }
                if (Schema::hasColumn('noticeboards', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('noticeboards', 'is_all_blocks')) {
                    $table->dropColumn('is_all_blocks');
                }
            });
        }
    }
};