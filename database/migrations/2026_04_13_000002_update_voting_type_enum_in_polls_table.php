<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateVotingTypeEnumInPollsTable extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE polls MODIFY COLUMN voting_type ENUM('flat_based','user_based','owner_based','tenant_based') NOT NULL DEFAULT 'user_based'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE polls MODIFY COLUMN voting_type ENUM('flat_based','user_based') NOT NULL DEFAULT 'user_based'");
    }
}
