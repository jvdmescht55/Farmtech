# Farmtech — Progress & Outstanding Work

Last updated: 2026-08-20 (rev. 5 — scraper batch ingestion webhook). What changed since rev. 4:
a `POST /api/pipeline/webhook` endpoint that lets a scheduled scraper (Apify or similar) submit a
batch of raw listings and have them run through the exact same AI vetting + landed-cost pipeline
the admin's manual "Source New Listing" form already uses, queued and emailed to the admin when
something needs review. Rev. 4 covered role-based access control (Admin vs Staff), rate limiting,
S3/R2-compatible cloud storage support, a GitHub Actions CI workflow, and the full storefront
redesign. See [README.md](README.md) for architecture/setup.

---

## ✅ Working and verified (this session)

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
