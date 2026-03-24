<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTransaction>
 */
class RecurringTransactionFactory extends Factory
{
    protected $model = RecurringTransaction::class;

    public function definition(): array
    {
        $dayOfMonth = fake()->numberBetween(1, 28);
        $startDate = Carbon::today()->subMonths(3);
        $nextRun = Carbon::today()->day(min($dayOfMonth, Carbon::today()->daysInMonth));
        if ($nextRun->lt(Carbon::today())) {
            $nextRun->addMonthNoOverflow()->day(min($dayOfMonth, $nextRun->daysInMonth));
        }

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'type' => 'expense',
            'amount' => fake()->randomFloat(2, 10, 2000),
            'payment_method' => fake()->randomElement(['cash', 'card']),
            'description' => fake()->sentence(3),
            'day_of_month' => $dayOfMonth,
            'start_date' => $startDate->toDateString(),
            'end_date' => null,
            'next_run' => $nextRun->toDateString(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function dueToday(): static
    {
        return $this->state([
            'next_run' => Carbon::today()->toDateString(),
            'day_of_month' => Carbon::today()->day,
        ]);
    }

    public function overdue(int $months = 1): static
    {
        $pastDate = Carbon::today()->subMonths($months);
        return $this->state([
            'next_run' => $pastDate->toDateString(),
            'day_of_month' => $pastDate->day <= 28 ? $pastDate->day : 15,
        ]);
    }
}
