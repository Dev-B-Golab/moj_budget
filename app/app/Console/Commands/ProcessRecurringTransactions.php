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
            ->whereNotNull('next_run')
            ->where('next_run', '<=', $today)
            ->get();

        $count = 0;

        foreach ($due as $recurring) {
            // Check if end_date has passed
            if ($recurring->end_date && $recurring->end_date->lt($today)) {
                $recurring->update(['is_active' => false]);
                continue;
            }

            // Process all missed runs (catch-up)
            $runDate = $recurring->next_run->copy();

            while ($runDate->lte($today)) {
                // Stop if past end_date
                if ($recurring->end_date && $runDate->gt($recurring->end_date)) {
                    $recurring->update(['is_active' => false, 'next_run' => null]);
                    break;
                }

                // Create the transaction with the SCHEDULED date
                Transaction::create([
                    'user_id' => $recurring->user_id,
                    'category_id' => $recurring->category_id,
                    'type' => $recurring->type,
                    'amount' => $recurring->amount,
                    'payment_method' => $recurring->payment_method,
                    'description' => $recurring->description,
                    'date' => $runDate->toDateString(),
                ]);

                $count++;

                // Calculate next run from current run date
                $nextRun = $runDate->copy()->addMonthNoOverflow();
                $day = min($recurring->day_of_month, $nextRun->daysInMonth);
                $nextRun->day($day);

                // If next_run is past end_date, deactivate
                if ($recurring->end_date && $nextRun->gt($recurring->end_date)) {
                    $recurring->update(['is_active' => false, 'next_run' => null]);
                    break;
                }

                $recurring->update(['next_run' => $nextRun->toDateString()]);
                $runDate = $nextRun->copy();
            }
        }

        $this->info("Processed {$count} recurring transaction(s).");

        return self::SUCCESS;
    }
}
