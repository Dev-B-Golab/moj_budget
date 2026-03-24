<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $expenseCategory;
    private Category $incomeCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->expenseCategory = Category::factory()->expense()->create(['name' => 'Rachunki']);
        $this->incomeCategory = Category::factory()->income()->create(['name' => 'Pensja']);
    }

    public function test_index_shows_recurring_transactions(): void
    {
        RecurringTransaction::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('recurring.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Recurring/Index')
                ->has('recurringTransactions', 2)
        );
    }

    public function test_store_creates_recurring_transaction(): void
    {
        $data = [
            'type' => 'expense',
            'amount' => 350.00,
            'category_id' => $this->expenseCategory->id,
            'payment_method' => 'card',
            'description' => 'Czynsz',
            'day_of_month' => 5,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => '',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('recurring.store'), $data);

        $response->assertRedirect(route('recurring.index'));
        $this->assertDatabaseHas('recurring_transactions', [
            'user_id' => $this->user->id,
            'amount' => '350.00',
            'day_of_month' => 5,
            'is_active' => true,
        ]);
    }

    public function test_store_calculates_next_run_correctly(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10));

        $data = [
            'type' => 'expense',
            'amount' => 100,
            'category_id' => $this->expenseCategory->id,
            'payment_method' => 'card',
            'day_of_month' => 15,
            'start_date' => '2026-03-01',
        ];

        $this->actingAs($this->user)->post(route('recurring.store'), $data);

        // day_of_month (15) is after start_date day (1), so next_run = March 15
        $this->assertDatabaseHas('recurring_transactions', [
            'next_run' => '2026-03-15',
        ]);

        Carbon::setTestNow();
    }

    public function test_store_pushes_next_run_to_next_month_if_day_passed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 20));

        $data = [
            'type' => 'expense',
            'amount' => 100,
            'category_id' => $this->expenseCategory->id,
            'payment_method' => 'card',
            'day_of_month' => 5,
            'start_date' => '2026-03-20',
        ];

        $this->actingAs($this->user)->post(route('recurring.store'), $data);

        // day_of_month (5) is before start_date day (20), so next_run pushed to April 5
        $this->assertDatabaseHas('recurring_transactions', [
            'next_run' => '2026-04-05',
        ]);

        Carbon::setTestNow();
    }

    public function test_update_recalculates_next_run(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10));

        $recurring = RecurringTransaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'day_of_month' => 5,
            'next_run' => '2026-04-05',
        ]);

        $data = [
            'type' => 'expense',
            'amount' => 200,
            'category_id' => $this->expenseCategory->id,
            'payment_method' => 'card',
            'day_of_month' => 20,
            'start_date' => '2026-01-01',
            'end_date' => '',
            'is_active' => true,
        ];

        $this->actingAs($this->user)->put(route('recurring.update', $recurring), $data);

        $recurring->refresh();
        // day_of_month changed to 20, today is March 10, so next_run = March 20
        $this->assertEquals('2026-03-20', $recurring->next_run->toDateString());

        Carbon::setTestNow();
    }

    public function test_update_does_not_skip_current_month_if_same_day(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15));

        $recurring = RecurringTransaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'day_of_month' => 15,
            'next_run' => '2026-03-15',
        ]);

        $data = [
            'type' => 'expense',
            'amount' => 200,
            'category_id' => $this->expenseCategory->id,
            'payment_method' => 'card',
            'day_of_month' => 15,
            'start_date' => '2026-01-01',
            'end_date' => '',
            'is_active' => true,
        ];

        $this->actingAs($this->user)->put(route('recurring.update', $recurring), $data);

        $recurring->refresh();
        // day_of_month == today, should stay at March 15 (not skip to April)
        $this->assertEquals('2026-03-15', $recurring->next_run->toDateString());

        Carbon::setTestNow();
    }

    public function test_destroy_deletes_recurring_transaction(): void
    {
        $recurring = RecurringTransaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('recurring.destroy', $recurring));

        $response->assertRedirect(route('recurring.index'));
        $this->assertDatabaseCount('recurring_transactions', 0);
    }

    public function test_toggle_activates_and_deactivates(): void
    {
        $recurring = RecurringTransaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->user)->patch(route('recurring.toggle', $recurring));
        $recurring->refresh();
        $this->assertFalse($recurring->is_active);

        $this->actingAs($this->user)->patch(route('recurring.toggle', $recurring));
        $recurring->refresh();
        $this->assertTrue($recurring->is_active);
    }

    public function test_cannot_edit_other_users_recurring(): void
    {
        $otherUser = User::factory()->create();
        $recurring = RecurringTransaction::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $this->expenseCategory->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('recurring.edit', $recurring));
        $response->assertForbidden();

        $response = $this->actingAs($this->user)->put(route('recurring.update', $recurring), [
            'type' => 'expense',
            'amount' => 100,
            'category_id' => $this->expenseCategory->id,
            'payment_method' => 'card',
            'day_of_month' => 1,
            'start_date' => now()->toDateString(),
        ]);
        $response->assertForbidden();
    }

    public function test_store_validates_day_of_month_range(): void
    {
        $data = [
            'type' => 'expense',
            'amount' => 100,
            'category_id' => $this->expenseCategory->id,
            'payment_method' => 'card',
            'day_of_month' => 31,
            'start_date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->user)->post(route('recurring.store'), $data);
        $response->assertSessionHasErrors('day_of_month');
    }

    public function test_store_validates_end_date_after_start(): void
    {
        $data = [
            'type' => 'expense',
            'amount' => 100,
            'category_id' => $this->expenseCategory->id,
            'payment_method' => 'card',
            'day_of_month' => 15,
            'start_date' => '2026-06-01',
            'end_date' => '2026-05-01',
        ];

        $response = $this->actingAs($this->user)->post(route('recurring.store'), $data);
        $response->assertSessionHasErrors('end_date');
    }
}
