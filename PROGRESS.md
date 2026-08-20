# Farmtech — Progress & Outstanding Work

Last updated: 2026-08-20 (rev. 9 — cinematic entrance animation, image normalization pipeline,
Industry browse pages, product card/grid overhaul).
What changed since rev. 8: a session-scoped entrance animation (top bar slides down, hero text
staggers in, the showcase card scales in) that plays once per browser tab and never replays on
in-session navigation; every sourced product image now gets a real background/lighting cleanup pass
(extended from hero-only to every image) with a deterministic trim-and-pad-to-1000×1000 fallback
when no AI key is configured — not true alpha-transparency background removal, which the current
stack genuinely can't do (see caveats); a new `/industry/{industry}` browse page so the homepage's
4 industry tiles (replacing the old flat 15-category grid) actually go somewhere real; and the
product card was rebuilt with a standardized `<x-product-image>` studio canvas, a top badge row
(industry + compliance status), and bottom-aligned price/stock/CTA regardless of title length. Rev.
8 added the order tracking portal, PDP compliance badges, WhatsApp CTA, and the industrial reskin;
rev. 7 added the multi-industry expansion, Value-Density Feasibility Engine, and Top-5 Trending
strip; rev. 6 added the arbitrage engine, profit transparency, and image quality gate; rev. 5 added
the scraper webhook; rev. 4 covered RBAC, rate limiting, S3 storage, CI, and the storefront redesign.
See [README.md](README.md) for architecture/setup.

---

## ✅ Working and verified (this session, rev. 9)

- **Cinematic entrance animation, session-scoped.** A global `Alpine.store('intro')`
  (`resources/js/app.js`) reads `sessionStorage.getItem('intro_animated')` once at page-init time
  *before* marking it set — so the very page load that finds it unset still gets `alreadyPlayed:
  false` and plays the sequence, while every subsequent navigation within the same browser tab sees
  it already set and skips straight to the resting state. The top claims bar slides down
  (`animate-slide-down-in`), the hero badge/title/subtitle/CTA stagger in at 0/80/140/200ms
  (existing `animate-reveal-up`, now conditionally applied instead of unconditional), and the
  showcase card scales in from 95%→100% (`animate-scale-in`, a new keyframe) with a hover lift.
  Verified live: `Alpine.store('intro').alreadyPlayed` is `false` with the animation class present
  on first navigation, and `true` with the class absent on the very next navigation in the same tab
  — the "once per visit" behavior actually works, not just looks right on first load.
  **The requested top-bar copy included "0% Duty"** — factually wrong for this business (real SA
  import duty rates of 10–15%+ are charged per HS code and are exactly what the landed-cost engine
  calculates and folds into the price; claiming 0% duty to a customer would be a false statement
  about their actual costs). Shipped as "Import Duty & 15% VAT Already Handled" instead — same
  intent (all-inclusive pricing, nothing extra to pay), accurate claim.
