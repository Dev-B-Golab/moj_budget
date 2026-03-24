<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->word(),
            'type' => 'expense',
            'icon' => '📦',
            'color' => '#6366f1',
            'is_default' => true,
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

    public function forUser(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
            'is_default' => false,
        ]);
    }
}
