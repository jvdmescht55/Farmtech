# Farmtech — Progress & Outstanding Work

Last updated: 2026-08-20 (rev. 6 — arbitrage engine, profit transparency, image quality).
What changed since rev. 5: the landed-cost formula was rebuilt to expose freight/customs/delivery
as separate stored line items, a strict "worth importing" arbitrage filter now runs before and
after AI vetting (minimum $50 base value, minimum 40% margin checked against AI-judged SA retail
competitiveness), the admin gets a Profit Breakdown card on every product and order plus a 30-day
Dashboard (Admin-only), the image pipeline now rejects undersized/wrong-aspect-ratio/watermarked
images before saving, and several category hero photos were swapped for ones that actually show
the equipment. Rev. 5 added the scraper webhook; rev. 4 covered RBAC, rate limiting, S3 storage,
CI, and the storefront redesign. See [README.md](README.md) for architecture/setup.

---

## ✅ Working and verified (this session)

- **Rebuilt landed-cost formula with a separated cost breakdown.** `landed_cost_zar` used to be one
  opaque number; the calculator (Node `landedCost.js` and PHP `LandedCostCalculator`, still kept
  in sync deliberately) now also returns and stores `intl_freight_zar`, `customs_vat_zar`, and
  `domestic_delivery_zar` as their own columns, so the admin UI can show exactly where the money
  went without recomputing from settings that may have since changed. **One deliberate deviation
  from the literal spec formula**: the spec's `customs_vat_zar = (base+freight) * (1+duty) * 1.15`
  would, if summed directly into `landed_cost` alongside `base` and `freight` again, double-count
  the base+freight principal (it's already embedded in that multiplication). I implemented
  `customs_vat_zar` as the *incremental* tax amount — `(base+freight) * [(1+duty)(1+vat) - 1]` —
  so the four line items sum to exactly the landed cost, verified by a dedicated test in both
  Node and PHP. Freight defaults to $16/kg and the flat delivery allowance to R250 (was R450,
  relabeled from "Clearing Agent Fee" to "Domestic Delivery Allowance" — same settings column,
  new real-world meaning, see `config/farmtech.php`).
- **Strict arbitrage filter, verified against three real live Gemini runs, not just unit tests.**
  - **Rule 1 (min $50 base value)**: a cheap pre-check before any AI spend, same principle as the
    existing supplier-blacklist check. Verified live: re-ran `mock_data.json`'s $9.50 "cheap pet
    chip reader" test case — it was skipped with `below_minimum_base_value`, zero Gemini calls
    made for it, while the other four (all ≥$50) were vetted normally.
  - **Rule 2 (min 40% margin, AI-judged competitiveness)**: the pipeline now computes the required
    retail price at *at least* 40% margin (a floor over whatever the admin's configured target
    margin is) and hands that price to Gemini alongside the listing, asking it to judge whether
    that's plausible against SA retail for the same tech spec. A `FAIL` compliance verdict always
    wins; otherwise an `uncompetitive` pricing verdict sets `status = rejected_uncompetitive` — a
    new status value, distinct from a compliance `rejected`, so an admin can tell "technically fine
    but not worth importing" apart from "actually broke a rule." Verified live: ran the real
    `mock_data.json` set (all came back genuinely `competitive`, which is a real result — I can't
    force a fake rejection just to demo one) **and** a synthetic worked example (a $65 reader
    artificially weighted at 22kg to blow out freight) that Gemini correctly flagged as
    uncompetitive — real API call, real `rejected_uncompetitive` status, real rejection reason
    quoting the inflated required price against the stated local comparable.
  - Every listing now also carries the constant footer promise: "Free Express Door-to-Door Delivery
    Across South Africa (All Customs & Clearance Handled)" — added to the homepage trust bar and
    every product's Delivery & Warranty tab, verified rendering on a real approved product page.
- **Admin Profit Breakdown + Dashboard, gated behind a new `view-financials` Gate (Admin only).**
  - `/admin/products/{id}`: a Profit Breakdown card showing Base Cost (USD & ZAR), Freight +
    Courier, Duty + VAT, Landed Cost, Retail Price, and "Your Cut" (net profit ZAR + margin %) —
    live-recalculated as the margin slider moves, same AJAX endpoint as before, now returning the
    fuller breakdown. Verified live against a real staged product: R1,258 base → R2,119.18 landed
    → R3,260.28 retail → R1,141.10 / 35.0% margin, numbers that hand-check correctly.
  - `/admin/orders/{id}`: a Profit Breakdown card (Sales, Landed Cost Basis, Your Cut), computed
    from each line item's *current* product landed cost × quantity sold. **Verified hidden from
    Staff and visible to Admin** — a dedicated test asserts both, since Orders is one of the two
    sections Staff can otherwise reach, and profit data specifically should not be part of that.
  - `/admin/dashboard` (new route, new nav link, Admin-only — Staff gets a 403, tested): Total
    Sales, Total Freight & Customs Costs, and Total Net Profit over the last 30 days, counting only
    orders with a confirmed `paid` payment status. Verified live in-browser (correctly showed
    R0.00 across the board — genuinely no paid orders in the last 30 days in this dev environment,
    not a broken query) and with a dedicated test seeding real paid/unpaid/out-of-window orders to
    prove the filtering and arithmetic.
- **Image quality filter in the sourcing pipeline**, verified against real downloaded images
  (not mocked): images under 800×800px or with an aspect ratio outside roughly 2:5–5:2 are
  rejected before saving — proven with two new tests that actually download undersized/
  wide-banner test images and confirm they're skipped, not just asserted against fake data.
  Accepted images convert to WebP at quality 85, max width 1200px (was 1600px/quality 82).
  **Watermark/heavy-compression-artifact detection uses a real Gemini vision call** (one more
  multimodal request per image, gracefully skipped — defaults to "accept" — if no API key or on
  any failure, same degrade-gracefully principle as the existing hero-image enhancement step).
  Verified live: called it against a real downloaded product photo and confirmed it returns a real
  `{accept: true}` judgment from the actual API, not a stub.
- **Category hero photography swapped for photos that actually show the equipment**, not generic
  livestock/farm scenery. Real, license-verified swaps for Scales, Ultrasound, RFID, Fencing, and
  Solar & Water — every replacement URL was independently confirmed to return HTTP 200 with real
  image bytes before being committed, and license/credit/source data updated to match. **Also
  replaced the 5 `picsum.photos` (literally random, unrelated stock photos) placeholder URLs in
  `worker/mock_data.json`** with the same real photos. **Honest gap**: the spec's exact photography
  brief (e.g. "stainless steel indicator with bright red LED display," "yellow ISO 11784 ear tags
  with laser-etched numbers visible," "helical rotor submersible pump") describes product-catalog
  photography that Wikimedia Commons — a general-purpose, user-contributed, freely-licensed photo
  library — does not have good free-licensed coverage of for this specific niche B2B equipment.
  What's live now is the closest real, correctly-licensed, on-topic match found for each category,
  not an exact match to every micro-detail in the brief; a couple of entries (e.g. the RFID/scales
  mock-data reuse) are real and passing the new quality filter rather than perfectly on-theme.
  **Note on where this landed vs. the request**: the request named `DatabaseSeeder.php` as the
  file to update — that file only ever seeded Settings/Admin/ExchangeRate data, never product
  images. The actual seam for this is `resources/data/category-images.php` (category tiles/hero)
  and `worker/mock_data.json` (the 5 demo product listings' own photos), both of which are what I
  updated; nothing in `DatabaseSeeder.php` needed to change.
- **Scraper batch ingestion webhook — `POST /api/pipeline/webhook`.** Guarded by a shared
  `X-Pipeline-Secret` header (fails closed with 503 if `PIPELINE_WEBHOOK_SECRET` isn't set, 401 on
  a wrong/missing header — verified by test, not just written). Accepts a batch of raw listings
  (`{products: [{title, category_hint, price_usd, weight_kg, duty_rate, specs_table, images,
  supplier_meta}, ...]}`, capped at 200/request), maps each into the same internal shape
  `worker/src/pipeline.js` already expects, and dispatches a queued `ProcessScrapedBatchJob` — no
  AI vetting ever runs inline on the request. The job reuses the exact same Node pipeline the
  admin's manual sourcing form calls (extracted into `App\Services\SourcingPipelineRunner` so
  there's one code path, not two), sends an admin email ("N new products sourced and awaiting
  review") only when at least one listing actually lands as `pending_review`, and logs (without
  emailing) batches that are all rejects or errors. 16 new PHPUnit tests cover the auth gate,
  payload validation, the pipeline-output parser, and the job's email/no-email/log branches — all
  against a mocked pipeline, the same principle as `PipelineTest`: proving Farmtech's own code
  reacts correctly, not re-testing Gemini's judgment.
  **Verified for real, not just in tests**: posted an actual 134.2 kHz RFID listing (the same one
  from `mock_data.json`) to the running dev server with `QUEUE_CONNECTION=sync` so the whole chain
  ran synchronously in one request — real Gemini API call, real landed-cost math, real DB insert
  as `status=pending_review, is_active=false`, and a real "1 new product sourced and awaiting
  review" email actually appeared in `storage/logs/laravel.log`. Then posted the known-bad 125 kHz
  listing: correctly rejected with the right reasons (wrong frequency, unverified supplier, no
  cert docs) and, since nothing in that batch needed review, no email fired. Confirmed the product
  from the first request renders correctly in the real `/admin/products` Staging Queue UI.
  **Extended beyond the requested field list on purpose**: `category_hint` and `duty_rate` are
  required in the payload even though they weren't in the original title/price_usd/weight_kg/
  specs_table/images/supplier_meta list — landed-cost math needs a real SA import duty rate before
  vetting even runs, and nothing in this codebase invents one (the admin's manual form requires
  the same field, by hand, today). A scraper config is expected to assign both per source/category.
- **Role-Based Access Control.** `User::isAdmin()` / `canAccessAdminPanel()` gate two roles:
  Admin (full access) and Staff (Orders + own Profile only). Enforced via Laravel `Gate`s
  (`manage-catalog`, `manage-users`, `manage-settings`) on the relevant route groups, and the
  admin nav renders links conditionally with `@can`. Verified both by `RoleAccessTest` (7 passing
  tests: staff gets 403 on Users/Settings/Products/Source, 200 on Orders/Profile; admin gets 200
  everywhere) and by logging in as each role in the browser.
- **Rate limiting.** Search and its autocomplete endpoint: 30/min/IP. Cart and checkout: 10/hour/IP
  (shared bucket — hitting the cart limit blocks checkout too, on purpose). Admin login: 5 failed
  attempts locks out for 60s, keyed by `email|ip` so one attacker can't lock out unrelated users on
  the same IP, and a successful login clears the counter. All four behaviors verified in
  `RateLimitingTest` (4 passing tests) by actually driving requests past the threshold and checking
  for 429/lockout, not just reading the config.
- **Full Laravel test suite: 33 passing tests, 137 assertions**, run against SQLite in-memory.
  `php artisan test` — everything from rev. 3 still passes plus the two new files above.
- **Storefront redesign — new visual identity.** Deep ag-tech green (`#143D2B`) + crisp mint
  (`#10B981`) + slate neutrals + amber for alerts/compliance, replacing the earlier khaki/steel
  industrial palette. Inter/Plus Jakarta Sans typography, `font-mono` tabular numbers for ZAR
  prices, glassmorphism cards, `rounded-xl`/`2xl` corners throughout. Admin views needed **zero**
  changes — they resolve color through the `farmtech.*` alias block, which now points at the same
  new hex values.
- **Sticky glass header** with scroll-triggered background/shadow transition, a category dropdown
  with real inline SVG icons per category, and **live search autocomplete** — typing in the search
  box hits the `/search/suggest` endpoint (debounced 300ms) and shows matching products with
  thumbnail, category and price before you even hit Enter. Verified in-browser: typed "scale", got
  a real matching product back with a live thumbnail.
- **Redesigned homepage**: split hero with an auto-rotating product showcase, a 4-pillar trust bar,
  a category grid with icon badges, and a **landed-cost transparency widget** — verified rendering
  real numbers (duty %, VAT %, landed cost, retail price) pulled from an actual product record.
- **Redesigned product page**: sticky gallery with a click-to-zoom full-screen modal, a pricing
  block with a pulsing (`animate-ping`) low-stock badge, and a tabbed spec matrix — Specifications
  / ISO Compliance & ICASA / Delivery & Warranty — replacing the old single stacked layout. All
  three tabs verified clickable and correctly populated in-browser. A mobile-only sticky
  add-to-cart bar appears below `lg:` breakpoint, verified at a real 375px viewport.
- **Cart-action toast.** Add-to-cart still round-trips through the server (no rewrite of
  `CartController` needed), but the flash message now renders as a floating, auto-dismissing toast
  instead of an inline banner, and the header's cart-count badge pops (`animate-pop-in`) when a
  fresh add just happened.
- Frontend build verified clean: `npm run build` succeeds, 55KB CSS / 101KB JS.

---

## ⚠️ Built, but with a real caveat attached

- **The webhook queues a job — it doesn't process it inline unless `QUEUE_CONNECTION=sync`.**
  Production (`.env.example`) defaults to `QUEUE_CONNECTION=database`, which means a worker
  process (`php artisan queue:work`) has to actually be running for `ProcessScrapedBatchJob` to
  ever execute. Nothing in this change starts that worker automatically — it's a process someone
  has to supervise (systemd/supervisor/etc.), same as any Laravel queue deployment. My end-to-end
  verification used `QUEUE_CONNECTION=sync` specifically so the job would run in the same request
  and I could observe the real result immediately.
- **No retry/backoff on a failed batch.** `ProcessScrapedBatchJob::$tries = 1` — if the Node
  process itself crashes (not an individual listing failing vetting, but the whole pipeline dying,
  e.g. Gemini quota exhausted mid-batch), the job fails once and stops; nothing currently
  re-queues it. Individual listing failures within a batch don't have this problem — they're
  caught in `pipeline.js` per-item and reported as `errored`, letting the rest of the batch finish.
- **CI workflow (`.github/workflows/ci.yml`) is written but not verified to actually run.** This
  sandbox has no GitHub Actions runner available, so I could not push and watch it go green. The
  steps mirror exactly what already passes locally (`composer install` → migrate → `php artisan
  test`; `npm ci` → `npm test` in `worker/`), but "passes locally" and "passes in a clean CI
  container" are not the same guarantee until it's actually run once on GitHub.
- **S3/R2 cloud storage support is written to spec, not verified against a real bucket.** No AWS/R2
  credentials are available in this environment. The code path (`FILESYSTEM_DISK=s3` on the Laravel
  side, `WORKER_FILESYSTEM_DISK=s3` on the worker side) is structurally correct and mirrors the
  well-established Laravel Flysystem S3 pattern, but I have not confirmed a real upload/read
  round-trip against AWS S3 or Cloudflare R2.
- **No full cart drawer.** The spec asked for a "floating cart-drawer counter" — I built the
  animated count badge (pops on add) but not a slide-out drawer with a live item preview, to keep
  scope realistic given everything else in this pass. The cart page itself (`/cart`) is unchanged
  and fully functional.
- **The landed-cost widget shows real numbers, not a "vs. local retail" comparison.** The original
  ask described an "ROI widget comparing local retail vs. Farmtech direct-import pricing." There is
  no real data source for South African retail competitor pricing, and fabricating a comparison
  figure would violate this project's standing no-fake-numbers principle. What's built instead is
  an honest breakdown of one real product's actual landed cost — duty rate, VAT rate, landed cost,
  and final price — proving there's no hidden markup, without inventing a "you save RX vs. the shop
  down the road" number that isn't backed by anything real.
- **No "SARS VAT invoice" claim was added**, deliberately. The original trust-badge language
  suggested claiming formal tax invoices are provided; no PDF tax-invoice generation feature
  exists, so the trust bar says "All-In Pricing" / "Secure Checkout" instead — true statements
  about what the site actually does.
- **Per-product warranty terms are not fabricated.** No `warranty` field exists on `Product`, and
  no site-wide warranty policy was ever specified. The Delivery & Warranty tab honestly says terms
  vary by supplier/model and to contact support with the SKU, rather than inventing a "12-month
  warranty" figure that isn't true for every listing.
- **Products sourced before this revision have `NULL` freight/customs/delivery breakdown columns.**
  The new `intl_freight_zar`/`customs_vat_zar`/`domestic_delivery_zar` columns only get populated
  by the pipeline going forward; older rows keep their original (still-correct) `landed_cost_zar`
  and `retail_price_zar`, but contribute `R0` to those specific breakdown fields wherever they're
  summed (e.g. the Dashboard's "Total Freight & Customs Costs" tile) until re-sourced or manually
  backfilled. The Product review page's Profit Breakdown card is unaffected — it always
  live-recalculates from the current formula/settings, never reads the stored breakdown columns.
- **The image watermark/artifact check adds one Gemini call per downloaded image**, on top of the
  one vetting call per listing — a batch with several product photos now makes noticeably more API
  calls than before. It degrades gracefully (defaults to accept) rather than blocking the pipeline
  if that call fails, but the added cost/latency is real, not hypothetical.

---

## 🔧 Still needs to happen

1. Actually run the CI workflow once on GitHub (push to a real remote) to confirm it's green, not
   just structurally plausible.
2. Verify S3/R2 storage against a real bucket once credentials exist.
3. A real cart drawer, if the animated badge isn't enough.
4. A real invite-by-email flow for new admin/staff accounts (unchanged from rev. 3).
5. A finer-grained permission system if Staff needs partial access to catalog/settings beyond the
   current binary Admin/Staff split.
6. Courier Guy / DHL live integration — unchanged, still admin-entered free text.

---

## ❌ Deliberately not implemented (scope decisions, unchanged from earlier revisions)

- Admin credentials in `users`, not `settings` — security decision.
- Payment gateway keys `.env`-only, not editable from `/admin/settings`.
- No automated Alibaba scraper — against their ToS; **Admin → Source New Listing** is the stand-in.
- AI hero images are edits of the real supplier photo, not generations.
- Mail defaults to the `log` driver, not real send.
- `PipelineTest` doesn't re-test Gemini's own AI judgment — the non-deterministic external call is
  tested with real API calls in `worker/test/`, where it belongs.

---

## 🚫 Won't happen without more from you

- Real email delivery, true direct Alibaba import, AI image enhancement actually producing output,
  real payments, proof the Docker path works, live courier tracking, a verified CI run, a verified
  S3/R2 bucket — all need either credentials, a real GitHub remote, or a business decision I can't
  make for you.

---

## Known rough edges

- Same Windows/local-install and dev-server-can-die caveats as before.
- 46+ automated tests total across Laravel (33) and the Node worker (24) — genuinely covered, not
  padding.

---

## Quick reference

- Storefront: `http://localhost:8000` · Admin: `http://localhost:8000/admin/login` ·
  Orders: `/admin/orders` · Your Profile: `/admin/profile` · Users: `/admin/users`
- Run the Laravel test suite: `php artisan test`
- Run worker tests: `cd worker && npm test`
- Build frontend assets: `npm run build`
- Run the sourcing pipeline by hand: `cd worker && node src/pipeline.js --file mock_data.json`
