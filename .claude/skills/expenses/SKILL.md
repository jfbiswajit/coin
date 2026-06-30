---
name: expenses
description: Sync expenses from Supabase or parse an invoice/receipt image and create transactions. Invoke with no args to sync from Supabase, or pass an image path/attachment for invoice parsing.
argument-hint: "[invoice image path] — omit to sync from Supabase"
---

## Context

The user logs expenses on the go via a companion PWA hosted on Netlify (accessible from phone/outside home). That PWA stores entries in a Supabase `expenses` table (columns: `id`, `item`, `amount`, `created_at`). When back at the machine, the user syncs those entries into this local Laravel app via this skill, then the Supabase table is cleared for the next batch.

Two sources:
- `/expenses` (no args) — sync from Supabase
- `/expenses <image>` — parse a receipt or invoice photo

---

## Step 1 — Detect input type

- **No args** → Supabase path
- **Image path or attached image** → Image path

---

### Supabase path

Fetch all rows from Supabase:

```bash
curl -s "https://zzgtbqjepmxizljfxfxf.supabase.co/rest/v1/expenses?order=created_at.asc" \
  -H "apikey: sb_publishable_kYq-Pxk-jWoTyFGaksAnfg_C-sKyfMh" \
  -H "Authorization: Bearer sb_publishable_kYq-Pxk-jWoTyFGaksAnfg_C-sKyfMh"
```

Extract each row as `{ item, amount, date }` from the JSON response:
- `item` = `item` field
- `amount` = `amount` field
- `date` = date part of `created_at` (YYYY-MM-DD)
- **Refine the title** — fix typos, expand abbreviations (e.g. "Mobie cover" → "Mobile Cover")
- Set `invoice_total = null`

If the table is empty, stop and tell the user there is nothing to sync.

---

### Image path

Analyze the image visually and extract:
- Every line item with its amount
- The transaction date (from the invoice date if present, otherwise today)
- The invoice grand total (if printed on the invoice) → save as `invoice_total`

Apply the same title refinement rules as above.

---

## Step 2 — Fetch expense categories

```bash
php artisan tinker --execute "
use App\Models\Category;
Category::where('user_id', 1)->where('type', 'expense')->orderBy('name')->get(['id', 'name'])->each(fn(\$c) => print(\$c->id . ' | ' . \$c->name . PHP_EOL));
"
```

## Step 3 — Map each item to a category

Use only the fetched category IDs. For each item:
1. Consider what real-world purpose the item serves.
2. Pick the best-matching category.
3. If no clear fit, use the fallback (e.g. "Miscellaneous").

## Step 4 — Show confirmation table

Display a markdown table:

| # | Item | Amount | Category | Date |
|---|------|--------|----------|------|
| 1 | Chicken | 400 | Groceries | 2026-03-20 |
| 2 | Fish | 200 | Groceries | 2026-03-20 |
| | **Total** | **600** | | |

**If `invoice_total` is not null**, add a verification row immediately after the table:

- If `invoice_total` == sum of all amounts: ✓ Invoice total matches (600)
- If they differ: ⚠ Invoice total is X but extracted items sum to Y — please review before confirming

Then ask: **"Insert these transactions? (y/n)"**

## Handling feedback on the confirmation table

If the user provides corrections (e.g. wrong title, wrong amount, wrong category) instead of y/n:
- Apply all corrections to the in-memory table
- **Always respond by showing the full updated table** (all rows, not just the changed ones), with the updated totals and invoice verification line
- Then ask: **"Insert these transactions? (y/n)"**

Keep doing this for every round of corrections until the user confirms with **y**.

## Step 5 — Insert on confirmation

Run a single `php artisan tinker --execute "..."` command:

```php
php artisan tinker --execute "
use App\Models\Transaction;
use Illuminate\Support\Str;

\$transactions = [
    ['category_id' => 3, 'amount' => 400, 'title' => 'Chicken', 'transacted_at' => '2026-03-20'],
    ['category_id' => 3, 'amount' => 200, 'title' => 'Fish', 'transacted_at' => '2026-03-20'],
];

foreach (\$transactions as \$t) {
    Transaction::create([
        'uuid'          => Str::uuid(),
        'user_id'       => 1,
        'category_id'   => \$t['category_id'],
        'type'          => 'expense',
        'amount'        => \$t['amount'],
        'title'         => \$t['title'],
        'transacted_at' => \$t['transacted_at'],
    ]);
}

echo 'Inserted ' . count(\$transactions) . ' transactions';
"
```

Confirm with the count of inserted transactions.

## Step 6 — Truncate Supabase (Supabase path only)

Only run this step if the input was from Supabase. After successful insertion, delete all rows from the Supabase table:

```bash
curl -s -X DELETE "https://zzgtbqjepmxizljfxfxf.supabase.co/rest/v1/expenses?id=not.is.null" \
  -H "apikey: sb_publishable_kYq-Pxk-jWoTyFGaksAnfg_C-sKyfMh" \
  -H "Authorization: Bearer sb_publishable_kYq-Pxk-jWoTyFGaksAnfg_C-sKyfMh" \
  -H "Prefer: return=minimal"
```

Confirm to the user that Supabase has been cleared and is ready for new entries.
