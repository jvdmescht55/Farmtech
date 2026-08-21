# Farmtech — Production Readiness Checklist

Written 2026-08-21, alongside the rev. 14 production-readiness pass (see PROGRESS.md). This is a
real, code-grounded checklist — every item below reflects what's actually in this codebase today,
not generic boilerplate. Items marked **Done** were completed as part of that pass; everything else
needs a real decision, credential, or infrastructure step this environment doesn't have.

---

## 1. Queue worker (needed for the scraper webhook and any queued mail)

**Gap**: `docker-compose.yml` has no queue-worker service, and `QUEUE_CONNECTION=database` (the
`.env.example` default) means `ProcessScrapedBatchJob` and any `ShouldQueue` mail just sit in the
`jobs` table forever unless something runs `php artisan queue:work`. Nothing currently does.

**To do before going live**, run a supervised `queue:work` process. Example supervisor config
(`/etc/supervisor/conf.d/farmtech-worker.conf` on the deploy host):

```ini
[program:farmtech-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/var/www/html
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/queue-worker.log
stopwaitsecs=3600
```

`--max-time=3600` makes supervisor cycle the worker hourly so it picks up new code after a deploy
without a manual restart step. For the Docker path, add a `worker-queue` service to
`docker-compose.yml` running the same command against the `laravel.test` image, or run
`queue:work` via a Kubernetes CronJob/Deployment if that's the target platform instead.

## 2. Transactional email — **partially done**

- **Done**: `config/mail.php` already defaults to the `log` driver and falls back cleanly with no
  credentials set. `.env.example` now documents the exact Postmark and Resend SMTP settings (both
  work through the existing generic `smtp` mailer — no code change needed, just real credentials).
- **Done**: All 5 real Mailables (`NewOrderAdminAlertMailable`, `OrderPlacedCustomerMailable`,
  `OrderStatusUpdatedCustomerMailable`, `ScrapedBatchSummaryMailable`, `SupportRequestMailable`)
  are covered by `tests/Feature/MailableRenderTest.php`, proving they compile without a broken
  Blade/markdown reference.
- **Still needed**: real Postmark/Resend credentials in production `.env`, and one real send
  verified against an actual inbox — `MailableRenderTest` proves the *template* compiles, not that
  delivery through a real provider succeeds (SPF/DKIM records, sender reputation, etc. are outside
  what this repo can verify).

## 3. Database indexing — **done this pass**

Added `database/migrations/2026_08_21_082625_add_production_readiness_indexes.php`:
- `products (status, is_active)` — the exact pair `Product::scopeStorefrontVisible()` filters on for
  nearly every storefront query; the pre-existing `(status, category)` index didn't cover it.
- `product_specs (spec_key)` — used directly in category-filter `whereHas()` calls and the facet
  builder's `GROUP BY`; previously only `(product_id, spec_group)` existed.
- `orders (status)` and `orders (payment_status)` — the admin order list and dashboard filter on
  both; previously the only index on `orders` was the `order_number` unique constraint.

No other missing indexes found — `order_items.order_id`/`product_id` and
`product_bundle_items.product_id`/`companion_product_id` already get an implicit index from their
`foreignId()->constrained()` FK declarations.

## 4. Environment variables — real checklist, not a generic list

Every one of these is already documented with real context in `.env.example`; this is the subset
that's a placeholder/sandbox value today and must be replaced before go-live:

| Variable | Current (.env.example) | Needed for production |
|---|---|---|
| `APP_KEY` | blank | `php artisan key:generate` on the real deploy |
| `APP_ENV` / `APP_DEBUG` | `local` / `true` | `production` / `false` — debug-mode stack traces leaking to real users is a real security gap |
| `DB_*` | Docker-compose sandbox creds | real managed DB credentials |
| `GEMINI_API_KEY` | blank | real key — the sourcing pipeline can't vet anything without it |
| `PIPELINE_WEBHOOK_SECRET` | blank (endpoint fails closed at 503) | a real shared secret if the scraper webhook is used |
| `USD_ZAR_API_KEY` | blank (falls back to last stored rate) | real key for a live forex feed, or accept the stale-rate fallback |
| `PAYFAST_*` / `OZOW_*` / `YOCO_*` | blank/sandbox | real merchant credentials — no live payment can process without these |
| `ADMIN_PASSWORD` | `change-me-immediately` | a real password, changed immediately, exactly as the placeholder says |
| `MAIL_*` | `log` driver | real Postmark/Resend credentials, see §2 |
| `FILESYSTEM_DISK` + `AWS_*` | `local` disk | `s3` + real S3/R2 credentials if local disk storage won't survive a redeploy on the target host |
| `APP_FORCE_HTTPS` | `false` | `true` behind a real TLS-terminating load balancer |

## 5. CI — **verified as far as this sandbox can**

`.github/workflows/ci.yml` was re-read in full this pass: both jobs (`laravel`: PHP 8.2, `composer
install` → `key:generate` → `npm ci && npm run build` → `php artisan test`; `worker`: Node 20,
`npm ci && npm test`) match exactly what's been run locally throughout this project, and
`composer.lock`/`package-lock.json`/`worker/package-lock.json` all exist for `npm ci`/`composer
install --no-interaction` to work. **What this sandbox genuinely cannot do**: actually push to a
real GitHub remote and watch the Actions run go green — that's still unverified in the sense of "a
real CI run happened," only in the sense of "the workflow file is structurally correct and mirrors
tested local commands."

## 6. Courier tracking deep-links — **attempted, blocked, left honest**

The ask was to deep-link tracking pages with the real `tracking_number`, not just link to each
carrier's tracking-portal homepage (today's behavior). Live lookups against both
`thecourierguy.co.za` and `dhl.com` were attempted this pass to find the real query-parameter
format; both returned bot-protection failures (403 / connection reset) rather than usable content.
**Deliberately not guessed** — `CourierTrackingLinks`'s own code comment already states the
reasoning: an unverified deep-link parameter would silently produce a broken or wrong-shipment link,
which is worse than today's safe (if less convenient) homepage link. This remains a real "still
needs to happen" item — either a manual one-time lookup of each carrier's real format, or (better,
long-term) a real courier tracking API integration instead of URL-guessing.

## 7. Things this checklist deliberately does NOT tell you to do

- Enable a CDN — no evidence this app is at a traffic scale where it matters yet.
- Add application monitoring/APM — a real product decision (which vendor, what budget), not a
  code-level readiness gap.
- Add a staging environment — infrastructure/process decision, not something a checklist item can
  create.

These are left out on purpose rather than padded in as generic "best practice" filler.
