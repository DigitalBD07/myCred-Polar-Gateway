# myCred Polar.sh Points & Subscriptions

Purchase myCred points or sell recurring subscriptions with Polar.sh — fully verified webhooks, idempotent crediting, a customer self‑serve cancel flow, and an admin “Subscribe” dashboard for MRR/ARR and subscriber insights.

- WordPress plugin
- Version: 3.5.0
- License: GPL-2.0-or-later
- Requires: WordPress 5.8+, PHP 7.4+, myCred (free/core)
- Polar features: Live/Sandbox, PWYW and fixed pricing, Customer Portal cancel, Subscriptions sync

> If you’re here from the GitHub release: the plugin file is a single PHP file. Copy it to wp-content/plugins/mycred-polar-points/mycred-polar-plugin.php and activate.

---

## Table of contents

- Features
- Screenshots
- Requirements
- Installation
- Quick start
- Configuration (Polar + WordPress)
- Shortcode
- Admin pages
- Webhooks and security
- Subscriptions dashboard (Subscribe page)
- Cancel flow
- Database schema
- WP actions and endpoints
- Troubleshooting
- FAQ
- Changelog
- Contributing
- License

---

## Features

- One‑time points purchase (PWYW or fixed product)
- Recurring subscriptions for automatic points (per cycle)
- Robust webhook verification (Svix/Polar Standard Webhooks)
  - Modes: strict, api_fallback, disabled (dev)
- Idempotent awarding (safe against retries)
- Transaction logs (last 100)
- Success page fallback credits (in case webhook is delayed)
- Subscription list for end users + cancel at period end
- Admin Subscribe dashboard:
  - KPIs (Active, Canceling, Canceled last 30 days, MRR/ARR)
  - Latest subscriptions table
  - “Sync from Polar” (active + canceled) and CSV export
- Live/Sandbox support and test connection

---

## Screenshots

Add these after you install:

- docs/img/settings.png — Plugin settings
- docs/img/shortcode.png — Front‑end purchase/subscription form
- docs/img/subscribe-dashboard.png — Admin Subscribe dashboard

You can use placeholders and replace later:

/docs/img/settings.png /docs/img/shortcode.png /docs/img/subscribe-dashboard.png

text


---

## Requirements

- WordPress 5.8+
- PHP 7.4+ (8.x supported)
- myCred plugin active
- Polar organization access token with scopes:
  - products:read
  - checkouts:write
  - orders:read
  - subscriptions:read
  - customer_sessions:write
  - subscriptions:write

---

## Installation

1) Create the folder:

wp-content/plugins/mycred-polar-points/

text


2) Copy the plugin file (from this repo) to:

wp-content/plugins/mycred-polar-points/mycred-polar-plugin.php

text


3) Activate “myCred Polar.sh Points & Subscriptions NoV_8” in WordPress > Plugins.

4) Visit Settings > myCred Polar.sh to configure.

---

## Quick start

1) In Polar:
   - Create a one‑time product (PWYW or fixed).
   - Create recurring products for plans.
   - Create an Access Token (see scopes above).
   - Create a Standard Webhook with event “order.paid”.
     - Endpoint: your-site-url/wp-json/mycred-polar/v1/webhook
     - Format: Raw
     - Copy the Secret to plugin settings.

2) In WordPress (Settings > myCred Polar.sh):
   - Mode: Sandbox or Live
   - Paste the Polar access token
   - Paste product ids (one‑time, and define recurring plans)
   - Paste webhook secret
   - Save

3) Add the shortcode to any page:

[mycred_polar_form]

text


4) Use the “Test Connection” button to confirm API access.

---

## Configuration

- Mode
  - Sandbox (sandbox-api) vs Live (api)
- Access Tokens
  - Enter the correct token for each mode
- One‑time product ids
  - PWYW or fixed. PWYW requires “amount” during checkout; the plugin sends it.
- Exchange rate
  - $ per point for PWYW math and plan display
- myCred point type
- Webhook
  - Secret (from Polar’s Standard Webhooks / Svix)
  - Verification mode: strict / api_fallback / disabled (dev)
- Subscription plans
  - JSON or table editor
  - If “Use custom amount”, price is points_per_cycle × exchange_rate

---

## Shortcode

[mycred_polar_form]

text


Renders:
- One‑time points UI
- Subscription plan picker
- Manage Subscriptions block (list + cancel)

Requires user to be logged in.

---

## Admin pages

- Settings > myCred Polar.sh
  - Main configuration
  - Test connection
- myCred Polar.sh > Transaction Logs
  - Last 100 transactions (date, user, points, amount, order id, status)
- myCred Polar.sh > Subscribe (dashboard)
  - KPIs per currency: Active, Canceling, Canceled (30d), MRR, ARR
  - Latest 500 subscriptions table (user, email, plan, amount, interval, status, started, current period, renews, cancel/ended)
  - Buttons: “Sync from Polar” and “Export CSV”

