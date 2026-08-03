<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MVP conversations are always direct (1:1) — small-group chats are
        // an explicitly later feature (spec section 19), so there's no
        // "type" column yet to avoid designing for a shape we don't use.
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