- **Image background normalization, extended and made honest about its real limits.** Every sourced
  image (not just the hero/first one, as of this session) now gets Gemini's real background/lighting
  cleanup pass (`imageEnhance.js`, renamed `enhanceHeroImage` → `enhanceProductImage`) targeting a
  clean white/light-grey studio background and centered crop on a 1000×1000 canvas. When no Gemini
  key is configured, or the AI call fails, a new deterministic fallback
  (`worker/src/lib/imageNormalize.js`) trims uniform-color borders (sharp's own edge detection) and
  pads the result onto a plain white 1000×1000 canvas — a real, honest improvement (dead whitespace
  removed, consistent framing) but explicitly **not** true background removal: sharp has no
  subject-matting model, so a photo with a complex/busy background keeps that background, just
  cropped and centered. You chose this approach (extend the existing proven AI step + a deterministic
  fallback) over adding a dedicated background-removal service/API, which would have been a real new
  cost/infra dependency. 3 new tests (`imageNormalize.test.js`) plus updated coverage in
  `imagePipeline.test.js` confirming the fixed 1000×1000 output; all 34 worker tests pass.
- **`/industry/{industry}` browse pages — the homepage's 4 industry tiles actually go somewhere.**
  New `Product::scopeIndustry()`, a shared `ProductController::filteredAndSorted()` helper (same
  sort/in-stock logic as the category page, extracted rather than copy-pasted), and
  `storefront/products/industry.blade.php` — same sort/filter control bar and sticky trending strip
  as the category page, plus a pill row of every category *in* that industry. The header mega-menu's
  industry group headers are now real links to these pages too. **Found and fixed a real bug while
  building this**: `Industry::categories()` used `array_filter()` without re-indexing, so
  `$industry->categories()[0]` (used for the homepage tile icon) threw "Undefined array key 0" for
  every industry except Agriculture (the only one whose filtered categories happen to start at
  index 0 in the original enum-declaration order) — this was latent in rev. 7/8's code too, just
  never exercised because nothing had indexed into position 0 before. Fixed with `array_values()`;
  4 new tests (`IndustryPageTest`).
- **Product card rebuilt**: a new `<x-product-image>` component (standardized
  `aspect-square bg-slate-50 border ... object-contain` studio canvas, used by the card and — where
  a fallback is needed — the same category-icon-on-dot-grid treatment from rev. 8) replaces the old
  `object-cover` full-bleed thumbnail; a top badge row shows the industry badge and compliance
  status (moved off the rotated corner stamp, which stays as-is on the PDP gallery — unrelated,
  untouched); the card is `h-full flex flex-col` so grid rows align regardless of title length, with
  price/stock/CTA pinned to the bottom via `mt-auto`; and a "View Specs" affordance replaces the
  bare card-as-link with an explicit visual call to action. One literal spec detail substituted:
  `border-slate-150` isn't a real Tailwind shade (the default slate scale has no 150 step) — used
  `border-slate-200`, the closest real token, instead.
- **Photography**: not re-searched this session. The spec's isolated/transparent-background studio
  photography brief is even less likely to exist as free-licensed Commons content than the
  "shows the equipment" brief already confirmed unavailable in rev. 7/8 — isolated product-catalog
  photography is typically commercial/proprietary stock content, not the kind of thing a
  general-purpose freely-licensed image library carries. The new AI enhancement pass (above) is the
  practical answer to "make the photos look more consistent," applied to whatever real photos exist.
- **Full regression check**: 89 Laravel tests passing (up from 85; +4 new), 34 Node worker tests
  (up from 31; +3 new), `npm run build` clean, verified live in-browser at desktop and 375px mobile
  (no horizontal overflow, no real console errors beyond the pre-existing offline-Google-Fonts
  warnings this sandbox always shows).

---

## ✅ Working and verified (this session, rev. 8)

- **Customer order tracking portal — `/track`.** Guest lookup by order number + email (the only
  credential a real customer has — there are no customer accounts in this app), POST-only so the
  email never lands in a URL, browser history, or referrer header, rate-limited to 10/hour/IP
  (`RateLimiter::for('track', ...)`, same bucket size as cart/checkout). A 5-stage stepper
  (`OrderStatus::trackingStageIndex()`/`trackingStageLabels()`) maps the real order status —
  Cancelled gets a dedicated banner instead of a partially-lit stepper, since there's no honest
  position for a stopped order on a linear progress bar. The delivery address shows city/province/
  postal only (street lines withheld, per your answer on the design question) and the hardware
  manifest is pulled from the real `order->items`. **Courier tracking links** (`App\Services\
  CourierTrackingLinks`) only link to a carrier's real tracking page — Courier Guy and DHL SA URLs
  were confirmed via live web search before being hardcoded; RAM and DawnWing are best-effort from
  third-party listings and worth a manual spot-check before relying on them. An unrecognized courier
  still shows the tracking number as plain text rather than a guessed/broken deep link. 11 new tests
  (`TrackOrderControllerTest`) cover the happy path, wrong-email rejection, case-insensitive email
  matching, address masking, the cancelled banner, both courier-link branches, and the rate limit.
- **PDP compliance badge strip — data-driven, not a fixed set.** `<x-compliance-badges>` renders
  only badges the pipeline actually verified for *that specific product* — ICASA status, power/plug
  check, ISO 11784/11785 (RFID category only), battery-transport cert, and an IP rating pulled from
  a real spec-sheet row via a regex on the *value* (`/\bIP\s?\d{2}\b/`) rather than a specific
  `spec_key` string, since the AI-generated key wording varies by category ("IP Rating", "Enclosure
  Rating") but a real IP rating value always looks like IP54/IP65/IP67 — verified live against a
  real approved product whose spec sheet used "Enclosure Rating" as the key, confirming the
  value-based match catches it where a key-based match would have missed it. A product with fewer
  verified attributes shows fewer badges — there is no "always show 4" fallback. Moved out of the
  buried Compliance tab to directly under the price card. 5 new tests (`ComplianceBadgesTest`).
- **WhatsApp specialist CTA**, gated entirely behind a new admin-configurable `support_whatsapp`
  setting (`/admin/settings`, same key-value pattern as the existing margin/freight settings) — the
  floating button (desktop bottom-right, mobile above the sticky add-to-cart bar), the inline PDP
  card, and the footer badge all render *only* when a real number is configured, never a dead
  `wa.me/` link. The prefilled message includes the product's title and SKU. Verified both states
  live (absent when unset, present with a working `wa.me/` link once set) and by test.
- **Industrial reskin.** The site's glassmorphism look (blurred, translucent surfaces) was
  centralized in exactly two CSS component classes — `.glass-card` and `.glass-header`
  (`resources/css/app.css`) — so redefining those two (solid background + hairline `border-slate-200`,
  no blur) cascaded the new look through the header, hero showcase card, trending strip, and PDP
  price card automatically, without a per-view rewrite. The homepage and category-page hero blocks
  — previously full-bleed `bg-brand-950` dark-green/black, stacked directly on top of an equally dark
  trending strip — moved to a light `bg-slate-50` canvas; the trending strip itself moved from dark
  glass cards to `bg-slate-100/70` with white hairline-border cards matching the catalog grid's
  existing `_card.blade.php` treatment, rather than inventing a third visual style. The footer stays
  intentionally dark (light body + dark footer is a standard, non-clashing pattern) — flagged as a
  design choice in my proposal, not silently decided. Verified live: `npm run build` clean, full
  Laravel suite green, and manually driven in-browser (computed styles confirm `backdrop-filter:
  none` and solid white backgrounds post-change; no horizontal overflow at a 375px mobile viewport).
- **Category page rebuild.** Real breadcrumb, an industry-grouped sibling-category pill switcher
  (`Industry::categories()` — e.g. the Scales page shows Scales/Ultrasound/RFID/Smart Irrigation/
  Accessories, not all 15 flat), and a working control bar: `?sort=` (newest/price_asc/price_desc/
  popularity — popularity reuses the exact same real-units-sold `withSum` logic as the Trending
  strip, never a fabricated score) and `?in_stock=1`, replacing the old static "N products" text
  with "Showing 1–12 of N items". Deliberately scoped to an in-stock toggle only, not a price-range
  filter — sort-by-price already covers positioning by price, and there's no attribute-facet data
  model to build a fuller filter system against yet. 6 new tests (`ProductGridControlsTest`).
- **Broken/missing product images now show the category's own icon** on a subtle dot-grid
  background (`<x-product-image-fallback>`) instead of a generic "No image" gray box — wired into
  the catalog grid card and the PDP gallery. The trending strip's own image-absent state was left as
  its existing simple text fallback rather than wiring per-category icons through its Alpine/JS
  product-array pipeline — a real scope trim, not an oversight (see caveats).
- **Footer extracted to `<x-footer>`** with the four requested columns: Brand/WhatsApp badge, all
  15 category links, Import/Customs/Tracking (linking to `/track` and to real anchored sections of
  the Terms page rather than inventing three near-duplicate content pages), and Legal. 5 new tests
  across `PolicyPagesTest`.
- **Three CPA-compliant legal pages** (`/policies/returns`, `/policies/terms`,
  `/policies/icasa-compliance`) — every factual claim on them is real and already true elsewhere in
  this codebase (14-day CPA defect-return window under section 56, all-inclusive VAT/duty pricing,
  7–12 business day lead times, the actual ISO 11784/11785 + ICASA vetting process described
  end-to-end). **Honest gap, not fabricated content**: you told me there is no registered legal
  entity behind this site yet, so the registered company name, CIPC registration number, and
  physical address are left as clearly bracketed placeholders (`[Company Registration Pending]`
  etc.) rather than invented — presenting fake entity details on a page about a customer's actual
  legal consumer rights would be actively misleading, not just an "honest gap" in the way a stock
  photo substitution is. The returns policy references the National Consumer Commission generically
  as the CPA regulator rather than naming a specific ombud membership that doesn't exist.
- **Photography re-investigated, no viable improvements found.** Searched specifically against your
  briefs (red-LED indicators, yellow ISO ear tags, green-beam laser levels) — the one promising lead
  (a "134.2 kHz RFID animal tag" file) turned out to be an implant chip photographed next to an
  injector, not an ear tag, so using it would have been a *worse* match than what's already live
  despite the tempting filename. Same conclusion as rev. 6: Wikimedia Commons doesn't have
  good free-licensed coverage of this specific B2B equipment niche. `category-images.php` is
  unchanged this session rather than force a weak substitution.
- **Full regression check**: 85 Laravel tests passing (up from 58; +27 new), 31 Node worker tests
  unchanged and still passing, `npm run build` clean.

---

## ✅ Working and verified (this session, rev. 7)

- **Multi-industry catalog expansion.** New `App\Enums\Industry` enum (`agriculture`,
  `construction`, `industrial_logistics`, `solar_power`) groups the now-15 `ProductCategory` cases
  — `category` is a plain string column, not a DB enum, so no migration was needed. Each new
  category (Smart Irrigation, Laser Levels, Moisture Meters, Rebar Detectors, Theodolites, Platform
  Scales, Fleet Trackers, Industrial RFID, MPPT Controllers) got its own icon, HS-code hint, hero
  photo (real, license-checked Wikimedia Commons sources — two categories, Rebar Detectors and
  Platform Scales, reuse the closest-real-match photo Commons has rather than an exact-topic shot,
  same honest-gap principle as rev. 6's photography pass) and category-specific vetting ruleset in
  `vettingPrompt.js` (e.g. fleet trackers always require ICASA review since they always carry a
  cellular/GPS radio; industrial RFID is explicitly told not to apply the 134.2 kHz livestock
  frequency rule).
  **Header navigation was the one place this expansion actually broke usability, and has been
  fixed**: the desktop category dropdown was a flat single-column list that would have rendered 15
  rows (~630px) with no grouping despite `Industry` existing specifically for this; it's now a
  4-column mega-menu grouped by industry (`resources/views/layouts/storefront.blade.php`). The
  mobile horizontal nav got the same grouping with `|` separators between industries. Verified live
  in-browser at both desktop and 375px mobile viewports — mega-menu opens with the correct 4
  columns/15 items, mobile nav renders the same grouping.
- **Value-Density Feasibility Engine**, replacing the flat $50-minimum-base-value rule from rev. 6.
  `worker/src/lib/valueDensityFilter.js` (Node, the actual pipeline enforcement point) and
  `app/Services/ValueDensityEvaluator.php` (PHP mirror for the admin margin-slider breakdown, kept
  in sync deliberately, same principle as `LandedCostCalculator`) both implement: reject if
  international freight exceeds 35% of base cost AND net profit is under R1,500 (a heavy/cheap item
  is only worth it if the absolute profit is still substantial); reject if net profit is under R500
  regardless. This fixes a real gap in the old rule — a light, high-value item under $50 (a small
  sensor module) no longer auto-rejects just for being inexpensive, while a heavy, low-value item
  (cast steel weights) still dies on freight cost. The AI-judged pricing half moved from a binary
  competitive/uncompetitive call to `local_price_delta_pct`, an estimated percentage below/above
  typical SA dealer pricing — `pricing_verdict` is now "competitive" only at 20%+ below local, not
  merely "not obviously worse." 9 new tests (5 Node, 4 PHP) cover both engines against the same
  worked examples so they can't silently drift apart; all 31 worker tests and 58 Laravel tests pass.
- **"Top 5 Trending" strip.** `Product::scopeTrending()` ranks by real `order_items.quantity` sums
  (`withSum`), falling back to newest-first when nothing's sold yet — never a fabricated
  view/conversion metric. Renders on the homepage and sticky (collapsible) on category and search
  pages via a shared `View::composer` in `AppServiceProvider` so the query runs once per request,
  not duplicated per controller. Verified live: renders the real 4 approved products (there are
  only 4 in this dev DB) with correct industry badges, prices, and stock status; the Quick View
  modal opens and links through to the real product page.

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

- **Image "background normalization" is real border-trim-and-pad, or real AI cleanup with a key —
  never true alpha-transparency background removal.** The spec asked for images "centered on a
  clean transparent/white 1000x1000 canvas," which implies subject-matting (isolating the product
  from its background). Neither sharp (deterministic resize/crop library) nor the existing
  Gemini-image-edit step can do that — Gemini's image edit returns a new flattened photo with a
  repainted background, not an alpha-channel cutout, and sharp's `.trim()` only removes uniform-color
  borders, not a complex photographic background. This was a deliberate choice you made between two
  real options (extend the existing step vs. add a paid/self-hosted matting service) — not a
  silent shortfall, but worth restating here since "clean canvas" and "true transparency" read as
  the same thing in the original ask and they are not the same feature.
- **The legal pages are not launch-ready as written** — the registered company name, CIPC
  registration number, and physical address are bracketed placeholders, since you confirmed there
  is no registered entity behind this site yet. Everything else on those pages (the CPA return
  window, pricing/VAT facts, ICASA process description) is real and accurate today; only the entity
  identity block needs filling in before this is genuine legal content a customer could rely on.
- **RAM and DawnWing courier tracking links are best-effort, not independently confirmed.** Courier
  Guy and DHL SA URLs were verified via live web search results showing the actual page title/URL;
  RAM (`track.ramgroup.co.za`) and DawnWing (`dawnwing.co.za/.../online-parcel-tracking/`) came from
  third-party aggregator summaries rather than a direct fetch of the page itself (one direct fetch
  attempt timed out). Worth a manual click-through before relying on them in production — an
  unrecognized/wrong courier name still degrades gracefully to plain text, never a broken link.
- **No price-range filter on category pages**, only sort-by-price and an in-stock toggle. The
  original design sketch mentioned a price-range filter alongside in-stock; I trimmed it as YAGNI
  once building it — sort-by-price already lets a buyer find the cheap or expensive end of a
  category, and there's no faceted-attribute data model yet to justify a fuller filter system.
- **The trending strip's "no image" state still shows plain text, not the new per-category SVG
  fallback.** The fallback component was wired into the catalog grid card and PDP gallery (both
  server-rendered Blade), but the trending strip's product cards are built from a JSON array handed
  to Alpine.js client-side — threading a per-category icon through that pipeline for a rare edge
  case (an approved, trending product with zero images) wasn't worth the added complexity this pass.
- **The Industry grouping only reaches the header nav, not the homepage tile grid or the admin
  category filter.** The header dropdown/mobile nav are the two places a flat 15-item list was
  actually unusable, so those got grouped by `Industry`. The homepage "Shop by Category" section
  (a 2/3-column photo-tile grid) and the admin products list's category `<select>` filter still
  list all 15 categories flat, ungrouped — both remain fully functional (a bigger grid, a longer
  dropdown), just not industry-sectioned like the nav now is. Left as-is rather than a speculative
  redesign of sections that weren't actually broken.
- **`mock_data.json` and the sourcing pipeline's live-fire verification were not re-run against the
  10 new categories.** Rev. 6's real end-to-end pipeline runs (RFID listings) still pass, and the
  Node/PHP unit tests cover every new category's vetting rules and the Value-Density engine, but no
  new category beyond the original 5 has been proven against a real Gemini vetting call the way RFID
  was in rev. 5/6 — that would need real listing data for e.g. a laser level or fleet tracker, which
  doesn't exist in this dev environment.
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
6. Live courier *integration* (auto-fetched status/ETA) is still unbuilt — rev. 8 added a link to
   the carrier's own tracking page once the admin enters `courier_name`/`tracking_number` by hand,
   which is real progress but not the same as an API integration.
7. Real registered-company details (name, CIPC number, physical address) for the three legal pages
   — currently bracketed placeholders, deliberately not invented (see caveats above).
8. A real WhatsApp number in `/admin/settings` — the CTA is fully built and tested but hidden until
   an admin configures one.
9. RAM/DawnWing tracking URLs should be manually spot-checked (see caveats above).
10. Product photography for the 10 categories added in rev. 7 is still the closest-real-match
    Wikimedia Commons had, not purpose-shot product photography — re-investigated in rev. 8 with
    no improvement found; a real fix likely needs either purchased stock photography or actual
    supplier product photos, not another Commons search.
11. True background removal (alpha-transparency subject cutout) if the "isolated on transparent
    canvas" look genuinely matters beyond what the current AI-cleanup/deterministic-trim pass
    delivers — needs a real matting model or paid API, a follow-up decision, not a code gap.
12. `worker/mock_data.json` and `resources/data/category-images.php` were not re-generated against
    the new 1000×1000 normalization pipeline — existing entries are untouched real images, just not
    yet re-processed through the new pipeline path to see the consistent-canvas treatment applied
    to them specifically (new listings sourced going forward get it automatically).

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
- 123 automated tests total across Laravel (89) and the Node worker (34) — genuinely covered, not
  padding.

---

## Quick reference

- Storefront: `http://localhost:8000` · Browse by industry: `/industry/{agriculture|construction|
  industrial_logistics|solar_power}` · Track an order: `/track` · Returns policy:
  `/policies/returns` · Terms: `/policies/terms` · ICASA compliance: `/policies/icasa-compliance` ·
  Admin: `http://localhost:8000/admin/login` · Orders: `/admin/orders` · Settings (incl. WhatsApp
  number): `/admin/settings` · Your Profile: `/admin/profile` · Users: `/admin/users`
- Run the Laravel test suite: `php artisan test`
- Run worker tests: `cd worker && npm test`
- Build frontend assets: `npm run build`
- Run the sourcing pipeline by hand: `cd worker && node src/pipeline.js --file mock_data.json`
