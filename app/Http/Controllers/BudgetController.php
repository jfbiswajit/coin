<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $expenseCategories = $user->categories()->where('type', 'expense')->orderBy('name')->get();

        $currentSpent = $user->transactions()
            ->where('type', 'expense')
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $expenses = $expenseCategories->map(fn ($cat) => [
            'category_id' => $cat->id,
            'name' => $cat->name,
            'color' => $cat->color,
            'icon' => $cat->icon,
            'budget' => $cat->monthly_budget !== null ? (float) $cat->monthly_budget : null,
            'spent' => (float) ($currentSpent[$cat->id] ?? 0),
        ]);

        $loanCategories = $user->categories()->where('type', 'loan')->orderBy('name')->get();

        $loanPaidThisMonth = $user->transactions()
            ->where('type', 'loan')
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $loanPaidTotal = $user->transactions()
            ->where('type', 'loan')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $loans = $loanCategories->map(fn ($cat) => [
            'category_id' => $cat->id,
            'name' => $cat->name,
            'color' => $cat->color,
            'icon' => $cat->icon,
            'loan_amount' => (float) $cat->loan_amount,
            'emi_amount' => (float) $cat->emi_amount,
            'paid_this_month' => (float) ($loanPaidThisMonth[$cat->id] ?? 0),
            'total_paid' => (float) ($loanPaidTotal[$cat->id] ?? 0),
            'remaining' => max(0, (float) $cat->loan_amount - (float) ($loanPaidTotal[$cat->id] ?? 0)),
        ]);

        $savingCategories = $user->categories()->where('type', 'saving')->orderBy('name')->get();

        $savedThisMonth = $user->transactions()
            ->where('type', 'saving')
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $savedTotal = $user->transactions()
            ->where('type', 'saving')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $savings = $savingCategories->map(fn ($cat) => [
            'category_id' => $cat->id,
            'name' => $cat->name,
            'color' => $cat->color,
            'icon' => $cat->icon,
            'monthly_amount' => (float) $cat->monthly_amount,
            'target_amount' => $cat->target_amount !== null ? (float) $cat->target_amount : null,
            'saved_this_month' => (float) ($savedThisMonth[$cat->id] ?? 0),
            'total_saved' => (float) ($savedTotal[$cat->id] ?? 0),
        ]);

        return Inertia::render('Budget/Index', [
            'expenses' => $expenses,
            'loans' => $loans,
            'savings' => $savings,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function upsert(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $category = $request->user()->categories()->where('type', 'expense')->findOrFail($data['category_id']);
        $category->update(['monthly_budget' => $data['amount']]);

        return back();
    }
}
