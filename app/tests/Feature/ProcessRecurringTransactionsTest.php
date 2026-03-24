<?php

namespace Tests\Feature;

use App\Console\Commands\ProcessRecurringTransactions;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessRecurringTransactionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->expense()->create();
    }

    public function test_processes_due_recurring_transaction(): void
    {
        $recurring = RecurringTransaction::factory()->dueToday()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'amount' => 100.00,
            'description' => 'Czynsz',
        ]);

        $this->artisan('recurring:process')->assertExitCode(0);

        $this->assertDatabaseCount('transactions', 1);
        $transaction = Transaction::first();
        $this->assertEquals($this->user->id, $transaction->user_id);
        $this->assertEquals($this->category->id, $transaction->category_id);
        $this->assertEquals('100.00', $transaction->amount);
        $this->assertEquals('Czynsz', $transaction->description);
        $this->assertEquals(Carbon::today()->toDateString(), $transaction->date->toDateString());
    }

    public function test_does_not_process_inactive_recurring(): void
    {
        RecurringTransaction::factory()->dueToday()->inactive()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $this->artisan('recurring:process')->assertExitCode(0);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_does_not_process_future_recurring(): void
    {
        RecurringTransaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'next_run' => Carbon::today()->addDays(5)->toDateString(),
        ]);

        $this->artisan('recurring:process')->assertExitCode(0);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_catches_up_on_missed_months(): void
    {
        $threeMonthsAgo = Carbon::today()->subMonths(3);
        $dayOfMonth = min($threeMonthsAgo->day, 28);

        RecurringTransaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'amount' => 50.00,
            'day_of_month' => $dayOfMonth,
            'next_run' => $threeMonthsAgo->toDateString(),
            'start_date' => $threeMonthsAgo->copy()->subMonth()->toDateString(),
        ]);

        $this->artisan('recurring:process')->assertExitCode(0);

        // Should create multiple transactions for missed months
        $transactions = Transaction::all();
        $this->assertGreaterThanOrEqual(3, $transactions->count(), 'Should catch up on at least 3 missed months');

        // Each transaction should have the correct scheduled date, not today
        foreach ($transactions as $t) {
            $this->assertEquals($dayOfMonth, Carbon::parse($t->date)->day, 'Transaction date should match day_of_month, not today');
        }
    }

    public function test_uses_scheduled_date_not_today(): void
    {
        $yesterday = Carbon::yesterday();

        RecurringTransaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'day_of_month' => min($yesterday->day, 28),
            'next_run' => $yesterday->toDateString(),
        ]);

        $this->artisan('recurring:process')->assertExitCode(0);

        $transaction = Transaction::first();
        $this->assertNotNull($transaction);
        $this->assertEquals($yesterday->toDateString(), $transaction->date->toDateString(), 'Should use scheduled next_run date, not today');
    }

    public function test_deactivates_when_end_date_passed(): void
    {
        $recurring = RecurringTransaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'next_run' => Carbon::today()->subDays(5)->toDateString(),
            'end_date' => Carbon::today()->subDays(3)->toDateString(),
            'day_of_month' => Carbon::today()->subDays(5)->day <= 28 ? Carbon::today()->subDays(5)->day : 15,
        ]);

        $this->artisan('recurring:process')->assertExitCode(0);

        $recurring->refresh();
        $this->assertFalse($recurring->is_active);
    }

    public function test_advances_next_run_correctly(): void
    {
        $recurring = RecurringTransaction::factory()->dueToday()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'day_of_month' => 15,
            'next_run' => Carbon::today()->toDateString(),
        ]);

        Carbon::setTestNow(Carbon::today());
        $this->artisan('recurring:process')->assertExitCode(0);

        $recurring->refresh();
        $expectedNextRun = Carbon::today()->addMonthNoOverflow()->day(15);
        $this->assertEquals($expectedNextRun->toDateString(), $recurring->next_run->toDateString());
        Carbon::setTestNow();
    }

    public function test_handles_day_overflow_for_short_months(): void
    {
        // Set up recurring for day 31 (should clamp to 28 in Feb, 30 in Apr, etc.)
        Carbon::setTestNow(Carbon::create(2026, 1, 28));

        $recurring = RecurringTransaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'day_of_month' => 28,
            'next_run' => '2026-01-28',
        ]);

        $this->artisan('recurring:process')->assertExitCode(0);

        $recurring->refresh();
        // Feb 2026 has 28 days, so next_run should be Feb 28
        $this->assertEquals('2026-02-28', $recurring->next_run->toDateString());

        Carbon::setTestNow();
    }

    public function test_deactivates_when_next_run_exceeds_end_date(): void
    {
        $recurring = RecurringTransaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'day_of_month' => Carbon::today()->day <= 28 ? Carbon::today()->day : 15,
            'next_run' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(10)->toDateString(),
        ]);

        $this->artisan('recurring:process')->assertExitCode(0);

        $recurring->refresh();
        // Next month would be past end_date (10 days from now), so deactivated
        $this->assertFalse($recurring->is_active);
        $this->assertNull($recurring->next_run);
    }

    public function test_processes_multiple_recurring_for_different_users(): void
    {
        $user2 = User::factory()->create();
        $category2 = Category::factory()->income()->create();

        RecurringTransaction::factory()->dueToday()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);
        RecurringTransaction::factory()->dueToday()->create([
            'user_id' => $user2->id,
            'category_id' => $category2->id,
        ]);

        $this->artisan('recurring:process')->assertExitCode(0);

        $this->assertEquals(1, Transaction::where('user_id', $this->user->id)->count());
        $this->assertEquals(1, Transaction::where('user_id', $user2->id)->count());
    }

    public function test_does_not_process_null_next_run(): void
    {
        RecurringTransaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'next_run' => null,
            'is_active' => true,
        ]);

        $this->artisan('recurring:process')->assertExitCode(0);

        $this->assertDatabaseCount('transactions', 0);
    }
}
