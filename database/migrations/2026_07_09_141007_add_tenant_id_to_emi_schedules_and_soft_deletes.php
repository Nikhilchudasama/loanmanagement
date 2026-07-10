<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emi_schedules', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index('tenant_id');
        });

        Schema::table('borrowers', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('emi_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropIndex(['tenant_id']);
        });

        Schema::table('borrowers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
