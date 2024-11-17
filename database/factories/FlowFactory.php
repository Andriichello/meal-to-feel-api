<?php

namespace Database\Factories;

use App\Models\Chat;
use App\Models\Flow;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class FlowFactory.
 *
 * @extends Factory<Flow>
 */
class FlowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_id' => Chat::factory(),
            'user_id' => User::factory(),
            'beg_id' => Message::factory(),
            'end_id' => Message::factory(),
            'command' => '/test',
            'status' => 'initiated',
        ];
    }
}
