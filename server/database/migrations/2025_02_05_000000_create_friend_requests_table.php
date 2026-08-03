<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friend_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            // Only 'pending' and 'accepted' are ever stored. Declining deletes
            // the row entirely rather than recording a 'declined' status, so
            // there is never a reason (or even a record) to leak back to the
            // sender — see spec section 18's "do not reveal why" rule.
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['sender_id', 'recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friend_requests');
    }
};
