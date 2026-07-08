<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddBankToMaintenancePaymentsTypeEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE maintenance_payments MODIFY COLUMN type ENUM('Cash','Online','Created','UPI','Credit','Debit','bank') NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE maintenance_payments MODIFY COLUMN type ENUM('Cash','Online','Created','UPI','Credit','Debit') NULL");
    }
}
