<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $year = (int) $request->get('year', now()->year);

        $monthly = $user->transactions()
            ->whereYear('transacted_at', $year)
            ->selectRaw('MONTH(transacted_at) as month, type, SUM(amount) as total')
            ->groupByRaw('MONTH(transacted_at), type')
            ->get();

        $byMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $byMonth[$m] = ['income' => 0.0, 'expense' => 0.0, 'balance' => 0.0];
        }

        foreach ($monthly as $row) {
            $byMonth[$row->month][$row->type] = (float) $row->total;
        }

        foreach ($byMonth as $m => &$data) {
            $data['balance'] = $data['income'] - $data['expense'];
        }

        $topCategories = $user->transactions()
            ->with('category')
            ->whereYear('transacted_at', $year)
            ->where('type', 'expense')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn($t) => [
                'label' => $t->category->name,
                'color' => $t->category->color,
                'total' => (float) $t->total,
            ]);

        return Inertia::render('Reports/Index', [
            'byMonth' => array_values($byMonth),
            'topCategories' => $topCategories,
            'year' => $year,
        ]);
    }
}
