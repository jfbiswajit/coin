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
        $date = $request->get('date');

        if ($date) {
            $dateObj = \Carbon\Carbon::parse($date);
            $month = $dateObj->month;
            $year = $dateObj->year;
        } else {
            $month = (int) $request->get('month', now()->month);
            $year = (int) $request->get('year', now()->year);
        }

        $query = $user->transactions()->with('category')
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month);

        if ($date) {
            $query->whereDate('transacted_at', $date);
        }

        $type = $request->get('type', 'expense');
        $query->where('type', $type);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('is_credit')) {
            $query->where('is_credit', (bool) $request->is_credit);
        }

        $transactions = $query->orderByDesc('transacted_at')->orderBy('id')->paginate(20)->withQueryString()->through(fn($t) => [
            'id' => $t->id,
            'amount' => (float) $t->amount,
            'type' => $t->type,
            'is_credit' => (bool) $t->is_credit,
            'title' => $t->title,
            'transacted_at' => $t->transacted_at->format('Y-m-d'),
            'category' => ['id' => $t->category->id, 'name' => $t->category->name, 'color' => $t->category->color, 'icon' => $t->category->icon],
        ]);

        $categories = $this->activeCategories($user);

        $baseQuery = $user->transactions()
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month);

        if ($date) {
            $baseQuery->whereDate('transacted_at', $date);
        }

        $typeCounts = (clone $baseQuery)->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'categories' => $categories,
            'filters' => ['month' => $month, 'year' => $year, 'type' => $type, 'category_id' => $request->category_id, 'date' => $date, 'is_credit' => $request->has('is_credit') ? (bool) $request->is_credit : null],
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
            'is_credit' => 'sometimes|boolean',
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
            'is_credit' => $category->type === 'expense' && ($data['is_credit'] ?? false),
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
            'is_credit' => 'sometimes|boolean',
        ]);

        $category = $request->user()->categories()->findOrFail($data['category_id']);

        $transaction->update([
            'category_id' => $data['category_id'],
            'amount' => $data['amount'],
            'type' => $category->type,
            'is_credit' => $category->type === 'expense' && ($data['is_credit'] ?? false),
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

    public function settleLoan(Request $request, $categoryId)
    {
        $category = $request->user()->categories()->where('type', 'loan')->findOrFail($categoryId);
        $category->update(['settled_at' => now()]);

        return back()->with('success', 'Loan settled.');
    }

    public function withdrawSaving(Request $request, $categoryId)
    {
        $user = $request->user();
        $savingCategory = $user->categories()->where('type', 'saving')->findOrFail($categoryId);

        $data = $request->validate([
            'income_category_id' => 'required|exists:categories,id',
        ]);

        $incomeCategory = $user->categories()->where('type', 'income')->findOrFail($data['income_category_id']);

        $totalSaved = $user->transactions()
            ->where('type', 'saving')
            ->where('category_id', $savingCategory->id)
            ->sum('amount');

        if ($totalSaved <= 0) {
            return back()->withErrors(['withdraw' => 'No savings to withdraw.']);
        }

        $user->transactions()->create([
            'uuid' => Str::uuid()->toString(),
            'category_id' => $incomeCategory->id,
            'amount' => $totalSaved,
            'type' => 'income',
            'title' => 'Withdrawal – ' . $savingCategory->name,
            'transacted_at' => now()->toDateString(),
        ]);

        $savingCategory->update(['withdrawn_at' => now()]);

        return back()->with('success', 'Withdrawal successful.');
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
                if ($cat->settled_at !== null) return false;
                $remaining = (float) $cat->loan_amount - (float) ($loanPaid[$cat->id] ?? 0);
                return $remaining > 0;
            }
            if ($cat->type === 'saving') {
                if ($cat->withdrawn_at !== null) return false;
                if ($cat->target_amount !== null && (float) $cat->target_amount > 0) {
                    return (float) ($savingTotals[$cat->id] ?? 0) < (float) $cat->target_amount;
                }
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
