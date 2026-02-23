<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $month = now()->month;
        $year = now()->year;

        $allTime = $user->transactions()
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $balance = (float) ($allTime['income'] ?? 0)
                 - (float) ($allTime['expense'] ?? 0)
                 - (float) ($allTime['loan'] ?? 0)
                 - (float) ($allTime['saving'] ?? 0);

        $loanCategories = $user->categories()->where('type', 'loan')->get();

        $loanPaidTotal = $user->transactions()
            ->where('type', 'loan')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $totalLoanOutstanding = $loanCategories->sum(function ($cat) use ($loanPaidTotal) {
            return max(0, (float) $cat->loan_amount - (float) ($loanPaidTotal[$cat->id] ?? 0));
        });

        $totalSaved = (float) ($allTime['saving'] ?? 0);

        $thisMonth = $user->transactions()
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $incomeThisMonth = (float) ($thisMonth['income'] ?? 0);
        $spentThisMonth = (float) ($thisMonth['expense'] ?? 0)
                        + (float) ($thisMonth['loan'] ?? 0)
                        + (float) ($thisMonth['saving'] ?? 0);

        $expenseCategoryIds = $user->categories()->where('type', 'expense')->pluck('id');

        $totalBudget = (float) $user->budgets()
            ->whereIn('category_id', $expenseCategoryIds)
            ->where('month', $month)
            ->where('year', $year)
            ->sum('amount');

        $totalEMI = (float) $loanCategories->sum('emi_amount');

        $savingCategories = $user->categories()->where('type', 'saving')->get();
        $totalSavingTarget = (float) $savingCategories->sum('monthly_amount');

        $moneyNeeded = $totalBudget + $totalEMI + $totalSavingTarget;

        $recent = $user->transactions()
            ->with('category')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'id'            => $t->id,
                'amount'        => (float) $t->amount,
                'type'          => $t->type,
                'title'         => $t->title,
                'transacted_at' => $t->transacted_at->format('Y-m-d'),
                'category'      => ['name' => $t->category->name, 'color' => $t->category->color],
            ]);

        return Inertia::render('Dashboard', [
            'balance' => $balance,
            'loanOutstanding' => $totalLoanOutstanding,
            'totalSaved' => $totalSaved,
            'incomeThisMonth' => $incomeThisMonth,
            'spentThisMonth' => $spentThisMonth,
            'moneyNeeded' => $moneyNeeded,
            'monthLabel' => now()->format('F Y'),
            'recent' => $recent,
        ]);
    }
}
