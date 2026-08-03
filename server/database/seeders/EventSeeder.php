<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $organizer = User::first() ?? User::factory()->create();

        Event::factory()
            ->count(15)
            ->create(['created_by' => $organizer->id]);
    }
}
