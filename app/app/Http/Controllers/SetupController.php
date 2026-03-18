<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\RecurringTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SetupController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->setup_completed_at) {
            return redirect()->route('dashboard');
        }

        $categories = Category::forUser($user->id)
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        return Inertia::render('Setup/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'card_balance' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'cash_balance' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'recurring' => ['array', 'max:20'],
            'recurring.*.category_id' => ['required', 'integer', 'exists:categories,id'],
            'recurring.*.amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'recurring.*.payment_method' => ['required', 'in:card,cash'],
            'recurring.*.description' => ['nullable', 'string', 'max:255'],
            'recurring.*.day_of_month' => ['required', 'integer', 'min:1', 'max:28'],
        ]);

        $user->update([
            'initial_card_balance' => $validated['card_balance'],
            'initial_cash_balance' => $validated['cash_balance'],
            'setup_completed_at' => now(),
        ]);

        foreach ($validated['recurring'] as $item) {
            $today = Carbon::today();
            $nextRun = $today->copy()->day(min($item['day_of_month'], 28));
            if ($nextRun->lte($today)) {
                $nextRun->addMonth();
            }

            RecurringTransaction::create([
                'user_id' => $user->id,
                'category_id' => $item['category_id'],
                'type' => 'expense',
                'amount' => $item['amount'],
                'payment_method' => $item['payment_method'],
                'description' => $item['description'] ?? '',
                'day_of_month' => $item['day_of_month'],
                'start_date' => $today,
                'next_run' => $nextRun,
                'is_active' => true,
            ]);
        }

        return redirect()->route('dashboard');
    }

    public function skip(Request $request)
    {
        $request->user()->update([
            'setup_completed_at' => now(),
        ]);

        return redirect()->route('dashboard');
    }
}
