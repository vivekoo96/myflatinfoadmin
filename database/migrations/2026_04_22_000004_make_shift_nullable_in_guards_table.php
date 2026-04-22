<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeShiftNullableInGuardsTable extends Migration
{
    public function up()
    {
        Schema::table('guards', function (Blueprint $table) {
            if (Schema::hasColumn('guards', 'shift')) {
                $table->string('shift')->nullable()->change();
            }
        });
    }

    public function down()
    {
        Schema::table('guards', function (Blueprint $table) {
            if (Schema::hasColumn('guards', 'shift')) {
                $table->string('shift')->nullable(false)->change();
            }
        });
    }
}
