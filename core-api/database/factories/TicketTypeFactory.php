<?php

namespace Database\Factories;

use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'remaining_inventory' => 50,
            // If you have an Event model, use: 'event_id' => \App\Models\Event::factory(),
            // For now, let's just use a hardcoded ID if you don't have an Event table yet:
            'event_id' => 1, 
        ];
    }
}
