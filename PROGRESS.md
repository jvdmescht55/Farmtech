# Farmtech — Progress & Outstanding Work

Last updated: 2026-08-20 (rev. 4 — operational hardening + storefront redesign). What changed
since rev. 3: role-based access control (Admin vs Staff), rate limiting, S3/R2-compatible cloud
storage support, a GitHub Actions CI workflow, and a full visual redesign of the storefront
(new color system, sticky glass header with live search, redesigned homepage and product page,
motion/micro-interactions). See [README.md](README.md) for architecture/setup.

---

## ✅ Working and verified (this session)

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
