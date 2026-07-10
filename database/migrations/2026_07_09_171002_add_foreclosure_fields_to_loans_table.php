<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->decimal('foreclosure_charges', 15, 2)->nullable()->after('processing_fee');
            $table->timestamp('foreclosed_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->dropColumn(['foreclosure_charges', 'foreclosed_at']);
        });
    }
};
