<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        // Create required default Transfer categories
        Category::factory()->create(['name' => 'Transfer', 'type' => 'expense', 'is_default' => true, 'icon' => '🔄']);
        Category::factory()->create(['name' => 'Transfer', 'type' => 'income', 'is_default' => true, 'icon' => '🔄']);
    }

    public function test_create_shows_transfer_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('transfer.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Transfer/Create'));
    }

    public function test_store_creates_two_paired_transactions(): void
    {
        $data = [
            'direction' => 'card_to_cash',
            'amount' => 500.00,
            'description' => 'Bankomat',
            'date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->user)->post(route('transfer.store'), $data);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('transactions', 2);

        $expense = Transaction::where('user_id', $this->user->id)->where('type', 'expense')->first();
        $income = Transaction::where('user_id', $this->user->id)->where('type', 'income')->first();

        $this->assertNotNull($expense);
        $this->assertNotNull($income);
        $this->assertEquals('500.00', $expense->amount);
        $this->assertEquals('500.00', $income->amount);
        $this->assertEquals('card', $expense->payment_method);
        $this->assertEquals('cash', $income->payment_method);
    }

    public function test_cash_to_card_direction(): void
    {
        $data = [
            'direction' => 'cash_to_card',
            'amount' => 200.00,
            'date' => now()->toDateString(),
        ];

        $this->actingAs($this->user)->post(route('transfer.store'), $data);

        $expense = Transaction::where('type', 'expense')->first();
        $income = Transaction::where('type', 'income')->first();

        $this->assertEquals('cash', $expense->payment_method);
        $this->assertEquals('card', $income->payment_method);
    }

    public function test_validates_direction(): void
    {
        $data = [
            'direction' => 'invalid',
            'amount' => 100,
            'date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->user)->post(route('transfer.store'), $data);
        $response->assertSessionHasErrors('direction');
    }

    public function test_validates_amount(): void
    {
        $data = [
            'direction' => 'card_to_cash',
            'amount' => 0,
            'date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->user)->post(route('transfer.store'), $data);
        $response->assertSessionHasErrors('amount');
    }
}
