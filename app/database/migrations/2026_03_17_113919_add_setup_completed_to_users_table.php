<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('setup_completed_at')->nullable()->after('remember_token');
            $table->decimal('initial_card_balance', 10, 2)->default(0)->after('setup_completed_at');
            $table->decimal('initial_cash_balance', 10, 2)->default(0)->after('initial_card_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['setup_completed_at', 'initial_card_balance', 'initial_cash_balance']);
        });
    }
};
