<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $query = $user->transactions()->with('category')
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month);

        $type = $request->get('type', 'expense');
        $query->where('type', $type);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $transactions = $query->orderByDesc('transacted_at')->paginate(20)->through(fn($t) => [
            'id' => $t->id,
            'amount' => (float) $t->amount,
            'type' => $t->type,
            'title' => $t->title,
            'transacted_at' => $t->transacted_at->format('Y-m-d'),
            'category' => ['id' => $t->category->id, 'name' => $t->category->name, 'color' => $t->category->color, 'icon' => $t->category->icon],
        ]);

        $categories = $this->activeCategories($user);

        $baseQuery = $user->transactions()
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month);

        $typeCounts = (clone $baseQuery)->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'categories' => $categories,
            'filters' => ['month' => $month, 'year' => $year, 'type' => $type, 'category_id' => $request->category_id],
            'typeCounts' => [
                'expense' => $typeCounts->get('expense', 0),
                'income' => $typeCounts->get('income', 0),
                'saving' => $typeCounts->get('saving', 0),
                'loan' => $typeCounts->get('loan', 0),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $categories = $this->activeCategories($request->user());

        return Inertia::render('Transactions/Create', ['categories' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'uuid' => 'nullable|uuid',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'title' => 'required|string|max:255',
            'transacted_at' => 'required|date',
        ]);

        $category = $request->user()->categories()->findOrFail($data['category_id']);

        $uuid = $data['uuid'] ?? Str::uuid()->toString();

        $existing = $request->user()->transactions()->where('uuid', $uuid)->first();
        if ($existing) {
            return back()->with('success', 'Transaction already recorded.');
        }

        $request->user()->transactions()->create([
            'uuid' => $uuid,
            'category_id' => $data['category_id'],
            'amount' => $data['amount'],
            'type' => $category->type,
            'title' => $data['title'],
            'transacted_at' => $data['transacted_at'],
        ]);

        return back()->with('success', 'Transaction added.');
    }

    public function update(Request $request, Transaction $transaction)
    {
        abort_if($transaction->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'title' => 'required|string|max:255',
            'transacted_at' => 'required|date',
        ]);

        $category = $request->user()->categories()->findOrFail($data['category_id']);

        $transaction->update([
            'category_id' => $data['category_id'],
            'amount' => $data['amount'],
            'type' => $category->type,
            'title' => $data['title'],
            'transacted_at' => $data['transacted_at'],
        ]);

        return back();
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        abort_if($transaction->user_id !== $request->user()->id, 403);
        $transaction->delete();

        return back();
    }

    private function activeCategories($user)
    {
        $categories = $user->categories()->orderBy('type')->orderBy('name')->get();

        $loanPaid = $user->transactions()
            ->where('type', 'loan')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $savingTotals = $user->transactions()
            ->where('type', 'saving')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return $categories->filter(function ($cat) use ($loanPaid, $savingTotals) {
            if ($cat->type === 'loan') {
                $remaining = (float) $cat->loan_amount - (float) ($loanPaid[$cat->id] ?? 0);
                return $remaining > 0;
            }
            if ($cat->type === 'saving' && $cat->target_amount !== null) {
                return (float) ($savingTotals[$cat->id] ?? 0) < (float) $cat->target_amount;
            }
            return true;
        })->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'type' => $c->type,
            'color' => $c->color,
            'icon' => $c->icon,
        ])->values();
    }
}
