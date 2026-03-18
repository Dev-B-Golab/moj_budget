<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TransferController extends Controller
{
    public function create()
    {
        return Inertia::render('Transfer/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'direction' => ['required', Rule::in(['card_to_cash', 'cash_to_card'])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'description' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $transferExpense = Category::where('name', 'Transfer')
            ->where('type', 'expense')
            ->where('is_default', true)
            ->firstOrFail();
        $transferIncome = Category::where('name', 'Transfer')
            ->where('type', 'income')
            ->where('is_default', true)
            ->firstOrFail();

        $isCardToCash = $validated['direction'] === 'card_to_cash';
        $desc = $validated['description'] ?: ($isCardToCash ? 'Wypłata z bankomatu' : 'Wpłata na konto');

        DB::transaction(function () use ($request, $validated, $transferExpense, $transferIncome, $isCardToCash, $desc) {
            $request->user()->transactions()->create([
                'type' => 'expense',
                'amount' => $validated['amount'],
                'category_id' => $transferExpense->id,
                'payment_method' => $isCardToCash ? 'card' : 'cash',
                'description' => $desc,
                'date' => $validated['date'],
            ]);

            $request->user()->transactions()->create([
                'type' => 'income',
                'amount' => $validated['amount'],
                'category_id' => $transferIncome->id,
                'payment_method' => $isCardToCash ? 'cash' : 'card',
                'description' => $desc,
                'date' => $validated['date'],
            ]);
        });

        return redirect()->route('dashboard')
            ->with('success', 'Transfer został zrealizowany.');
    }
}
