<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('availability_status_updated_at')->nullable()->after('availability_status');
        });

        // Backfill existing rows so staleness is measured from a real
        // reference point rather than treating every pre-existing account
        // as having an unknown (and therefore maximally stale) status.
        DB::table('users')->whereNull('availability_status_updated_at')->update([
            'availability_status_updated_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('availability_status_updated_at');
        });
    }
};
