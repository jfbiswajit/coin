<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = $request->user()->categories()
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => $c->type,
                'color' => $c->color,
                'icon' => $c->icon,
                'budget' => $c->monthly_budget !== null ? (float) $c->monthly_budget : 0,
                'loan_amount' => $c->loan_amount !== null ? (float) $c->loan_amount : null,
                'emi_amount' => $c->emi_amount !== null ? (float) $c->emi_amount : null,
                'monthly_amount' => $c->monthly_amount !== null ? (float) $c->monthly_amount : null,
                'target_amount' => $c->target_amount !== null ? (float) $c->target_amount : null,
            ]);

        return Inertia::render('Categories/Index', ['categories' => $categories]);
    }

    public function store(Request $request)
    {
        $base = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:income,expense,saving,loan',
            'color' => 'required|string|size:7',
            'icon' => 'required|string|max:50',
        ]);

        $categoryData = [
            'name' => $base['name'],
            'type' => $base['type'],
            'color' => $base['color'],
            'icon' => $base['icon'],
        ];

        if ($base['type'] === 'expense') {
            $extra = $request->validate(['budget_amount' => 'required|numeric|min:0']);
            $categoryData['monthly_budget'] = $extra['budget_amount'];
        } elseif ($base['type'] === 'income') {
            $extra = $request->validate(['monthly_amount' => 'required|numeric|min:0.01']);
            $categoryData['monthly_amount'] = $extra['monthly_amount'];
        } elseif ($base['type'] === 'loan') {
            $extra = $request->validate([
                'loan_amount' => 'required|numeric|min:0.01',
                'emi_amount' => 'required|numeric|min:0.01',
            ]);
            $categoryData['loan_amount'] = $extra['loan_amount'];
            $categoryData['emi_amount'] = $extra['emi_amount'];
        } elseif ($base['type'] === 'saving') {
            $extra = $request->validate([
                'monthly_amount' => 'required|numeric|min:0.01',
                'target_amount' => 'nullable|numeric|min:0',
            ]);
            $categoryData['monthly_amount'] = $extra['monthly_amount'];
            $categoryData['target_amount'] = $extra['target_amount'] ?? null;
        }

        $request->user()->categories()->create($categoryData);

        return back();
    }

    public function update(Request $request, Category $category)
    {
        abort_if($category->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'required|string|size:7',
            'icon' => 'required|string|max:50',
        ]);

        if ($category->type === 'expense') {
            $extra = $request->validate(['budget_amount' => 'required|numeric|min:0']);
            $data['monthly_budget'] = $extra['budget_amount'];
        } elseif ($category->type === 'income') {
            $extra = $request->validate(['monthly_amount' => 'required|numeric|min:0.01']);
            $data['monthly_amount'] = $extra['monthly_amount'];
        } elseif ($category->type === 'loan') {
            $extra = $request->validate([
                'loan_amount' => 'required|numeric|min:0.01',
                'emi_amount' => 'required|numeric|min:0.01',
            ]);
            $data['loan_amount'] = $extra['loan_amount'];
            $data['emi_amount'] = $extra['emi_amount'];
        } elseif ($category->type === 'saving') {
            $extra = $request->validate([
                'monthly_amount' => 'required|numeric|min:0.01',
                'target_amount' => 'nullable|numeric|min:0',
            ]);
            $data['monthly_amount'] = $extra['monthly_amount'];
            $data['target_amount'] = $extra['target_amount'] ?? null;
        }

        $category->update($data);

        return back();
    }

    public function destroy(Request $request, Category $category)
    {
        abort_if($category->user_id !== $request->user()->id, 403);
        $category->delete();

        return back();
    }
}
