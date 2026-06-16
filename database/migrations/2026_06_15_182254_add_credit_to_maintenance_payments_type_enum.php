<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add 'Credit' and 'Debit' to the maintenance_payments.type ENUM column.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE maintenance_payments MODIFY COLUMN type ENUM('Cash','Online','Created','UPI','Credit','Debit') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE maintenance_payments MODIFY COLUMN type ENUM('Cash','Online','Created','UPI') NULL");
    }
};
