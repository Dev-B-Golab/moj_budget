<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $expenseCategory;
    private Category $incomeCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->expenseCategory = Category::factory()->expense()->create(['name' => 'Jedzenie']);
        $this->incomeCategory = Category::factory()->income()->create(['name' => 'Wynagrodzenie']);
    }

    public function test_index_returns_transactions(): void
    {
        Transaction::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Transactions/Index')
                ->has('transactions.data', 3)
                ->has('categories')
                ->has('accountCreatedAt')
        );
    }

    public function test_index_does_not_show_other_users_transactions(): void
    {
        $otherUser = User::factory()->create();
        Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $this->expenseCategory->id,
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('transactions.index'));

        $response->assertInertia(fn ($page) =>
            $page->has('transactions.data', 1)
        );
    }

    public function test_store_creates_transaction(): void
    {
        $data = [
            'type' => 'expense',
            'amount' => 42.50,
            'category_id' => $this->expenseCategory->id,
            'payment_method' => 'card',
            'description' => 'Zakupy',
            'date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->user)->post(route('transactions.store'), $data);

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'amount' => '42.50',
            'description' => 'Zakupy',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('transactions.store'), []);

        $response->assertSessionHasErrors(['type', 'amount', 'category_id', 'payment_method', 'date']);
    }

    public function test_store_rejects_future_dates(): void
    {
        $data = [
            'type' => 'expense',
            'amount' => 100,
            'category_id' => $this->expenseCategory->id,
            'payment_method' => 'card',
            'date' => now()->addDay()->toDateString(),
        ];

        $response = $this->actingAs($this->user)->post(route('transactions.store'), $data);

        $response->assertSessionHasErrors('date');
    }

    public function test_store_rejects_other_users_category(): void
    {
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->forUser($otherUser)->create();

        $data = [
            'type' => 'expense',
            'amount' => 100,
            'category_id' => $otherCategory->id,
            'payment_method' => 'card',
            'date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->user)->post(route('transactions.store'), $data);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_update_modifies_transaction_without_deleting(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 50.00,
            'description' => 'Original',
        ]);

        $data = [
            'type' => 'expense',
            'amount' => 75.00,
            'category_id' => $this->expenseCategory->id,
            'payment_method' => 'card',
            'description' => 'Updated',
            'date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->user)->put(route('transactions.update', $transaction), $data);

        $response->assertRedirect(route('transactions.index'));

        // CRITICAL: transaction still exists and is updated, NOT deleted
        $this->assertDatabaseCount('transactions', 1);
        $transaction->refresh();
        $this->assertEquals('75.00', $transaction->amount);
        $this->assertEquals('Updated', $transaction->description);
    }

    public function test_update_does_not_affect_other_transactions(): void
    {
        $transaction1 = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'description' => 'Transaction 1',
        ]);
        $transaction2 = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'description' => 'Transaction 2',
        ]);

        $data = [
            'type' => 'expense',
            'amount' => 99.99,
            'category_id' => $this->expenseCategory->id,
            'payment_method' => 'card',
            'description' => 'Updated T1',
            'date' => now()->toDateString(),
        ];

        $this->actingAs($this->user)->put(route('transactions.update', $transaction1), $data);

        // Both still exist
        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseHas('transactions', ['id' => $transaction2->id, 'description' => 'Transaction 2']);
    }

    public function test_update_rejects_other_users_transaction(): void
    {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $this->expenseCategory->id,
        ]);

        $data = [
            'type' => 'expense',
            'amount' => 100,
            'category_id' => $this->expenseCategory->id,
            'payment_method' => 'card',
            'date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->user)->put(route('transactions.update', $transaction), $data);

        $response->assertForbidden();
    }

    public function test_destroy_deletes_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('transactions.destroy', $transaction));

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_destroy_rejects_other_users_transaction(): void
    {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $this->expenseCategory->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('transactions.destroy', $transaction));

        $response->assertForbidden();
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_index_filters_by_type(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
        ]);

        $response = $this->actingAs($this->user)->get(route('transactions.index', ['type' => 'expense']));

        $response->assertInertia(fn ($page) =>
            $page->has('transactions.data', 1)
        );
    }

    public function test_index_filters_by_date_range(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'date' => now()->subDays(10)->toDateString(),
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'date' => now()->subDays(60)->toDateString(),
        ]);

        $response = $this->actingAs($this->user)->get(route('transactions.index', [
            'date_from' => now()->subDays(30)->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertInertia(fn ($page) =>
            $page->has('transactions.data', 1)
        );
    }
}
