<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBankDetailsToBuildingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('is_upi_enabled');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_ifsc_code')->nullable()->after('bank_account_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_number', 'bank_ifsc_code']);
        });
    }
}
