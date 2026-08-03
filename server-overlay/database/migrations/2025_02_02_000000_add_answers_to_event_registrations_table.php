<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('interaction_mode')->nullable()->after('user_id');
            $table->boolean('open_to_matching')->default(true)->after('interaction_mode');
            $table->boolean('message_before_event')->default(false)->after('open_to_matching');
            $table->unsignedInteger('preferred_group_size')->nullable()->after('message_before_event');
            $table->string('attendance_format')->nullable()->after('preferred_group_size');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn([
                'interaction_mode',
                'open_to_matching',
                'message_before_event',
                'preferred_group_size',
                'attendance_format',
            ]);
        });
    }
};
