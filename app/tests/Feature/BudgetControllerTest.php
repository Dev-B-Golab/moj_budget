<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->expenseCategory = Category::factory()->expense()->create(['name' => 'Jedzenie']);
    }

    public function test_index_shows_budgets(): void
    {
        Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 500,
        ]);

        $response = $this->actingAs($this->user)->get(route('budgets.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Budgets/Index')
                ->has('budgets', 1)
                ->has('availableCategories')
                ->has('currentMonth')
        );
    }

    public function test_store_creates_budget(): void
    {
        $response = $this->actingAs($this->user)->post(route('budgets.store'), [
            'category_id' => $this->expenseCategory->id,
            'amount' => 1000,
        ]);

        $response->assertRedirect(route('budgets.index'));
        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => '1000.00',
        ]);
    }

    public function test_update_modifies_budget_amount(): void
    {
        $budget = Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 500,
        ]);

        $response = $this->actingAs($this->user)->put(route('budgets.update', $budget), [
            'amount' => 750,
        ]);

        $response->assertRedirect(route('budgets.index'));
        $budget->refresh();
        $this->assertEquals('750.00', $budget->amount);
    }

    public function test_destroy_removes_budget(): void
    {
        $budget = Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 500,
        ]);

        $response = $this->actingAs($this->user)->delete(route('budgets.destroy', $budget));

        $response->assertRedirect(route('budgets.index'));
        $this->assertDatabaseCount('budgets', 0);
    }

    public function test_cannot_modify_other_users_budget(): void
    {
        $otherUser = User::factory()->create();
        $budget = Budget::create([
            'user_id' => $otherUser->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 500,
        ]);

        $response = $this->actingAs($this->user)->put(route('budgets.update', $budget), ['amount' => 999]);
        $response->assertForbidden();

        $response = $this->actingAs($this->user)->delete(route('budgets.destroy', $budget));
        $response->assertForbidden();
    }

    public function test_available_categories_excludes_budgeted_ones(): void
    {
        $category2 = Category::factory()->expense()->create(['name' => 'Transport']);

        Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 500,
        ]);

        $response = $this->actingAs($this->user)->get(route('budgets.index'));

        $response->assertInertia(fn ($page) =>
            $page->has('availableCategories', 1)
                ->where('availableCategories.0.name', 'Transport')
        );
    }
}
