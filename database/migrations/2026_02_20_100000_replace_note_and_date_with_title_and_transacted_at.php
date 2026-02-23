<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('title')->nullable()->after('type');
            $table->date('transacted_at')->nullable()->after('title');
        });

        DB::table('transactions')->update([
            'title' => DB::raw('COALESCE(note, \'\')'),
            'transacted_at' => DB::raw('date'),
        ]);

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['note', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->date('date')->nullable()->after('type');
            $table->string('note')->nullable()->after('date');
        });

        DB::table('transactions')->update([
            'date' => DB::raw('DATE(transacted_at)'),
            'note' => DB::raw('title'),
        ]);

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['title', 'transacted_at']);
        });
    }
};
