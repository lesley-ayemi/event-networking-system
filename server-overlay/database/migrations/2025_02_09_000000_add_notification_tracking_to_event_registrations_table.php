<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            // Null means "not yet processed for this event" — set once the
            // scheduled command has considered this registration, whether or
            // not comfort_settings actually resulted in an email going out,
            // so the same registration is never re-evaluated on a later run.
            $table->timestamp('reminder_sent_at')->nullable()->after('attendance_format');
            $table->timestamp('introduction_sent_at')->nullable()->after('reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn(['reminder_sent_at', 'introduction_sent_at']);
        });
    }
};
