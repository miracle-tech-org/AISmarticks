<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // Required for Schema::hasTable if used, though not strictly for this DB::table approach.

class DeduplicateDeviceTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Get all (user_id, token) pairs that have duplicates
        $duplicateGroups = DB::table('device_tokens')
            ->select('user_id', 'token')
            ->groupBy('user_id', 'token')
            ->havingRaw('COUNT(id) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            // For each group, find the max id (the one to keep, representing the latest entry)
            $maxId = DB::table('device_tokens')
                ->where('user_id', $group->user_id)
                ->where('token', $group->token)
                ->max('id');

            // Delete all other entries in this group (those with id < maxId)
            DB::table('device_tokens')
                ->where('user_id', $group->user_id)
                ->where('token', $group->token)
                ->where('id', '<>', $maxId)
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Reversing a deduplication is not practical as it involves data loss.
        // If this migration is rolled back, the unique index added in a subsequent migration
        // would likely fail if new duplicates were introduced after this ran.
        // No action taken.
    }
}
