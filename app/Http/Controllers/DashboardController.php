<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $summary = $user->transactions()
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $totalIncome = (float) ($summary['income'] ?? 0);
        $totalExpense = (float) ($summary['expense'] ?? 0);
        $balance = $totalIncome - $totalExpense;
        $savingsRate = $totalIncome > 0 ? round(($balance / $totalIncome) * 100, 1) : null;

        $expenseByCategory = $user->transactions()
            ->with('category')
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month)
            ->where('type', 'expense')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get()
            ->map(fn($t) => [
                'label' => $t->category->name,
                'color' => $t->category->color,
                'total' => (float) $t->total,
            ]);

        $last6Months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $d = Carbon::createFromDate($year, $month, 1)->startOfMonth()->subMonths($i);
            $monthSummary = $user->transactions()
                ->whereYear('transacted_at', $d->year)
                ->whereMonth('transacted_at', $d->month)
                ->selectRaw('type, SUM(amount) as total')
                ->groupBy('type')
                ->pluck('total', 'type');
            $last6Months->push([
                'label' => $d->format('M'),
                'income' => (float) ($monthSummary['income'] ?? 0),
                'expense' => (float) ($monthSummary['expense'] ?? 0),
            ]);
        }

        $recentTransactions = $user->transactions()
            ->with('category')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'amount' => (float) $t->amount,
                'type' => $t->type,
                'date' => $t->transacted_at->toIso8601String(),
                'note' => $t->title,
                'category' => [
                    'name' => $t->category->name,
                    'color' => $t->category->color,
                    'icon' => $t->category->icon,
                ],
            ]);

        return Inertia::render('Dashboard', [
            'stats' => compact('totalIncome', 'totalExpense', 'balance', 'savingsRate'),
            'expenseByCategory' => $expenseByCategory,
            'last6Months' => $last6Months,
            'recentTransactions' => $recentTransactions,
            'month' => $month,
            'year' => $year,
        ]);
    }
}
