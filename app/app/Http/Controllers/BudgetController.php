<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();

        $budgets = Budget::where('user_id', $user->id)
            ->with('category')
            ->get();

        // Current month spending per category
        $spending = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $budgetData = $budgets->map(function ($budget) use ($spending) {
            $spent = (float) ($spending->get($budget->category_id) ?? 0);
            return [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'category_name' => $budget->category->name,
                'category_icon' => $budget->category->icon,
                'category_color' => $budget->category->color,
                'limit' => (float) $budget->amount,
                'spent' => $spent,
                'percentage' => $budget->amount > 0 ? round(($spent / $budget->amount) * 100) : 0,
            ];
        });

        // Categories available for new budgets (expense only, not already budgeted)
        $budgetedCategoryIds = $budgets->pluck('category_id')->toArray();
        $availableCategories = Category::forUser($user->id)
            ->where('type', 'expense')
            ->whereNotIn('id', $budgetedCategoryIds)
            ->orderBy('name')
            ->get(['id', 'name', 'icon', 'color']);

        return Inertia::render('Budgets/Index', [
            'budgets' => $budgetData,
            'availableCategories' => $availableCategories,
            'currentMonth' => $now->translatedFormat('F Y'),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ]);

        Budget::updateOrCreate(
            ['user_id' => $user->id, 'category_id' => $validated['category_id']],
            ['amount' => $validated['amount']]
        );

        return redirect()->route('budgets.index');
    }

    public function update(Request $request, Budget $budget)
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ]);

        $budget->update(['amount' => $validated['amount']]);

        return redirect()->route('budgets.index');
    }

    public function destroy(Request $request, Budget $budget)
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403);
        }

        $budget->delete();

        return redirect()->route('budgets.index');
    }
}
