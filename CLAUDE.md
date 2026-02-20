# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Dev (runs serve + queue + pail + vite concurrently)
composer dev

# Build
npm run build          # vue-tsc type-check + vite build

# Tests
composer test          # clears config cache, then runs PHPUnit

# Single test
php artisan test --filter TestClassName
php artisan test tests/Feature/SomeTest.php

# Code format
./vendor/bin/pint

# Database
php artisan migrate
php artisan db:seed
```

> `npm install` requires `--legacy-peer-deps` (@vitejs/plugin-vue 5.x vs Vite 7).

## Architecture

**Request flow:** Browser → Laravel router → Controller → Inertia response → Vue page component rendered client-side. Auth is session-based (Sanctum). Routes live in `routes/web.php` and `routes/auth.php`.

**Controllers** are thin — they load data and pass props via `Inertia::render()`. Heavy query logic (filters, aggregates for dashboard/reports) belongs in the controller for now; there are no service classes or repositories.

**Vue pages** receive all data as Inertia props (typed via `resources/js/types/`). Pages use `useForm()` from `@inertiajs/vue3` for mutations. The `@` alias resolves to `resources/js/`.

**AppLayout** (`resources/js/Layouts/AppLayout.vue`) is the shell for all authenticated pages — sidebar on desktop, bottom tab bar on mobile.

**Offline / PWA:** `resources/js/offline.ts` queues failed POST/PATCH/DELETE requests into IndexedDB and replays them via Background Sync. The service worker at `public/sw.js` handles caching and offline fallback. PWA config is in `config/pwa.php`.

**Queue & sessions** use the database driver (not Redis). Background Sync relies on the queue worker being started via `composer dev`.

## Key conventions

- **Transaction dedup:** `uuid` column is unique; clients must generate a UUID before submitting to prevent double-submission on retry.
- **Budget upsert:** `POST /budget` upserts on `(user_id, category_id, month, year)` — no separate update route.
- **Category ownership:** All categories are user-scoped. Default categories are seeded on `Registered` event via `AppServiceProvider` → `CategorySeeder::run($user)`.
- **Type enums:** `Transaction.type` and `Category.type` are `income | expense` strings (not PHP enums).
- **Dark mode:** Toggled via `class` on `<html>`. Use `dark:` variants. Custom Tailwind colors: `coin-primary`, `coin-accent`, `coin-dark-bg`, `coin-dark-card`.
- **TypeScript:** Strict mode. All new Vue components and composables must be typed. Run `vue-tsc` (via `npm run build`) to verify.
