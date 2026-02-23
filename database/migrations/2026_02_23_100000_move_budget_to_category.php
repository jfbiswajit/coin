<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->decimal('monthly_budget', 12, 2)->nullable()->after('icon');
        });

        DB::statement('
            UPDATE categories c
            JOIN (
                SELECT category_id, amount
                FROM budgets b1
                WHERE (b1.year, b1.month) = (
                    SELECT b2.year, b2.month
                    FROM budgets b2
                    WHERE b2.category_id = b1.category_id
                    ORDER BY b2.year DESC, b2.month DESC
                    LIMIT 1
                )
            ) latest ON c.id = latest.category_id
            SET c.monthly_budget = latest.amount
        ');

        Schema::dropIfExists('budgets');
    }

    public function down(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('month');
            $table->smallInteger('year');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->unique(['user_id', 'category_id', 'month', 'year']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('monthly_budget');
        });
    }
};
