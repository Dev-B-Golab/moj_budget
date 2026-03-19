<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);
        $year = $validated['year'] ?? Carbon::now()->year;
        $month = $validated['month'] ?? Carbon::now()->month;

        $monthlyData = Transaction::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->selectRaw("
                MONTH(date) as month,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses
            ")
            ->groupByRaw('MONTH(date)')
            ->orderByRaw('MONTH(date)')
            ->get();

        $expensesByCategory = Transaction::where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereYear('transactions.date', $year)
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.name', 'categories.color', 'categories.icon')
            ->selectRaw('SUM(transactions.amount) as total')
            ->groupBy('categories.id', 'categories.name', 'categories.color', 'categories.icon')
            ->orderByDesc('total')
            ->get();

        $incomeByCategory = Transaction::where('transactions.user_id', $user->id)
            ->where('transactions.type', 'income')
            ->whereYear('transactions.date', $year)
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.name', 'categories.color', 'categories.icon')
            ->selectRaw('SUM(transactions.amount) as total')
            ->groupBy('categories.id', 'categories.name', 'categories.color', 'categories.icon')
            ->orderByDesc('total')
            ->get();

        $paymentMethodStats = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereYear('date', $year)
            ->selectRaw("
                payment_method,
                SUM(amount) as total,
                COUNT(*) as count
            ")
            ->groupBy('payment_method')
            ->get();

        $yearTotal = Transaction::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->selectRaw("
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expenses
            ")
            ->first();

        $availableYears = Transaction::where('user_id', $user->id)
            ->selectRaw('DISTINCT YEAR(date) as year')
            ->orderBy('year', 'desc')
            ->pluck('year');

        if ($availableYears->isEmpty()) {
            $availableYears = collect([Carbon::now()->year]);
        }

        // Daily breakdown for selected month
        $dailyData = Transaction::where('transactions.user_id', $user->id)
            ->whereYear('transactions.date', $year)
            ->whereMonth('transactions.date', $month)
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.name as category_name', 'categories.icon as category_icon', 'categories.color as category_color')
            ->selectRaw('DAY(transactions.date) as day, transactions.type, SUM(transactions.amount) as total')
            ->groupByRaw('DAY(transactions.date), transactions.type, categories.id, categories.name, categories.icon, categories.color')
            ->orderByRaw('DAY(transactions.date)')
            ->get();

        // Daily totals (sum per day)
        $dailyTotals = Transaction::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->selectRaw("
                DAY(date) as day,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses
            ")
            ->groupByRaw('DAY(date)')
            ->orderByRaw('DAY(date)')
            ->get();

        return Inertia::render('Statistics/Index', [
            'monthlyData' => $monthlyData,
            'expensesByCategory' => $expensesByCategory,
            'incomeByCategory' => $incomeByCategory,
            'paymentMethodStats' => $paymentMethodStats,
            'yearTotal' => [
                'income' => (float) ($yearTotal->total_income ?? 0),
                'expenses' => (float) ($yearTotal->total_expenses ?? 0),
                'balance' => (float) (($yearTotal->total_income ?? 0) - ($yearTotal->total_expenses ?? 0)),
            ],
            'selectedYear' => (int) $year,
            'selectedMonth' => (int) $month,
            'availableYears' => $availableYears,
            'dailyData' => $dailyData,
            'dailyTotals' => $dailyTotals,
        ]);
    }
}
