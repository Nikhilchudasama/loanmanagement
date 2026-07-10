<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('emi_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('borrower_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_id', 100)->unique();
            $table->string('gateway', 50); // stripe, razorpay
            $table->string('gateway_payment_id', 255)->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending'); // pending, completed, failed, refunded
            $table->string('payment_type', 20)->default('full'); // full, partial
            $table->json('gateway_response')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('loan_id');
            $table->index('borrower_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
