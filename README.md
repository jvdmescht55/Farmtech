# Farmtech

AI-vetted sourcing, compliance vetting, and dropshipping platform for South African
agricultural technology — digital livestock scales, handheld veterinary ultrasound
scanners, and ISO 11784/11785 RFID equipment.

## Architecture

```
Farmtech/
├── app/                    Laravel 12 application (admin dashboard + storefront)
│   ├── Http/Controllers/
│   │   ├── Admin/          Staging queue, product review, settings
│   │   └── Storefront/     Home, category, product, cart, checkout
│   ├── Models/              Product, ProductSpec, ProductImage, ComplianceAudit,
│   │                        ExchangeRate, Setting, Order, User, BlacklistedSupplier
│   └── Services/
│       ├── LandedCostCalculator.php   PHP mirror of worker/src/lib/landedCost.js
│       └── Payments/                  PayFast / Ozow / Yoco gateway scaffolds
├── database/migrations/    Full schema — see "Database schema" below
├── resources/views/        Blade views (Tailwind), admin/ and storefront/
├── worker/                 Node.js sourcing & AI compliance vetting pipeline
│   ├── src/pipeline.js     CLI entry point
│   ├── src/lib/            forex, landed cost, Gemini vetting, image pipeline, DB (mysql + sqlite drivers)
│   ├── src/prompts/        Vetting system prompt + strict JSON response schema
│   ├── test/               node:test unit tests (22 tests, all passing)
│   └── mock_data.json      5 worked test-case listings (see below)
├── docker-compose.yml      laravel.test (PHP 8.2) + mysql (MariaDB) + worker (Node)
├── setup.sh / setup.bat    One-shot turnkey setup
└── .env.example
```

## Requirements

