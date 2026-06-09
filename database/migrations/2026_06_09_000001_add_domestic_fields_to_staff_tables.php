<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDomesticFieldsToStaffTables extends Migration
{
    public function up()
    {
        if (Schema::hasTable('staffs')) {
            Schema::table('staffs', function (Blueprint $table) {
                // Verification status of the uploaded document (document_verification holds
                // the uploaded file path; noc_police holds the optional NOC file path).
                if (!Schema::hasColumn('staffs', 'document_status')) {
                    $table->enum('document_status', ['Pending', 'Verified'])
                          ->nullable()
                          ->after('noc_police');
                }
            });
        }

        if (Schema::hasTable('staff_flat_tags')) {
            Schema::table('staff_flat_tags', function (Blueprint $table) {
                // Engagement when a staff is assigned to a single flat.
                if (!Schema::hasColumn('staff_flat_tags', 'engagement_type')) {
                    $table->enum('engagement_type', ['In-house', 'Timely-basis'])
                          ->nullable()
                          ->after('time_slot');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('staffs') && Schema::hasColumn('staffs', 'document_status')) {
            Schema::table('staffs', function (Blueprint $table) {
                $table->dropColumn('document_status');
            });
        }

        if (Schema::hasTable('staff_flat_tags') && Schema::hasColumn('staff_flat_tags', 'engagement_type')) {
            Schema::table('staff_flat_tags', function (Blueprint $table) {
                $table->dropColumn('engagement_type');
            });
        }
    }
}
