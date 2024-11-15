<?php

namespace Database\Factories;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class MessageFactory.
 *
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unique_id' => rand(10000000, 99999999),
            'user_id' => User::factory(),
            'chat_id' => Chat::factory(),
            'type' => 'text',
            'text' => fake()->sentence(),
        ];
    }
}
