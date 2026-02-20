<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $month = now()->month;
        $year = now()->year;

        $budgets = $user->budgets()
            ->where('month', $month)
            ->where('year', $year)
            ->pluck('amount', 'category_id');

        $categories = $user->categories()
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => array_merge($c->toArray(), [
                'budget' => (float) ($budgets[$c->id] ?? 0),
            ]));

        return Inertia::render('Categories/Index', ['categories' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:income,expense',
            'color' => 'required|string|size:7',
            'icon' => 'required|string|max:50',
            'budget_amount' => 'required|numeric|min:0',
        ]);

        $category = $request->user()->categories()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'color' => $data['color'],
            'icon' => $data['icon'],
        ]);

        $request->user()->budgets()->create([
            'category_id' => $category->id,
            'month' => now()->month,
            'year' => now()->year,
            'amount' => $data['budget_amount'],
        ]);

        return back();
    }

    public function update(Request $request, Category $category)
    {
        abort_if($category->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'required|string|size:7',
            'icon' => 'required|string|max:50',
            'budget_amount' => 'required|numeric|min:0',
        ]);

        $category->update([
            'name' => $data['name'],
            'color' => $data['color'],
            'icon' => $data['icon'],
        ]);

        $request->user()->budgets()->updateOrCreate(
            ['category_id' => $category->id, 'month' => now()->month, 'year' => now()->year],
            ['amount' => $data['budget_amount']]
        );

        return back();
    }

    public function destroy(Request $request, Category $category)
    {
        abort_if($category->user_id !== $request->user()->id, 403);
        $category->delete();

        return back();
    }
}
