<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('borrower_id')->constrained()->cascadeOnDelete();
            $table->string('loan_number', 50)->unique();
            $table->string('loan_type', 50)->default('personal');
            $table->decimal('loan_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('loan_tenure_months');
            $table->date('emi_start_date');
            $table->decimal('processing_fee', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('interest_type', 20)->default('reducing'); // flat, reducing
            $table->string('status', 20)->default('pending'); // pending, approved, active, completed, defaulted, rejected
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index('tenant_id');
            $table->index('borrower_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
