<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

Route::post('/demo-login', function () {
    if (app()->environment('production') && !config('app.demo_enabled', false)) {
        abort(404);
    }
    $user = \App\Models\User::where('email', 'demo@example.com')->first();
    if (!$user) {
        $user = \App\Models\User::create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => bcrypt('demo1234'),
            'email_verified_at' => now(),
            'setup_completed_at' => now(),
        ]);
    }

    // Seed demo transactions if the user has none
    if ($user->transactions()->count() === 0) {
        $expenseCats = \App\Models\Category::where('is_default', true)->where('type', 'expense')->pluck('id', 'name');
        $incomeCats = \App\Models\Category::where('is_default', true)->where('type', 'income')->pluck('id', 'name');

        $today = now();
        $demoTransactions = [
            // Current month incomes
            ['type' => 'income', 'category_id' => $incomeCats['Wynagrodzenie'], 'amount' => 6500, 'payment_method' => 'card', 'description' => 'Pensja', 'date' => $today->copy()->startOfMonth()->addDays(4)],
            ['type' => 'income', 'category_id' => $incomeCats['Freelance'], 'amount' => 1200, 'payment_method' => 'card', 'description' => 'Projekt strony www', 'date' => $today->copy()->startOfMonth()->addDays(10)],
            // Current month expenses
            ['type' => 'expense', 'category_id' => $expenseCats['Mieszkanie'], 'amount' => 2200, 'payment_method' => 'card', 'description' => 'Czynsz', 'date' => $today->copy()->startOfMonth()->addDay()],
            ['type' => 'expense', 'category_id' => $expenseCats['Rachunki'], 'amount' => 350, 'payment_method' => 'card', 'description' => 'Prąd i gaz', 'date' => $today->copy()->startOfMonth()->addDays(3)],
            ['type' => 'expense', 'category_id' => $expenseCats['Jedzenie'], 'amount' => 185.50, 'payment_method' => 'card', 'description' => 'Zakupy Biedronka', 'date' => $today->copy()->startOfMonth()->addDays(5)],
            ['type' => 'expense', 'category_id' => $expenseCats['Jedzenie'], 'amount' => 92.30, 'payment_method' => 'cash', 'description' => 'Lidl', 'date' => $today->copy()->startOfMonth()->addDays(8)],
            ['type' => 'expense', 'category_id' => $expenseCats['Transport'], 'amount' => 150, 'payment_method' => 'card', 'description' => 'Paliwo', 'date' => $today->copy()->startOfMonth()->addDays(6)],
            ['type' => 'expense', 'category_id' => $expenseCats['Restauracje'], 'amount' => 78, 'payment_method' => 'card', 'description' => 'Obiad z rodziną', 'date' => $today->copy()->startOfMonth()->addDays(7)],
            ['type' => 'expense', 'category_id' => $expenseCats['Rozrywka'], 'amount' => 45, 'payment_method' => 'card', 'description' => 'Netflix + Spotify', 'date' => $today->copy()->startOfMonth()->addDays(2)],
            ['type' => 'expense', 'category_id' => $expenseCats['Zdrowie'], 'amount' => 120, 'payment_method' => 'cash', 'description' => 'Leki w aptece', 'date' => $today->copy()->startOfMonth()->addDays(9)],
            ['type' => 'expense', 'category_id' => $expenseCats['Ubrania'], 'amount' => 259, 'payment_method' => 'card', 'description' => 'Buty sportowe', 'date' => $today->copy()->startOfMonth()->addDays(11)],
            ['type' => 'expense', 'category_id' => $expenseCats['Jedzenie'], 'amount' => 67.80, 'payment_method' => 'cash', 'description' => 'Rynek', 'date' => $today->copy()->startOfMonth()->addDays(12)],
            // Previous month
            ['type' => 'income', 'category_id' => $incomeCats['Wynagrodzenie'], 'amount' => 6500, 'payment_method' => 'card', 'description' => 'Pensja', 'date' => $today->copy()->subMonth()->startOfMonth()->addDays(4)],
            ['type' => 'expense', 'category_id' => $expenseCats['Mieszkanie'], 'amount' => 2200, 'payment_method' => 'card', 'description' => 'Czynsz', 'date' => $today->copy()->subMonth()->startOfMonth()->addDay()],
            ['type' => 'expense', 'category_id' => $expenseCats['Jedzenie'], 'amount' => 520, 'payment_method' => 'card', 'description' => 'Zakupy spożywcze', 'date' => $today->copy()->subMonth()->startOfMonth()->addDays(7)],
            ['type' => 'expense', 'category_id' => $expenseCats['Transport'], 'amount' => 280, 'payment_method' => 'card', 'description' => 'Paliwo + przegląd', 'date' => $today->copy()->subMonth()->startOfMonth()->addDays(10)],
            ['type' => 'expense', 'category_id' => $expenseCats['Rachunki'], 'amount' => 380, 'payment_method' => 'card', 'description' => 'Rachunki', 'date' => $today->copy()->subMonth()->startOfMonth()->addDays(3)],
            ['type' => 'expense', 'category_id' => $expenseCats['Rozrywka'], 'amount' => 145, 'payment_method' => 'card', 'description' => 'Kino + gra', 'date' => $today->copy()->subMonth()->startOfMonth()->addDays(14)],
        ];

        foreach ($demoTransactions as $t) {
            $user->transactions()->create($t);
        }
    }

    Auth::login($user);
    request()->session()->regenerate();
    return redirect()->route('dashboard');
})->middleware(['guest', 'throttle:5,1'])->name('demo.login');

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Setup wizard (before setup.completed middleware)
    Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
    Route::middleware('demo.restrict')->group(function () {
        Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
        Route::post('/setup/skip', [SetupController::class, 'skip'])->name('setup.skip');
    });

    Route::middleware('setup.completed')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');

    // Read-only views (no demo restriction)
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/recurring', [RecurringTransactionController::class, 'index'])->name('recurring.index');
    Route::get('/recurring/create', [RecurringTransactionController::class, 'create'])->name('recurring.create');
    Route::get('/recurring/{recurring}/edit', [RecurringTransactionController::class, 'edit'])->name('recurring.edit');
    Route::get('/transfer', [TransferController::class, 'create'])->name('transfer.create');
    Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');

    // Write operations (demo restricted)
    Route::middleware('demo.restrict')->group(function () {
        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/recurring', [RecurringTransactionController::class, 'store'])->name('recurring.store');
        Route::put('/recurring/{recurring}', [RecurringTransactionController::class, 'update'])->name('recurring.update');
        Route::delete('/recurring/{recurring}', [RecurringTransactionController::class, 'destroy'])->name('recurring.destroy');
        Route::patch('/recurring/{recurring}/toggle', [RecurringTransactionController::class, 'toggle'])->name('recurring.toggle');
        Route::post('/transfer', [TransferController::class, 'store'])->name('transfer.store');
        Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::put('/budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
        Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');
    });
    }); // end setup.completed
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::middleware('demo.restrict')->group(function () {
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';