- Docker Desktop (runs PHP, MySQL, and the worker in containers — no local PHP/MySQL install needed)
- Node.js 20+ (for the worker CLI and the Tailwind/Vite asset build)
- A Gemini API key (for the sourcing pipeline's compliance vetting) — get one at
  https://aistudio.google.com/apikey
- Optionally: an exchangerate-api.com key (`USD_ZAR_API_KEY`) for live forex — the pipeline
  falls back to the last rate stored in `exchange_rates` if this is unset or the API is offline

## Setup

```bash
cp .env.example .env   # setup.sh/.bat does this for you too
./setup.sh             # or setup.bat on Windows without a POSIX shell
```

This copies `.env`, installs worker + frontend dependencies, builds Tailwind assets, builds and
starts the Docker containers, waits for MySQL, generates `APP_KEY`, runs migrations, seeds
settings/admin user/exchange-rate defaults, and links storage.

Before running it, put your real keys in `.env`:

- `GEMINI_API_KEY` — required for the sourcing pipeline
- `USD_ZAR_API_KEY` — optional, live forex (get one free at exchangerate-api.com)
- `ADMIN_EMAIL` / `ADMIN_PASSWORD` — your admin login (seeded on first run)
- `PAYFAST_*` / `OZOW_*` / `YOCO_*` — leave blank until you have real sandbox credentials;
  checkout will show a clear "gateway not configured" error rather than silently failing

Then visit:

- Storefront: http://localhost:8000
- Admin: http://localhost:8000/admin/login

## Running the sourcing pipeline

```bash
docker compose exec worker node src/pipeline.js --file mock_data.json
```

This ingests the 5 bundled test listings, fetches the live USD/ZAR rate (or falls back),
computes landed cost per the formula below, calls Gemini for compliance vetting + copywriting,
downloads and converts images to `.webp`, and inserts each as a `pending_review` or `rejected`
product. Review the results at `/admin/products`.

Useful flags: `--sku <sku>` to run a single listing, `--dry-run` to skip DB/image writes and
print the vetting result instead (still calls the real Gemini API), `--skip-images` to skip
image download while still writing to the DB. Run `node src/pipeline.js --help` for details.

### Input format

`--file` takes a JSON file with either one listing object or an array of them. See
`worker/mock_data.json` for 5 worked examples covering: a compliant 134.2kHz ISO reader, a
cattle ultrasound scanner with a battery-certification gap, an invalid 125kHz pet-chip reader
that should be rejected, a compliant scale indicator, and a high-risk unverified-supplier
listing that should also be rejected. Required fields: `sku`, `raw_title`, `category_hint`,
`supplier_name`, `supplier_price_usd`, `weight_kg`, `duty_rate`, `raw_specs_text`.

Live scraping of Alibaba/Made-in-China URLs is **not** implemented — those sites' scraping
protections and ToS make a robust in-repo scraper out of scope here. The pipeline instead
expects listing data already extracted into this JSON shape, however that extraction happens
(manual entry, a browser extension, a separate scraping tool you run first).

## Landed cost formula

Implemented identically in `app/Services/LandedCostCalculator.php` (PHP, used by the admin
margin slider) and `worker/src/lib/landedCost.js` (Node, used by the pipeline):

```
Base ZAR      = (Supplier USD + (Weight KG × Freight USD/KG)) × USDZAR
Landed Cost   = (Base ZAR × (1 + Duty Rate)) × (1 + VAT Rate) + Clearance Fee
Retail Price  = Landed Cost / (1 - Target Margin)
```

## Database schema

`products`, `product_specs`, `product_images`, `compliance_audits`, `exchange_rates`,
`settings` as specified, plus `users` (admin auth), `orders`/`order_items` (checkout scaffold),
and `blacklisted_suppliers` (backs the "Reject & Blacklist Supplier" admin action).

## Deviations from the original spec (and why)

- **AI vetting runs on Gemini, not Claude.** The spec called for Anthropic's Claude, but only a
  Gemini key was available to actually run this end to end — the vetting logic (prompt rules,
  strict JSON schema, retry handling) is provider-agnostic in design, so `worker/src/lib/geminiVetting.js`
  and `worker/src/prompts/vettingPrompt.js` swap in cleanly. Model is `gemini-2.5-flash` by
  default, overridable via `GEMINI_MODEL`.
- **Admin credentials live in `users` (Laravel's standard auth table), not `settings`.**
  Storing login credentials in a generic key-value config table has no hashing convention and
  no framework auth integration — a real security smell. `settings` still holds every
  *operational* value the spec asked for (margin, freight rate, clearing fee).
- **Payment gateway API keys are `.env`-only, not editable from `/admin/settings`.** A web form
  writing secrets into the plain `settings` table (no encryption-at-rest) would undo the point
  of keeping them out of `users`. They're documented in `.env.example` instead.
- **No Alibaba/Made-in-China live scraper.** See "Input format" above.
- **Worker supports an optional SQLite driver** (`WORKER_DB_DRIVER=sqlite`, via Node's built-in
  `node:sqlite`) alongside the default MySQL driver, purely so the pipeline can be tried without
  Docker/MySQL. Production (Docker) still uses MySQL by default — see `worker/.env.example`.

## Verification status

This was built and then actually run end-to-end, not just written:

- **Laravel app**: PHP 8.2, Composer, and a local MariaDB were installed directly (this dev
  environment initially had none of those, nor Docker) so the app could be migrated, seeded, and
  exercised for real. Confirmed working in-browser: homepage, admin login, staging queue,
  settings page, all against real MySQL — not just "should work."
- **Node worker**: 22 unit tests pass, including a real network download → MIME-sniff → resize →
  webp re-encode against a live image URL, and the sqlite driver tested against the actual
  Laravel-migrated schema (`worker/test/`, run via `npm test` inside `worker/`).
- **Docker path**: unchanged and still the documented way to run this normally
  (`./setup.sh` / `setup.bat`) — the PHP/MariaDB installs above were a this-machine-only
  workaround, not a replacement for the Docker setup.
- **Payment gateways** (PayFast/Ozow/Yoco): still unverified — need real sandbox credentials.

## Storefront v2 — search, hero slider, admin sourcing, import transparency

- **Search**: `/search?q=` matches title, short description, SKU, and spec key/value (real
  `LIKE` queries — `app/Http/Controllers/Storefront/SearchController.php`), wired to the header
  search bar on every page.
- **Featured-spot hero slider**: auto-advancing carousel of the latest approved products at the
  top of the homepage (Alpine.js, pauses on hover, dot/arrow controls). Falls back to a static
  hero when there are no approved products yet.
- **Import/duty/VAT transparency panel**: every product page shows the actual duty rate, VAT
  rate, and HS code, plus a delivery-stage visual — grounded in real research on what cross-border
  shoppers actually want disclosed (see chat), not invented trust copy. Deliberately does *not*
  show the rand amount of duty or the underlying freight/clearing-fee/margin inputs, since
  combined those would let a customer reverse-engineer supplier cost.
- **"What We Checked Before Listing This"**: real compliance-audit facts (frequency, ICASA
  status, battery cert, supplier trust) shown on the product page — each line is conditional on
  that fact actually being present for that product, never a fixed template with blanks filled
  in in.
- **Admin → Source New Listing** (`/admin/source`): a form that shells out to the real
  `worker/src/pipeline.js` (`App\Http\Controllers\Admin\SourceController`) with the worker
  directory as its cwd, then redirects straight to the review page for whatever it produced. This
  is the "log in and hit edit or post" loop — no terminal needed for day-to-day sourcing.
- **AI hero-image cleanup** (`worker/src/lib/imageEnhance.js`): background/lighting/crop cleanup
  of the real first supplier photo via Gemini image editing — deliberately an edit of the actual
  photo, not a generated fictional image, so the hero slide still depicts the exact item shipped.
  Requires an image-capable model on a billing-enabled Gemini project; falls back to the
  unedited original photo on any failure (including simply having zero quota for image models,
  which is what this project's key currently has).

### Windows gotcha: shelling out to Node from `php artisan serve`

If you hit `Assertion failed: ncrypto::CSPRNG(nullptr, 0)` when Laravel spawns the worker
process on Windows: PHP's built-in dev server handles each request in a way that doesn't
reliably propagate `SystemRoot`/`windir` to child processes, and Node's crypto init on Windows
needs `SystemRoot` to locate `bcrypt.dll`. `SourceController::childProcessEnv()` explicitly
whitelists the Windows vars Node needs and re-parses `worker/.env` itself (rather than trusting
inherited environment) so Laravel's own root `.env` — which deliberately sets
`WORKER_DB_HOST=mysql` for the Docker path — can't shadow `worker/.env`'s real local value. Not
an issue under Docker/Linux.
