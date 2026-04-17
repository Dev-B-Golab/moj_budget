<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'forecast' => $this->buildForecast($user),
        ]);
    }

    private function buildForecast($user): array
    {
        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();

        // Find distinct months with transactions (excluding current month for averages)
        $monthCount = Transaction::where('user_id', $user->id)
            ->where('date', '<', $currentMonthStart)
            ->selectRaw('DISTINCT YEAR(date), MONTH(date)')
            ->get()
            ->count();

        if ($monthCount === 0) {
            return [
                'hasData' => false,
                'categories' => [],
                'summary' => null,
            ];
        }

        // Average monthly spending per expense category (excluding current month)
        $categoryAverages = Transaction::where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->where('transactions.date', '<', $currentMonthStart)
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.id', 'categories.name', 'categories.icon', 'categories.color')
            ->selectRaw('SUM(transactions.amount) as total')
            ->groupBy('categories.id', 'categories.name', 'categories.icon', 'categories.color')
            ->get()
            ->map(function ($cat) use ($monthCount) {
                $cat->monthly_avg = round((float) $cat->total / $monthCount, 2);
                return $cat;
            });

        // Current month spending per expense category
        $currentMonthByCategory = Transaction::where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereYear('transactions.date', $now->year)
            ->whereMonth('transactions.date', $now->month)
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.id')
            ->selectRaw('SUM(transactions.amount) as spent')
            ->groupBy('categories.id')
            ->pluck('spent', 'id');

        // Build per-category forecast
        $categoryForecasts = $categoryAverages->map(function ($cat) use ($currentMonthByCategory) {
            $spent = (float) ($currentMonthByCategory[$cat->id] ?? 0);
            $remaining = max(0, $cat->monthly_avg - $spent);
            $percentage = $cat->monthly_avg > 0 ? round(($spent / $cat->monthly_avg) * 100) : 0;

            return [
                'name' => $cat->name,
                'icon' => $cat->icon,
                'color' => $cat->color,
                'monthly_avg' => $cat->monthly_avg,
                'current_spent' => $spent,
                'remaining' => $remaining,
                'percentage' => $percentage,
            ];
        })->sortByDesc('monthly_avg')->values();

        // Overall monthly averages (excluding current month)
        $overallAvg = Transaction::where('user_id', $user->id)
            ->where('date', '<', $currentMonthStart)
            ->selectRaw("
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expenses
            ")
            ->first();

        $avgIncome = round((float) ($overallAvg->total_income ?? 0) / $monthCount, 2);
        $avgExpenses = round((float) ($overallAvg->total_expenses ?? 0) / $monthCount, 2);

        // Current month totals
        $currentMonth = Transaction::where('user_id', $user->id)
            ->whereYear('date', $now->year)
            ->whereMonth('date', $now->month)
            ->selectRaw("
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses
            ")
            ->first();

        $currentIncome = (float) ($currentMonth->income ?? 0);
        $currentExpenses = (float) ($currentMonth->expenses ?? 0);

        // Days progress in current month
        $daysInMonth = $now->daysInMonth;
        $daysPassed = $now->day;
        $daysRemaining = $daysInMonth - $daysPassed;

        // Projected end-of-month expenses (linear extrapolation)
        $projectedExpenses = $daysPassed > 0
            ? round(($currentExpenses / $daysPassed) * $daysInMonth, 2)
            : $avgExpenses;

        return [
            'hasData' => true,
            'categories' => $categoryForecasts,
            'summary' => [
                'avg_income' => $avgIncome,
                'avg_expenses' => $avgExpenses,
                'avg_balance' => round($avgIncome - $avgExpenses, 2),
                'current_income' => $currentIncome,
                'current_expenses' => $currentExpenses,
                'projected_expenses' => $projectedExpenses,
                'projected_balance' => round($currentIncome - $projectedExpenses, 2),
                'months_analyzed' => $monthCount,
                'days_passed' => $daysPassed,
                'days_in_month' => $daysInMonth,
                'days_remaining' => $daysRemaining,
            ],
        ];
    }
}
