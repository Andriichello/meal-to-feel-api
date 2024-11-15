<?php

namespace Database\Factories;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Class ChatFactory.
 *
 * @extends Factory<Chat>
 */
class ChatFactory extends Factory
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
            'username' => Str::random(8),
            'type' => 'private',
        ];
    }
}
