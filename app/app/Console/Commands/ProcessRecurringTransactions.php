<?php

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessRecurringTransactions extends Command
{
    protected $signature = 'recurring:process';
    protected $description = 'Process recurring transactions that are due today';

    public function handle(): int
    {
        $today = Carbon::today();

        $due = RecurringTransaction::where('is_active', true)
            ->where('next_run', '<=', $today)
            ->get();

        $count = 0;

        foreach ($due as $recurring) {
            // Check if end_date has passed
            if ($recurring->end_date && $recurring->end_date->lt($today)) {
                $recurring->update(['is_active' => false]);
                continue;
            }

            // Create the transaction
            Transaction::create([
                'user_id' => $recurring->user_id,
                'category_id' => $recurring->category_id,
                'type' => $recurring->type,
                'amount' => $recurring->amount,
                'payment_method' => $recurring->payment_method,
                'description' => $recurring->description,
                'date' => $today->toDateString(),
            ]);

            // Calculate next run
            $nextRun = $today->copy()->addMonthNoOverflow();
            $day = min($recurring->day_of_month, $nextRun->daysInMonth);
            $nextRun->day($day);

            // If next_run is past end_date, deactivate
            if ($recurring->end_date && $nextRun->gt($recurring->end_date)) {
                $recurring->update([
                    'is_active' => false,
                    'next_run' => null,
                ]);
            } else {
                $recurring->update([
                    'next_run' => $nextRun->toDateString(),
                ]);
            }

            $count++;
        }

        $this->info("Processed {$count} recurring transaction(s).");

        return self::SUCCESS;
    }
}
