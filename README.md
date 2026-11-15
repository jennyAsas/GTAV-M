# GTAV-M: Global Trade Association Events Manager

University of Baguio, School of Information Technology. WEBSYS2 project.

A Laravel web app for chapters, events, registrations, QR entry passes and gate check-in,
deployed on Render with Supabase Postgres.

## Status

The core slice is implemented. **It has not been run or tested yet**: the code was written in an
environment that could not install Composer packages. Run the test suite first (see below)
and fix anything that fails before relying on it.

| Module | Requirements | State |
| --- | --- | --- |
| Accounts and roles | FR-01 to FR-03 | Built |
| Chapters and events | FR-04 to FR-11 | Built |
| Registration and payment status | FR-12 to FR-16 | Built |
| QR entry pass | FR-17 | Built (print or save as PDF from the browser) |
| Check-in: event selection, scan, manual search, live count, attendance | FR-21 to FR-25 | Built |
| Dashboard: ticket volume, revenue, chapter roll-up | FR-26 to FR-29 | Built |
| Attendance certificates | FR-18 | Deferred |
| Workshop materials upload and download | FR-19, FR-20 | Deferred |

## Roles

| Role | How the account is created | Can do |
| --- | --- | --- |
| Global Administrator | `ADMIN_EMAIL` / `ADMIN_PASSWORD` on first deploy | Everything; chapters; users and roles |
| Regional Chapter Manager | By an administrator | Events, payments, assignments and reports for own chapter |
| Corporate Attendee | Self-registration | Browse, register, view registrations, entry pass |
| Guest Speaker | Self-registration | Assigned events (materials come with the deferred module) |
| Venue Gate Staff | By an administrator | Scan and search for assigned events |

## Business rules

- One registration per attendee per event, enforced by a unique key.
- Capacity: pending and paid registrations hold a seat, and rejected ones free it. Registration locks
  the event row in a transaction, so on Postgres two requests cannot take the last seat.
- Payment: a manager marks a registration paid or rejected. Free events are paid on registration.
- The entry pass is available only to the owner of a paid registration.
- Check-in returns `authorized`, `denied` or `already_used`. A ticket enters once, enforced by a
  row lock and a unique key on `attendances.registration_id`.
- Check-in is open from the start day of the event until it ends. This rule was proposed for
  the project, and the project document does not contain it.

## Local setup

Requirements: PHP 8.4 (the framework config uses `Pdo\Mysql`, a PHP 8.4 class), Composer, and
either Postgres or a Supabase project.

```bash
composer install
cp .env.example .env
php artisan key:generate
# Set DB_URL (or DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD),
# plus ADMIN_EMAIL and ADMIN_PASSWORD, in .env
php artisan migrate
php artisan db:seed
php artisan serve
```

## Tests

```bash
php artisan test
```

The tests use in-memory SQLite. SQLite ignores row locks, so the tests check the business
rules but cannot prove the concurrency guarantee. Before the event, test that on Postgres by
sending two registrations for the last seat at the same time.

## Deploy to Render with Supabase

1. In Supabase, open **Connect**, then copy the **Session pooler** connection string (port 5432).
   Do not use the transaction pooler on port 6543, because it does not support prepared
   statements.
2. In Render, create a **Web Service** from this repository. Choose the **Docker** runtime, or use
   `render.yaml` as a Blueprint.
3. Set the environment variables:
   - `APP_KEY`: run `php artisan key:generate --show` locally and paste the output.
   - `APP_URL`: the Render URL, for example `https://gtav-m.onrender.com`.
   - `APP_ENV=production` and `APP_DEBUG=false`.
   - `DB_CONNECTION=pgsql` and `DB_URL`: the session pooler string from step 1.
   - `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME`: the first administrator.
4. Deploy. On startup the container caches its configuration, then runs `migrate --force` and
   `db:seed --force`. The seeder creates the administrator only once and never resets the
   password afterwards.
5. Smoke test: log in as the administrator, create a manager and a chapter, create an event,
   register as an attendee, mark the registration paid, then scan the pass from the gate
   staff account.

Free-tier notes: Render's free web services sleep when idle, so the first request after a pause
is slow. Supabase free projects pause after a period of inactivity. Open both before a demo.

## Security notes

- Secrets live only in environment variables. `.env` is git-ignored.
- Passwords are hashed with bcrypt, and the session is regenerated on login.
- Every form has CSRF protection, and Blade escapes all output.
- Queries go through Eloquent with bound parameters.
- Login, registration, scan and search are rate limited.
- Authorization lives in `app/Policies` and in the gates in `AppServiceProvider`.
