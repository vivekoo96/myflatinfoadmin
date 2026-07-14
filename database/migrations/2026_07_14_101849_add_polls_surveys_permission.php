<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only insert if it doesn't already exist
        $exists = DB::table('permissions')
            ->where('guard', 'custom')
            ->where('slug', 'custom.polls')
            ->exists();

        if (!$exists) {
            DB::table('permissions')->insert([
                'guard'      => 'custom',
                'group'      => 'Polls & Surveys',
                'name'       => 'Polls & Surveys',
                'slug'       => 'custom.polls',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('guard', 'custom')
            ->where('slug', 'custom.polls')
            ->delete();
    }
};
