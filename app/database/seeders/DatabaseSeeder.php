<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Default categories (shared, no user_id)
        $defaultExpenseCategories = [
            ['name' => 'Jedzenie', 'type' => 'expense', 'icon' => '🛒', 'color' => '#ef4444', 'is_default' => true],
            ['name' => 'Transport', 'type' => 'expense', 'icon' => '🚗', 'color' => '#f97316', 'is_default' => true],
            ['name' => 'Mieszkanie', 'type' => 'expense', 'icon' => '🏠', 'color' => '#8b5cf6', 'is_default' => true],
            ['name' => 'Rozrywka', 'type' => 'expense', 'icon' => '🎮', 'color' => '#06b6d4', 'is_default' => true],
            ['name' => 'Zdrowie', 'type' => 'expense', 'icon' => '💊', 'color' => '#10b981', 'is_default' => true],
            ['name' => 'Ubrania', 'type' => 'expense', 'icon' => '👕', 'color' => '#ec4899', 'is_default' => true],
            ['name' => 'Edukacja', 'type' => 'expense', 'icon' => '📚', 'color' => '#3b82f6', 'is_default' => true],
            ['name' => 'Rachunki', 'type' => 'expense', 'icon' => '💡', 'color' => '#f59e0b', 'is_default' => true],
            ['name' => 'Restauracje', 'type' => 'expense', 'icon' => '🍔', 'color' => '#d946ef', 'is_default' => true],
            ['name' => 'Inne wydatki', 'type' => 'expense', 'icon' => '📦', 'color' => '#6366f1', 'is_default' => true],
            ['name' => 'Transfer', 'type' => 'expense', 'icon' => '🔄', 'color' => '#6b7280', 'is_default' => true],
        ];

        $defaultIncomeCategories = [
            ['name' => 'Wynagrodzenie', 'type' => 'income', 'icon' => '💰', 'color' => '#22c55e', 'is_default' => true],
            ['name' => 'Freelance', 'type' => 'income', 'icon' => '💼', 'color' => '#0ea5e9', 'is_default' => true],
            ['name' => 'Inwestycje', 'type' => 'income', 'icon' => '📈', 'color' => '#14b8a6', 'is_default' => true],
            ['name' => 'Inne przychody', 'type' => 'income', 'icon' => '💸', 'color' => '#84cc16', 'is_default' => true],
            ['name' => 'Transfer', 'type' => 'income', 'icon' => '🔄', 'color' => '#6b7280', 'is_default' => true],
        ];

        $categories = [];
        foreach (array_merge($defaultExpenseCategories, $defaultIncomeCategories) as $cat) {
            $categories[$cat['name']] = Category::create($cat);
        }

        // Demo user
        $demo = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => bcrypt('demo1234'),
        ]);

        // Generate 6 months of realistic transactions
        $now = Carbon::now();

        $expenseTemplates = [
            ['cat' => 'Jedzenie', 'descs' => ['Biedronka', 'Lidl', 'Żabka', 'Kaufland', 'Auchan'], 'min' => 15, 'max' => 250, 'freq' => 8],
            ['cat' => 'Transport', 'descs' => ['Paliwo', 'Uber', 'Bilet MPK', 'Parking', 'Serwis auta'], 'min' => 5, 'max' => 200, 'freq' => 4],
            ['cat' => 'Mieszkanie', 'descs' => ['Czynsz', 'Ubezpieczenie'], 'min' => 800, 'max' => 1500, 'freq' => 1],
            ['cat' => 'Rozrywka', 'descs' => ['Netflix', 'Spotify', 'Kino', 'Koncert', 'Gra PS5'], 'min' => 15, 'max' => 120, 'freq' => 3],
            ['cat' => 'Zdrowie', 'descs' => ['Apteka', 'Lekarz', 'Dentysta', 'Siłownia'], 'min' => 30, 'max' => 300, 'freq' => 2],
            ['cat' => 'Ubrania', 'descs' => ['Zara', 'H&M', 'Reserved', 'CCC'], 'min' => 50, 'max' => 300, 'freq' => 1],
            ['cat' => 'Edukacja', 'descs' => ['Kurs online', 'Książka', 'Udemy'], 'min' => 30, 'max' => 150, 'freq' => 1],
            ['cat' => 'Rachunki', 'descs' => ['Prąd', 'Gaz', 'Internet', 'Telefon', 'Woda'], 'min' => 50, 'max' => 300, 'freq' => 3],
            ['cat' => 'Restauracje', 'descs' => ['Pizza', 'Sushi', 'McDonald\'s', 'KFC', 'Obiad w mieście'], 'min' => 20, 'max' => 120, 'freq' => 4],
            ['cat' => 'Inne wydatki', 'descs' => ['Prezent', 'Chemia domowa', 'Narzędzia'], 'min' => 10, 'max' => 100, 'freq' => 2],
        ];

        $incomeTemplates = [
            ['cat' => 'Wynagrodzenie', 'descs' => ['Pensja'], 'min' => 5000, 'max' => 6500, 'freq' => 1],
            ['cat' => 'Freelance', 'descs' => ['Projekt web', 'Konsultacja', 'Logo design'], 'min' => 500, 'max' => 3000, 'freq' => 1],
            ['cat' => 'Inne przychody', 'descs' => ['Zwrot podatku', 'Sprzedaż OLX'], 'min' => 50, 'max' => 500, 'freq' => 0],
        ];

        for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
            $monthStart = $now->copy()->subMonths($monthOffset)->startOfMonth();
            $monthEnd = $monthOffset === 0 ? $now->copy() : $now->copy()->subMonths($monthOffset)->endOfMonth();
            $daysInRange = $monthStart->diffInDays($monthEnd);

            // Expenses
            foreach ($expenseTemplates as $template) {
                $count = max(1, $template['freq'] + rand(-1, 1));
                for ($i = 0; $i < $count; $i++) {
                    $date = $monthStart->copy()->addDays(rand(0, max(0, $daysInRange)));
                    if ($date->gt($now)) continue;

                    Transaction::create([
                        'user_id' => $demo->id,
                        'category_id' => $categories[$template['cat']]->id,
                        'type' => 'expense',
                        'amount' => round(rand($template['min'] * 100, $template['max'] * 100) / 100, 2),
                        'payment_method' => rand(0, 1) ? 'card' : 'cash',
                        'description' => $template['descs'][array_rand($template['descs'])],
                        'date' => $date->format('Y-m-d'),
                    ]);
                }
            }

            // Income
            foreach ($incomeTemplates as $template) {
                if ($template['freq'] === 0 && rand(0, 2) !== 0) continue;
                $count = max(1, $template['freq']);
                for ($i = 0; $i < $count; $i++) {
                    $date = $template['cat'] === 'Wynagrodzenie'
                        ? $monthStart->copy()->addDays(9)
                        : $monthStart->copy()->addDays(rand(0, max(0, $daysInRange)));
                    if ($date->gt($now)) continue;

                    Transaction::create([
                        'user_id' => $demo->id,
                        'category_id' => $categories[$template['cat']]->id,
                        'type' => 'income',
                        'amount' => round(rand($template['min'] * 100, $template['max'] * 100) / 100, 2),
                        'payment_method' => 'card',
                        'description' => $template['descs'][array_rand($template['descs'])],
                        'date' => $date->format('Y-m-d'),
                    ]);
                }
            }
        }
    }
}
