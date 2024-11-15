<?php

namespace Database\Factories;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Update;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class UpdateFactory.
 *
 * @extends Factory<Update>
 */
class UpdateFactory extends Factory
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
            'message_id' => Message::factory(),
            'type' => 'private',
            'metadata' => null,
        ];
    }
}
