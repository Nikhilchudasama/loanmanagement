<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->decimal('paid_amount', 15, 2)->nullable()->after('amount');
            $table->decimal('remaining_balance', 15, 2)->nullable()->after('paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['paid_amount', 'remaining_balance']);
        });
    }
};
