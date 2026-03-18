<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        $monthlyStats = Transaction::where('user_id', $user->id)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->selectRaw("
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expenses
            ")
            ->first();

        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with('category')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $expensesByCategory = Transaction::where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereMonth('transactions.date', $currentMonth)
            ->whereYear('transactions.date', $currentYear)
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.name', 'categories.color', 'categories.icon')
            ->selectRaw('SUM(transactions.amount) as total')
            ->groupBy('categories.id', 'categories.name', 'categories.color', 'categories.icon')
            ->orderByDesc('total')
            ->get();

        $monthlyTrend = Transaction::where('user_id', $user->id)
            ->where('date', '>=', $now->copy()->subMonths(5)->startOfMonth())
            ->selectRaw("
                YEAR(date) as year,
                MONTH(date) as month,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses
            ")
            ->groupByRaw('YEAR(date), MONTH(date)')
            ->orderByRaw('YEAR(date), MONTH(date)')
            ->get();

        // Balance by payment method (all-time)
        $balanceByMethod = Transaction::where('user_id', $user->id)
            ->selectRaw("
                payment_method,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) -
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as balance
            ")
            ->groupBy('payment_method')
            ->pluck('balance', 'payment_method');

        $cashBalance = (float) ($balanceByMethod['cash'] ?? 0) + (float) $user->initial_cash_balance;
        $cardBalance = (float) ($balanceByMethod['card'] ?? 0) + (float) $user->initial_card_balance;

        return Inertia::render('Dashboard', [
            'monthlyStats' => [
                'income' => (float) ($monthlyStats->total_income ?? 0),
                'expenses' => (float) ($monthlyStats->total_expenses ?? 0),
                'balance' => (float) (($monthlyStats->total_income ?? 0) - ($monthlyStats->total_expenses ?? 0)),
            ],
            'recentTransactions' => $recentTransactions,
            'expensesByCategory' => $expensesByCategory,
            'monthlyTrend' => $monthlyTrend,
            'currentMonth' => $now->translatedFormat('F Y'),
            'cashBalance' => $cashBalance,
            'cardBalance' => $cardBalance,
            'totalBalance' => $cashBalance + $cardBalance,
        ]);
    }
}
