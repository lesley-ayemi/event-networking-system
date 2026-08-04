<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Superseded by the generic, polymorphic reports table: reporting now targets
// the other user's account (with a taxonomy reason) instead of the whole
// conversation, and the same table also covers reported messages and events.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('conversation_reports');
    }

    public function down(): void
    {
        Schema::create('conversation_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }
};
