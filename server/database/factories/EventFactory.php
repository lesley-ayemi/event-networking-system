<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $isVirtual = $this->faker->boolean();
        $isFree = $this->faker->boolean(70);
        $startsAt = $this->faker->dateTimeBetween('+1 days', '+60 days');

        return [
            'name' => $this->faker->catchPhrase(),
            'description' => $this->faker->paragraph(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+2 hours'),
            'location' => $isVirtual ? null : $this->faker->city(),
            'is_virtual' => $isVirtual,
            'industry' => $this->faker->randomElement([
                'Technology', 'Design', 'Finance', 'Healthcare', 'Marketing', 'Education',
            ]),
            'one_to_one_available' => $this->faker->boolean(),
            'small_group_available' => $this->faker->boolean(),
            'is_free' => $isFree,
            'price' => $isFree ? null : $this->faker->randomFloat(2, 10, 200),
            'accessibility_options' => $this->faker->randomElements(
                ['wheelchair_accessible', 'asl_interpretation', 'quiet_room', 'captioning'],
                $this->faker->numberBetween(0, 2)
            ),
            'capacity' => $this->faker->optional(0.7)->numberBetween(10, 200),
            'created_by' => null,
        ];
    }
}
