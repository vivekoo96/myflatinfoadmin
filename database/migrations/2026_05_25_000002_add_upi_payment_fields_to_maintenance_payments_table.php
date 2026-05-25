<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddUpiPaymentFieldsToMaintenancePaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('maintenance_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('maintenance_payments', 'payment_screenshot')) {
                $table->string('payment_screenshot')->nullable()->after('type');
            }
            if (!Schema::hasColumn('maintenance_payments', 'upi_payment_status')) {
                $table->enum('upi_payment_status', ['Pending', 'Approved', 'Rejected'])->nullable()->after('payment_screenshot');
            }
            if (!Schema::hasColumn('maintenance_payments', 'upi_submitted_at')) {
                $table->timestamp('upi_submitted_at')->nullable()->after('upi_payment_status');
            }
            if (!Schema::hasColumn('maintenance_payments', 'upi_remarks')) {
                $table->string('upi_remarks')->nullable()->after('upi_submitted_at');
            }
        });

        // Extend the 'type' column to include 'UPI'
        // Disable strict mode to avoid 1265 warning being treated as error
        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@SESSION.sql_mode,'STRICT_TRANS_TABLES',''))");
        DB::statement("ALTER TABLE maintenance_payments MODIFY COLUMN type ENUM('Cash','Online','Created','UPI') NULL");
    }

    public function down()
    {
        Schema::table('maintenance_payments', function (Blueprint $table) {
            $table->dropColumn(['payment_screenshot', 'upi_payment_status', 'upi_submitted_at', 'upi_remarks']);
        });

        DB::statement("ALTER TABLE maintenance_payments MODIFY COLUMN type ENUM('Cash','Online','Created') NULL");
    }
}
