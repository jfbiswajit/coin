---
name: expense
description: Parse expenses from WhatsApp messages, plain text, or invoice/receipt images and create transactions. Use when the user provides expense lines or attaches an invoice photo.
argument-hint: "<WhatsApp messages, plain text lines, or invoice image path>"
disable-model-invocation: true
---

Parse expenses from `$ARGUMENTS` (or an image attached to the conversation) and create transactions.

## Step 1 — Detect input type

Determine whether the input is:
- **Image/invoice** — user attached a photo or provided an image path → follow the Image path below
- **Text** — WhatsApp messages or plain text lines → follow the Text path below

---

### Text path

Extract each expense as `{ item, amount, date }`.

Supported formats:
- Plain: `Chicken 400`
- WhatsApp: `[7:18 PM, 3/20/2026] Biswajit Biswas: Chicken 400`

Rules:
- `item` = descriptive text (e.g. "Chicken", "Fish fillets")
- `amount` = numeric value (integer or decimal)
- `date` = date from WhatsApp timestamp if present, otherwise today's date (`YYYY-MM-DD`)
- Skip lines without a recognisable item + amount pair
- **Refine the title** — fix typos, expand abbreviations, improve clarity (e.g. "Vegitables" → "Vegetables", "Mango 2 kg" → "Mango (2 kg)"). Keep it concise.
- Set `invoice_total = null`

---

### Image path

Analyze the image visually and extract:
- Every line item with its amount
- The transaction date (from the invoice date, if present; otherwise today)
- The invoice grand total (the final total printed on the invoice, if present) → save as `invoice_total`

Apply the same title refinement rules as the text path.

---

## Step 2 — Fetch expense categories

```sql
SELECT id, name FROM categories WHERE user_id = 1 AND type = 'expense' ORDER BY name
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
