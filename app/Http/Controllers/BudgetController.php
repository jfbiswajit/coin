<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $categories = $user->categories()->where('type', 'expense')->orderBy('name')->get();

        $budgets = $user->budgets()
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('category_id');

        $spent = $user->transactions()
            ->where('type', 'expense')
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $data = $categories->map(fn($cat) => [
            'category_id' => $cat->id,
            'name' => $cat->name,
            'color' => $cat->color,
            'icon' => $cat->icon,
            'budget' => $budgets->has($cat->id) ? (float) $budgets[$cat->id]->amount : null,
            'spent' => (float) ($spent[$cat->id] ?? 0),
        ]);

        return Inertia::render('Budget/Index', [
            'budgets' => $data,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function upsert(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'amount' => 'required|numeric|min:0',
        ]);

        $request->user()->budgets()->updateOrCreate(
            ['category_id' => $data['category_id'], 'month' => $data['month'], 'year' => $data['year']],
            ['amount' => $data['amount']]
        );

        return back();
    }
}
