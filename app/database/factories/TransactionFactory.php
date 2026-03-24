<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'type' => 'expense',
            'amount' => fake()->randomFloat(2, 1, 5000),
            'payment_method' => fake()->randomElement(['cash', 'card']),
            'description' => fake()->sentence(3),
            'date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
        ];
    }

    public function expense(): static
    {
        return $this->state(['type' => 'expense']);
    }

    public function income(): static
    {
        return $this->state(['type' => 'income']);
    }
}
