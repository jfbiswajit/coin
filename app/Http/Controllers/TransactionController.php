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

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $transactions = $query->orderByDesc('transacted_at')->paginate(20)->through(fn($t) => [
            'id' => $t->id,
            'amount' => (float) $t->amount,
            'type' => $t->type,
            'title' => $t->title,
            'transacted_at' => $t->transacted_at->format('Y-m-d\TH:i'),
            'category' => ['id' => $t->category->id, 'name' => $t->category->name, 'color' => $t->category->color, 'icon' => $t->category->icon],
        ]);

        $categories = $user->categories()->orderBy('name')->get(['id', 'name', 'type', 'color', 'icon']);

        $baseQuery = $user->transactions()
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month);

        $typeCounts = (clone $baseQuery)->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'categories' => $categories,
            'filters' => ['month' => $month, 'year' => $year, 'type' => $request->type, 'category_id' => $request->category_id],
            'typeCounts' => ['expense' => $typeCounts->get('expense', 0), 'income' => $typeCounts->get('income', 0)],
        ]);
    }

    public function create(Request $request)
    {
        $categories = $request->user()->categories()->orderBy('type')->orderBy('name')->get(['id', 'name', 'type', 'color', 'icon']);

        return Inertia::render('Transactions/Create', ['categories' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'uuid' => 'nullable|uuid',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:income,expense',
            'title' => 'required|string|max:255',
            'transacted_at' => 'required|date',
        ]);

        $uuid = $data['uuid'] ?? Str::uuid()->toString();

        $existing = $request->user()->transactions()->where('uuid', $uuid)->first();
        if ($existing) {
            return back()->with('success', 'Transaction already recorded.');
        }

        $request->user()->transactions()->create(array_merge($data, ['uuid' => $uuid]));

        return redirect()->route('transactions.index')->with('success', 'Transaction added.');
    }

    public function update(Request $request, Transaction $transaction)
    {
        abort_if($transaction->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:income,expense',
            'title' => 'required|string|max:255',
            'transacted_at' => 'required|date',
        ]);

        $transaction->update($data);

        return back();
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        abort_if($transaction->user_id !== $request->user()->id, 403);
        $transaction->delete();

        return back();
    }
}
