<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE categories MODIFY COLUMN type ENUM('income', 'expense', 'saving', 'loan') NOT NULL");
            DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('income', 'expense', 'saving', 'loan') NOT NULL");
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->decimal('loan_amount', 12, 2)->nullable()->after('icon');
            $table->decimal('emi_amount', 12, 2)->nullable()->after('loan_amount');
            $table->decimal('monthly_amount', 12, 2)->nullable()->after('emi_amount');
            $table->decimal('target_amount', 12, 2)->nullable()->after('monthly_amount');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['loan_amount', 'emi_amount', 'monthly_amount', 'target_amount']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE categories MODIFY COLUMN type ENUM('income', 'expense') NOT NULL");
            DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('income', 'expense') NOT NULL");
        }
    }
};
