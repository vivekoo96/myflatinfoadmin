<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateGuardPatrolsShiftColumn extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE guard_patrols MODIFY COLUMN shift VARCHAR(100) NOT NULL");
        Schema::table('guard_patrols', function (Blueprint $table) {
            $table->unsignedBigInteger('building_shift_id')->nullable()->after('shift');
            $table->index('building_shift_id');
        });
    }

    public function down()
    {
        Schema::table('guard_patrols', function (Blueprint $table) {
            $table->dropColumn('building_shift_id');
        });
        DB::statement("ALTER TABLE guard_patrols MODIFY COLUMN shift ENUM('Day','Night') NOT NULL");
    }
}
