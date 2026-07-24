# gym-progress-tracker

Laravel 13 monolith for a Telegram workout tracker with a shared backend for future mobile apps.

## Current stage

Stage 1 is in place:

- Docker and Docker Compose;
- base Laravel configuration files;
- initial database schema for users, muscle groups, exercises, and workouts;
- seeders for the system exercise catalog;
- localization scaffold for Telegram strings.

Stage 2 is also in place:

- Telegram webhook endpoint;
- queued update processing;
- `/start` registration flow;
- main menu with inline buttons;
- callback routing for menu placeholders;
- Telegram state storage;
- update deduplication;
- webhook artisan commands;
- tests for the core Telegram flow.

Stage 3 is in place:

- workout template selection;
- active workout creation;
- exercise selection inside an active workout;
- add set flow with weight, repetitions and optional RPE;
- set saving with volume and estimated 1RM calculation;
- workout completion with summary;
- tests for the workout flow and metrics.

Stage 4 is in place:

- workout history list and workout details;
- personal records storage and sync;
- base statistics summary;
- Telegram screens for `/history`, `/stats`, and `/records`;
- tests for records and insights.

Stage 5 is in place:

- exercise progress forecast service;
- Telegram forecast screen from the exercise action menu;
- callback routing for exercise progress;
- tests for forecast math and Telegram forecast rendering.

Stage 6 is in place:

- user goals table and model;
- Telegram goal list screen;
- Telegram goal creation flow;
- goal type selection and state handling;
- basic goal progress calculation;
- tests for goal service and Telegram goal flow.

Stage 7 is in place:

- Sanctum token auth for the shared API;
- `/api/v1` routes for exercises, workouts, templates, goals, statistics, and records;
- API resources and form requests;
- ownership policies for user data;
- API feature tests for auth, exercises, workouts, and goals.

Stage 8 is in place:

- workout template exercise management;
- template copying;
- template exercise reordering;
- API tests for template management.

Stage 9 is in place:

- webhook ingress hardening;
- constant-time secret validation;
- optional Telegram webhook secret-header validation;
- webhook rate limiting;
- safer job retry logging;
- tests for webhook security and throttling.

Stage 10 is in place:

- explicit authorization regression tests for foreign workouts and templates;
- Telegram state-machine regression tests for `/cancel`;
- expired Telegram state cleanup coverage;
- broader edge-case protection for shared API and dialogue flows.

Stage 11 is in place:

- README deployment and webhook setup notes;
- `.env.example` operational defaults;
- local port and public tunnel guidance for Telegram webhook delivery.

Stage 12 is in place:

- cleaned up Telegram translation files;
- removed mojibake from fallback workout strings;
- preserved a clean UTF-8 user-facing message set for Telegram.

## MVP scope

First release target:

- Telegram registration via `/start`;
- main menu;
- system exercise list;
- workout templates;
- start workout flow;
- add sets with weight and repetitions;
- last result preview;
- workout completion;
- workout history;
- personal records;
- base statistics;
- basic progress forecast;
- Docker;
- tests;
- README.

## Local run

The project is designed to run with:

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Local HTTP is exposed through Nginx on `http://localhost:8080`.

If you need Telegram to reach your local machine, expose port 8080 with a public HTTPS tunnel such as ngrok and point `TELEGRAM_WEBHOOK_URL` to the public base URL only. The application will append the webhook path automatically.

## Telegram setup

Set these variables in `.env`:

```env
TELEGRAM_BOT_TOKEN=
TELEGRAM_BOT_USERNAME=
TELEGRAM_WEBHOOK_SECRET=
TELEGRAM_WEBHOOK_URL=
TELEGRAM_REQUEST_TIMEOUT=10
TELEGRAM_WEBHOOK_RATE_LIMIT_PER_MINUTE=120
TELEGRAM_STATE_TTL_MINUTES=120
```

Notes:

- `TELEGRAM_WEBHOOK_SECRET` can be any long random string; it is used both in the webhook path and, when Telegram sends it, as the webhook secret token.
- `TELEGRAM_WEBHOOK_URL` must be the public HTTPS base URL, for example `https://abcd-1234.ngrok-free.dev`.
- Do not include `/api/telegram/webhook/...` in `TELEGRAM_WEBHOOK_URL`; the app adds that path itself.

Then register webhook:

```bash
docker compose exec app php artisan telegram:webhook:set
```

Inspect or remove webhook:

```bash
docker compose exec app php artisan telegram:webhook:info
docker compose exec app php artisan telegram:webhook:delete
```

## Structure

```text
app/
|-- Enums/
|-- Http/
|   |-- Controllers/
|   `-- Middleware/
|-- Models/
`-- Providers/

database/
|-- factories/
|-- migrations/
`-- seeders/

docker/
|-- nginx/
`-- php/

lang/
|-- en/
`-- ru/
```

## Deployment notes

- Start the stack with `docker compose up -d --build`.
- Run migrations and seeders before setting the webhook.
- Start the queue worker and scheduler containers together with the app.
- After changing `TELEGRAM_WEBHOOK_URL` or `TELEGRAM_WEBHOOK_SECRET`, rerun `php artisan telegram:webhook:set`.
- If Telegram stops delivering updates, check `storage/logs/telegram.log` and `php artisan telegram:webhook:info`.
