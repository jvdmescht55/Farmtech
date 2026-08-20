# Farmtech — Progress & Outstanding Work

Last updated: 2026-08-20. This is the honest status doc — what's actually working (verified
in-browser or by test, not just written), what's built but unproven, what's still on the
backlog, and what's out of scope on purpose or blocked on something only you can do.

For architecture/setup, see [README.md](README.md). This file is about *what's left*.

---

## ✅ Working and verified

Everything below has actually been run and checked, not just written:

- **Storefront**: home (hero slider, 6 category tiles, trust bar), category pages, product
  detail pages (specs, import/duty transparency, compliance summary), cart, checkout form (SA
  province/postal validation), keyword search — all confirmed rendering correctly against real
  MySQL data, no console errors on the latest pass.
- **Admin**: login, staging queue (status/category/verdict filters), review panel (compliance
  sidebar, live financial breakdown, working margin slider), quick edit, approve, reject +
  blacklist supplier, settings page, **Source New Listing** (runs the real pipeline from a web
  form, no terminal needed).
- **Sourcing pipeline** (`worker/`): forex fetch with DB fallback, landed-cost calculator, Gemini
  AI vetting (structured JSON, category-specific rules), image download + MIME validation + webp
  conversion, MySQL and SQLite DB drivers. **24 automated tests, all passing**, including a real
  network image download/convert and a real Gemini API call.
- **6 product categories**, each with a real HS code and AI vetting rules: scales, ultrasound,
  RFID, accessories, electric fencing, solar water pumps. HS codes and NRCS/ICASA requirements
  were researched, not guessed.
- **Design**: distinct visual identity (not default Tailwind look), real Wikimedia photography
  with attribution, purposeful motion (hero slider, scroll-reveal, hover states).

---

## ⚠️ Built, but not fully proven — needs your action to actually verify

- **Payment gateways (PayFast, Ozow, Yoco)**: signature generation, webhook verification, and the
  checkout handoff are all coded, but **never tested against a real sandbox account**. Right now
  checkout will show "gateway not configured" for all three, because there are no real
  credentials in `.env`. You'll need to sign up for each and drop the keys in before this can be
  proven end-to-end.
- **AI hero-image enhancement**: the code path works (it's real Gemini image-editing, not
  fabrication — see README), but your Gemini API key's free tier has **zero quota for image
  models** (confirmed live, not assumed). It silently falls back to the original photo. Needs you
  to enable billing on the Google Cloud project tied to that key.
- **Docker path** (`docker-compose.yml`, `setup.sh`/`setup.bat`): written to spec, but this whole
  build happened in a sandbox with no Docker available, so it was never actually run. Everything
  was proven instead against a PHP/MariaDB install done directly on the dev machine (documented
  in the README as a workaround). **Someone needs to run `./setup.sh` on a real machine with
  Docker Desktop** before you can trust that path.
- **Live USD/ZAR forex fetch**: the code and fallback logic are tested, but no
  `USD_ZAR_API_KEY` was ever set, so it's only ever been exercised via the fallback (static DB
  rate), never a real live fetch.

---

## 🔧 Still needs to happen

Roughly in the order I'd tackle them:

1. **Admin order management — doesn't exist yet.** Checkout creates real `orders`/`order_items`
   rows, but there is no `/admin` page to view, update, or fulfill them. Right now a customer
   could check out and the order would just sit in the database with no one able to see it from
   the UI. This is the single biggest gap for calling this a "working" store.
2. **Order confirmation emails.** No mail driver is configured (`config/mail.php` doesn't exist),
   and nothing calls `Mail::send`. A customer gets a browser success page and nothing else —
   no receipt, no "your order is on the way."
3. **No automated tests for the Laravel app.** The Node worker has 24 tests; the PHP side has
   zero. Everything on the Laravel side was checked by hand in-browser this session, which proves
   it worked *then* but won't catch a regression later.
4. **No admin user management.** One hardcoded admin account from a seeder. No way to invite a
   second staff member or rotate the password without editing `.env` and re-seeding.
5. **No real inventory tracking.** `stock_status` is just `in_stock`/`pre_order` — there's no
   quantity field, so nothing stops overselling a single unit to five customers at once.
6. **No admin audit trail.** Approve/reject/edit actions aren't logged anywhere — you can't
   answer "who approved this listing and when" after the fact.
7. **Courier Guy / DHL integration is a total placeholder.** The `.env.example` has config keys
   for both, but grep the codebase — nothing ever reads them. No shipping label, no tracking
   number, no webhook. It's a name in a config file, not a feature.
8. **Rate limiting / abuse protection** on search, cart, and checkout is whatever Laravel ships
   with by default — never reviewed or tuned for this app specifically.
9. **Image storage is local disk** (`public/uploads/products`). Fine for one server; will not
   survive a multi-server or serverless deploy without moving to S3-compatible storage first.

---

## ❌ Deliberately not implemented (scope decisions, not oversights)

These were conscious calls, each explained in more depth in the README:

- **Admin credentials live in the `users` table**, not the `settings` key-value table the
  original brief described — storing login credentials in a generic config table has no hashing
  convention and no framework auth integration. Real security smell, so I didn't do it.
- **Payment gateway API keys are `.env`-only**, not editable from `/admin/settings` — a web form
  writing secrets into the plain `settings` table would undo the point of keeping them out of
  `users` in the first place.
- **No automated Alibaba/Made-in-China scraper.** Their Terms of Use explicitly prohibit
  automated retrieval. **Admin → Source New Listing** is the legitimate stand-in: you paste in
  what you see on the listing, and the real AI vetting/costing pipeline runs on it.
- **AI hero images are edits of the real supplier photo**, not text-to-image generations. A
  generated image might not depict the exact unit a customer receives — for physical imported
  goods that's a returns/trust problem, not just a style choice.

---

## 🚫 Won't happen without more from you

Things I can't finish myself because they require you to do something outside this codebase:

- **True direct Alibaba import** — needs you to register for Alibaba's Open Platform API
  (openapi.alibaba.com) and get approved. That's a business relationship only you can establish;
  I'll build the real integration the moment you have App Key/Secret.
- **AI image enhancement actually producing output** — needs you to enable billing on the Google
  Cloud project behind your Gemini key.
- **Real payments** — needs PayFast/Ozow/Yoco merchant sign-ups and real credentials.
- **Proof the Docker path works** — needs a run on a machine that actually has Docker Desktop.

---

## Known rough edges

- This dev environment has PHP 8.2, Composer, and MariaDB installed directly via `winget` — a
  sandbox-specific workaround, not how you should run this day to day. Use `./setup.sh` /
  `setup.bat` with Docker on your own machine.
- If you ever run **Source New Listing** on Windows *outside* Docker, you may hit the
  `SystemRoot`-propagation / `.env`-shadowing issues documented in the README — already fixed in
  `SourceController`, but worth knowing about if something like it resurfaces elsewhere.
- Test coverage is lopsided: the Node worker is well-tested (24 tests), the Laravel app has none.

---

## Quick reference

- Storefront: `http://localhost:8000` · Admin: `http://localhost:8000/admin/login`
- Admin login: `ADMIN_EMAIL` / `ADMIN_PASSWORD` from `.env` (seeded on first migrate)
- Run the sourcing pipeline by hand: `cd worker && node src/pipeline.js --file mock_data.json`
- Run worker tests: `cd worker && npm test`
- Full setup from scratch: `./setup.sh` (or `setup.bat` on Windows) — needs Docker Desktop
