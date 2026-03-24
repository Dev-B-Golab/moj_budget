<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\RecurringTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RecurringTransactionController extends Controller
{
    public function index(Request $request)
    {
        $recurring = RecurringTransaction::where('user_id', $request->user()->id)
            ->with('category')
            ->orderByDesc('is_active')
            ->orderBy('next_run')
            ->get();

        return Inertia::render('Recurring/Index', [
            'recurringTransactions' => $recurring,
        ]);
    }

    public function create(Request $request)
    {
        $categories = Category::forUser($request->user()->id)->orderBy('name')->get();

        return Inertia::render('Recurring/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['expense', 'income'])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where(function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)->orWhere('is_default', true);
            })],
            'payment_method' => ['required', Rule::in(['cash', 'card'])],
            'description' => ['nullable', 'string', 'max:255'],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:28'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $dayOfMonth = $validated['day_of_month'];

        // Calculate next_run
        $nextRun = $startDate->copy()->day(min($dayOfMonth, $startDate->daysInMonth));
        if ($nextRun->lt($startDate)) {
            $nextRun->addMonthNoOverflow()->day(min($dayOfMonth, $nextRun->daysInMonth));
        }

        $validated['next_run'] = $nextRun->toDateString();
        $validated['is_active'] = true;

        $request->user()->recurringTransactions()->create($validated);

        return redirect()->route('recurring.index')
            ->with('success', 'Cykliczna transakcja została dodana.');
    }

    public function edit(Request $request, RecurringTransaction $recurring)
    {
        if ($recurring->user_id !== $request->user()->id) {
            abort(403);
        }

        $categories = Category::forUser($request->user()->id)->orderBy('name')->get();

        return Inertia::render('Recurring/Edit', [
            'recurring' => $recurring->load('category'),
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, RecurringTransaction $recurring)
    {
        if ($recurring->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(['expense', 'income'])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where(function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)->orWhere('is_default', true);
            })],
            'payment_method' => ['required', Rule::in(['cash', 'card'])],
            'description' => ['nullable', 'string', 'max:255'],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:28'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ]);

        // Recalculate next_run if day_of_month changed
        $now = Carbon::today();
        $dayOfMonth = $validated['day_of_month'];
        $nextRun = $now->copy()->day(min($dayOfMonth, $now->daysInMonth));
        if ($nextRun->lt($now)) {
            $nextRun->addMonthNoOverflow()->day(min($dayOfMonth, $nextRun->daysInMonth));
        }

        // If end_date passed, deactivate
        if (isset($validated['end_date']) && Carbon::parse($validated['end_date'])->lt($now)) {
            $validated['is_active'] = false;
        }

        $validated['next_run'] = $nextRun->toDateString();

        $recurring->update($validated);

        return redirect()->route('recurring.index')
            ->with('success', 'Cykliczna transakcja została zaktualizowana.');
    }

    public function destroy(Request $request, RecurringTransaction $recurring)
    {
        if ($recurring->user_id !== $request->user()->id) {
            abort(403);
        }

        $recurring->delete();

        return redirect()->route('recurring.index')
            ->with('success', 'Cykliczna transakcja została usunięta.');
    }

    public function toggle(Request $request, RecurringTransaction $recurring)
    {
        if ($recurring->user_id !== $request->user()->id) {
            abort(403);
        }

        $recurring->update(['is_active' => !$recurring->is_active]);

        return redirect()->route('recurring.index')
            ->with('success', $recurring->is_active ? 'Aktywowano.' : 'Wstrzymano.');
    }
}
