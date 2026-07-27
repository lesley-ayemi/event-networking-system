<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
            $table->text('bio')->nullable()->after('password');
            $table->string('job_title')->nullable()->after('bio');
            $table->string('industry')->nullable()->after('job_title');
            $table->string('profile_image')->nullable()->after('industry');
            $table->json('interaction_preferences')->nullable()->after('profile_image');
            $table->json('quiz_answers')->nullable()->after('interaction_preferences');
            $table->json('compatibility_profile')->nullable()->after('quiz_answers');
            $table->boolean('onboarding_completed')->default(false)->after('compatibility_profile');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'bio',
                'job_title',
                'industry',
                'profile_image',
                'interaction_preferences',
                'quiz_answers',
                'compatibility_profile',
                'onboarding_completed',
            ]);
            $table->string('name')->after('id');
        });
    }
};
