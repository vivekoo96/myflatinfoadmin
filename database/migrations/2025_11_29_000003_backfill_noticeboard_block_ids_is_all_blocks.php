<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('noticeboards') || ! Schema::hasTable('noticeboard_blocks')) {
            return;
        }

        $noticeIds = DB::table('noticeboards')->pluck('id');
        foreach ($noticeIds as $id) {
            $blockIds = DB::table('noticeboard_blocks')->where('noticeboard_id', $id)->pluck('block_id')->toArray();
            // save block_ids JSON
            if (Schema::hasColumn('noticeboards', 'block_ids')) {
                DB::table('noticeboards')->where('id', $id)->update(['block_ids' => json_encode($blockIds)]);
            }

            if (count($blockIds) > 0 && Schema::hasTable('blocks')) {
                $buildingId = DB::table('noticeboards')->where('id', $id)->value('building_id');
                // get active blocks for building
                $active = DB::table('blocks')->where('building_id', $buildingId)->where('status', 'Active')->pluck('id')->toArray();
                $isAll = (count($active) > 0 && count(array_diff($active, $blockIds)) == 0);
                if (Schema::hasColumn('noticeboards', 'is_all_blocks')) {
                    DB::table('noticeboards')->where('id', $id)->update(['is_all_blocks' => $isAll]);
                }
            }
        }
    }

    public function down()
    {
        // Not destructive, but clear fields
        if (! Schema::hasTable('noticeboards')) {
            return;
        }
        if (Schema::hasColumn('noticeboards', 'block_ids')) {
            DB::table('noticeboards')->update(['block_ids'=>null]);
        }
        if (Schema::hasColumn('noticeboards', 'is_all_blocks')) {
            DB::table('noticeboards')->update(['is_all_blocks'=>false]);
        }
    }
};