---

## Webhooks and security

- Endpoint:
  - POST /wp-json/mycred-polar/v1/webhook
- Event handled: order.paid
- Signature verify: Svix-compatible (webhook-id, webhook-timestamp, webhook-signature variants)
- Modes:
  - strict: reject if signature invalid or missing
  - api_fallback: if signature fails, plugin fetches order via Polar API and verifies paid
  - disabled: dev only; verifies via API only

Idempotency:
- Each order id is granted at most once (transaction table lock + prior log check)

---

## Subscriptions dashboard (Subscribe page)

At a glance:
- Active subscribers
- Canceling (cancel_at_period_end)
- Canceled or ended in last 30 days
- MRR and ARR per currency

Data sources:
- Local cache table (synced from Polar)
- “Sync from Polar” pulls:
  - GET /v1/subscriptions?active=true
  - GET /v1/subscriptions?active=false
- CSV export of entire cache

MRR normalization per subscription:
- month: amount / interval_count
- year: amount / 12 / interval_count
- week: amount × (52/12) / interval_count
- day: amount × (365/12) / interval_count

---

## Cancel flow (end users)

In the shortcode’s “Manage Subscriptions”:
1) Validate ownership of subscription (metadata.user_id or customer.external_id)
2) Create Polar Customer Session:
   - POST /v1/customer-sessions (requires customer_sessions:write)
3) Customer Portal cancel:
   - DELETE /v1/customer-portal/subscriptions/{id}
4) Fallback (org token):
   - PATCH /v1/subscriptions/{id} {"cancel_at_period_end": true} (requires subscriptions:write)

Cache is updated after successful cancel.

---

## Database schema

Two custom tables (with your WP prefix):

1) {prefix}mycred_polar_logs
- id, user_id, order_id, points, amount (cents), status, webhook_data, created_at

2) {prefix}mycred_polar_subscriptions
- id, user_id, subscription_id (unique), product_id, plan_name
- points_per_cycle, amount (cents), currency
- recurring_interval, recurring_interval_count
- status, cancel_at_period_end
- current_period_start, current_period_end
- started_at, canceled_at, ends_at, ended_at
- customer_email, customer_external_id
- updated_at, created_at

The plugin auto‑migrates schema (ALTER TABLE add missing columns) on load.

---

## WP actions, AJAX and routes

Shortcode:
- [mycred_polar_form]

REST (webhook):
- POST /wp-json/mycred-polar/v1/webhook (order.paid)

Rewrite:
- /mycred-success → success page (credit fallback)

AJAX (front):
- mycred_polar_create_checkout
- mycred_polar_create_subscription_checkout
- mycred_polar_list_subscriptions
- mycred_polar_cancel_subscription

AJAX (admin):
- mycred_polar_test_connection
- mycred_polar_admin_sync_subscriptions

Admin POST:
- mycred_polar_export_subscriptions (CSV)

---

## Troubleshooting

- “Cancel failed: Unknown error”
  - Ensure the plugin is on v3.5.0 or later.
  - Check token scopes include customer_sessions:write and subscriptions:write.
  - See browser console/Network → AJAX JSON will now include HTTP code and Polar error message for clarity.

- Webhook not triggering points
  - Verify event type is order.paid and Format is Raw.
  - Check verification mode: if strict, ensure the secret matches.
  - Use success fallback by completing checkout and hitting the success page (the plugin tries a few times to fetch the order and credit).

- Subscribe page shows blanks/warnings
  - v3.5.0 includes schema migration and null‑safe rendering.
  - Click “Sync from Polar” to populate full rows.

---

## FAQ

- Does PWYW work?
  - Yes. The plugin calculates amount = points × exchange_rate and passes it to Polar.

- Can users manage subscriptions?
  - The UI lists a user’s active subscriptions and allows cancel at period end.

- Multiple currencies?
  - KPIs are grouped per currency. Consolidated USD view can be added if you want (FX rates required).

- Can I auto‑sync nightly?
  - Not yet. Open an issue/PR — happy to add a WP-Cron daily sync.

---

## Changelog

- 3.5.0
  - New Subscribe dashboard (KPIs, table, Sync, CSV)
  - Defensive rendering + auto schema migration
  - Hardened cancel flow + clearer errors
- 3.4.x
  - Added admin dashboard foundation and cancel fixes
- 3.3.x
  - Fixed AJAX cancel hook and fallback PATCH
- 3.2.x
  - Base implementation: one‑time, subscriptions, webhook crediting, logs, success fallback

See commit history for details.

---

## Contributing

- Fork the repo
- Create a feature branch: `feat/your-idea`
- Follow WP PHPCS where possible
- Open a PR with a clear description and testing notes

Issues / Ideas welcome — especially:
- Daily/weekly auto‑sync
- Multi‑currency consolidation
- Cohort/churn analytics on the Subscribe dashboard

---

## License

GPL-2.0-or-later

Copyright (c) 2025
