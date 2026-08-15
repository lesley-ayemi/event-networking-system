<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo content for the hosted deployment.
 *
 * Deliberately builds rows by hand rather than using model factories: factories
 * depend on fakerphp/faker, which is a require-dev package and therefore absent
 * from a `composer install --no-dev` production image. EventSeeder throws there.
 * Hand-written rows also read better than faker's catchPhrase() output.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Event::query()->exists()) {
            return;
        }

        $organiser = User::firstOrCreate(
            ['email' => 'organiser@eventnetworking.demo'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Organiser',
                // Not meant to be signed into; registration is open for that.
                'password' => Str::random(40),
                'onboarding_completed' => true,
                'organiser_status' => 'approved',
                'job_title' => 'Community Lead',
                'industry' => 'Technology',
            ]
        );

        foreach ($this->events() as $event) {
            Event::create($event + ['created_by' => $organiser->id]);
        }
    }

    private function events(): array
    {
        return [
            [
                'name' => 'Accessible Design Meetup',
                'description' => "A short talks evening on designing for screen readers, low vision and cognitive load. Two 20-minute talks, then open floor. Quiet room available all evening.",
                'starts_at' => now()->addDays(6)->setTime(18, 30),
                'ends_at' => now()->addDays(6)->setTime(21, 0),
                'location' => 'Manchester',
                'is_virtual' => false,
                'industry' => 'Technology',
                'one_to_one_available' => true,
                'small_group_available' => true,
                'is_free' => true,
                'price' => null,
                'accessibility_options' => ['wheelchair_accessible', 'quiet_room', 'captioning'],
                'capacity' => 60,
            ],
            [
                'name' => 'Junior Developers Coffee Morning',
                'description' => "Low-key morning for people in their first couple of years. No talks, no badges, just small tables and a rough topic on each one.",
                'starts_at' => now()->addDays(9)->setTime(10, 0),
                'ends_at' => now()->addDays(9)->setTime(12, 0),
                'location' => 'Leeds',
                'is_virtual' => false,
                'industry' => 'Technology',
                'one_to_one_available' => true,
                'small_group_available' => true,
                'is_free' => true,
                'price' => null,
                'accessibility_options' => ['wheelchair_accessible', 'quiet_room'],
                'capacity' => 30,
            ],
            [
                'name' => 'Remote Product Design Roundtable',
                'description' => "Video roundtable on research practice in distributed teams. Cameras optional, chat participation is genuinely fine.",
                'starts_at' => now()->addDays(12)->setTime(16, 0),
                'ends_at' => now()->addDays(12)->setTime(17, 30),
                'location' => null,
                'is_virtual' => true,
                'industry' => 'Design',
                'one_to_one_available' => true,
                'small_group_available' => true,
                'is_free' => true,
                'price' => null,
                'accessibility_options' => ['captioning'],
                'capacity' => 100,
            ],
            [
                'name' => 'Healthcare Data Ethics Panel',
                'description' => "Panel and audience Q&A on consent and secondary use of patient data. BSL interpretation confirmed.",
                'starts_at' => now()->addDays(18)->setTime(17, 0),
                'ends_at' => now()->addDays(18)->setTime(19, 30),
                'location' => 'Birmingham',
                'is_virtual' => false,
                'industry' => 'Healthcare',
                'one_to_one_available' => false,
                'small_group_available' => true,
                'is_free' => false,
                'price' => 15.00,
                'accessibility_options' => ['wheelchair_accessible', 'asl_interpretation', 'captioning'],
                'capacity' => 120,
            ],
            [
                'name' => 'Fintech Founders Breakfast',
                'description' => "Early breakfast for people building in payments and lending. Structured introductions so nobody has to break into a circle.",
                'starts_at' => now()->addDays(21)->setTime(8, 30),
                'ends_at' => now()->addDays(21)->setTime(10, 0),
                'location' => 'London',
                'is_virtual' => false,
                'industry' => 'Finance',
                'one_to_one_available' => true,
                'small_group_available' => false,
                'is_free' => false,
                'price' => 25.00,
                'accessibility_options' => ['wheelchair_accessible'],
                'capacity' => 40,
            ],
            [
                'name' => 'Intro to Rust for Backend Engineers',
                'description' => "Hands-on virtual workshop. Bring a laptop with Rust installed; there is a setup guide sent the day before.",
                'starts_at' => now()->addDays(27)->setTime(13, 0),
                'ends_at' => now()->addDays(27)->setTime(16, 0),
                'location' => null,
                'is_virtual' => true,
                'industry' => 'Technology',
                'one_to_one_available' => false,
                'small_group_available' => true,
                'is_free' => true,
                'price' => null,
                'accessibility_options' => ['captioning'],
                'capacity' => 80,
            ],
            [
                'name' => 'Creative Industries Mixer',
                'description' => "Evening mixer for illustrators, writers and motion designers. Portfolio table if you want to leave work out rather than talk about it.",
                'starts_at' => now()->addDays(34)->setTime(19, 0),
                'ends_at' => now()->addDays(34)->setTime(22, 0),
                'location' => 'Bristol',
                'is_virtual' => false,
                'industry' => 'Creative',
                'one_to_one_available' => true,
                'small_group_available' => true,
                'is_free' => false,
                'price' => 8.50,
                'accessibility_options' => ['wheelchair_accessible', 'quiet_room'],
                'capacity' => 90,
            ],
            [
                'name' => 'Women in Engineering Leadership Lunch',
                'description' => "Seated lunch with a facilitated table discussion on moving from senior IC into leadership.",
                'starts_at' => now()->addDays(41)->setTime(12, 30),
                'ends_at' => now()->addDays(41)->setTime(14, 30),
                'location' => 'Edinburgh',
                'is_virtual' => false,
                'industry' => 'Technology',
                'one_to_one_available' => true,
                'small_group_available' => true,
                'is_free' => false,
                'price' => 18.00,
                'accessibility_options' => ['wheelchair_accessible', 'quiet_room', 'captioning'],
                'capacity' => 50,
            ],
        ];
    }
}
