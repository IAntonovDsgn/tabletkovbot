# AGENTS.md

Telegram bot "TabletkovBot" (PHP 8.4, clean architecture). Runs entirely in Docker; the repo is mounted into the app container.

## Commands (all inside Docker)

All PHP tooling runs in the **`tabletkovbot-app`** container (project mounted at `/var/www/tabletkovbot`). Host-side `composer`/`php`/`vendor/bin/*` do not work standalone.

```sh
# Tests (Pest)
docker exec tabletkovbot-app sh -c 'cd /var/www/tabletkovbot && composer test'

# PHPStan — full check (analyses src/ per phpstan.neon); run after any change
./check-full-project-from-docker.sh

# PHPStan — single file (arg is translated from host path to container path)
./run-phpstan-from-docker.sh src/Infrastructure/Http/Router.php

# Console (app commands + Doctrine migrations)
docker exec tabletkovbot-app sh -c 'cd /var/www/tabletkovbot && composer console -- app:tg-bot-get-updates'
```

Container names are `${COMPOSE_PROJECT_NAME}-*` with `COMPOSE_PROJECT_NAME=tabletkovbot` from `docker/.env`: `tabletkovbot-app`, `-db` (MariaDB 10.11), `-rabbitmq`, `-nginx`, `-outbox-relay`. Verify with `docker ps`. First-time setup: `./first-run.sh` (creates `docker/.env` from `.env.example`, builds, `composer install`).

## Environment

Env vars live in **`docker/.env`** (not project root). `bootstrap/bootstrap.php` loads it via `Symfony\Dotenv`; docker-compose also uses `env_file: docker/.env`. New settings must be added there. DB config: `config/database.php`; Telegram token: `config/telegram.php`.

## Architecture

- `src/Domain` — entities + repository interfaces; no infra dependencies.
- `src/Application` — use cases: `BotManager` state machine, `Services`, `Persistence` (outbox/UnitOfWork interfaces). `Manager::process()` orchestrates transaction + session + state transition.
- `src/Infrastructure` — DBAL repos, HTTP `Router` (FastRoute), `TelegramMessageService`, Doctrine migrations.
- `src/Presentation` — `Api/WebhookController` (web entry `public/index.php`) and `Console/Console.php`.
- DI is PHP-DI with autowiring; repository/service **interfaces** are bound explicitly in `bootstrap/appServiceProvider.php`. After changing a constructor, verify resolution: `docker exec tabletkovbot-app php -r 'require "/var/www/tabletkovbot/bootstrap/bootstrap.php"; $c->get(<class>::class); echo "OK";'`

Adding a conversation state: create `State<X>Handler` implementing `StateHandlerInterface`, then register it in `src/Application/BotManager/StateHandlerFactory.php` (**both** the constructor param and the `match` arm — `match` over `EnumState` is exhaustive, PHPStan fails on a missing case).

## Persistence contract (do not regress)

- Repositories expose `insert(X $e): int` (returns DB id — never assign ids manually) and `update(X $e): void`. There is **no** `save()`.
- Entities track their own persistence state: `Entity::create()` = new, `Entity::restoreFromPersistence(id, ...)` = existing (watch argument order — id comes first, then owner fields).
- `Manager::process()` calls `findByChatId()` once, then persists the session **once, after the handler runs**: `insert` for a fresh session, `update` if `isExistInPersistence()`. On `InvalidValueException` → rollback + `errorHandler` (re-finds session, `resetState()`, `update`). On other `Throwable` → rollback + best-effort `INTERNAL_ERROR` message to outbox (`notifyInternalError`, failures swallowed) + rethrow; nothing else persisted.
- State handlers do their own lookups and must verify ownership: `$medicament->getChatId() !== $chatId` → throw `NotFoundEntityException`. Handlers like notifications expect the session to already exist and throw if `findByChatId()` returns null (the `Manager` creates missing sessions, handlers don't).

## Hard-earned quirks

- **PHPStan level 10 forbids using `mixed`**: no casting, no method calls, no destructuring on it. Narrow with `is_*`/`instanceof` checks at runtime. DBAL rows return `array<string, mixed>` — use `HydrateRowsTrait` (`src/Infrastructure/Database/Dbal/Repositories/HydrateRowsTrait.php`: `toInt`, `toBool`, `toString`, `toStringOrNull`). `require` results (e.g. bootstrap returning the container, `Container::get()`) are `mixed` too — see `Router.php`/`Console.php` for the established narrowing pattern.
- All `src/` files use `declare(strict_types=1);` — respect scalar type hints or get `TypeError`.
- **Telegram SDK** (`irazasyed/telegram-bot-sdk`) relies on magic `__get`/`__call` + `@property`. Use property access (`$update->callbackQuery`, `$message->chat->id`) — not `getCallbackQuery()`-style methods — or PHPStan fails.
- Dates/times: parse with `DateTimeImmutable::createFromFormat('!' . FORMAT, $value)` and check `=== false`; never `new DateTimeImmutable($string)` on user input. Formats: `Report::DATE_FORMAT = 'd.m.Y'`, `Medicament::TIME_FORMAT = 'H:i'` in `Medicament::DATE_TIME_ZONE = 'Asia/Yekaterinburg'`.
- Outbox buttons: `MessageButton implements JsonSerializable` (`new_state` enum value + `additional_payload`, separator `MessageButton::PAYLOAD_SEPARATOR = '|'`); `Message::$text` is nullable (getter normalizes null to `''`).
- **Outbox → RabbitMQ relay** (`app:outbox-publish` in the `-outbox-relay` container): `Outbox` polls pending rows, publishes via `RabbitMqMessageBroker` (direct exchange `outbox`, durable queue `telegram.send-message`, routing key = queue name, publisher confirms), deletes the row **only after** broker confirm — at-least-once semantics; consumers must dedupe. Config: `config/rabbitmq.php`; tuning: `OUTBOX_BATCH_SIZE`, `OUTBOX_POLL_INTERVAL_MS`. pcntl signals handle graceful stop. **Docker Desktop on this machine does not deliver SIGTERM to containers** — `docker stop` always ends in SIGKILL after grace period; test signal handling with `docker exec <ctr> sh -c 'kill -TERM 1'`.
- **Testing**: Pest on top of PHPUnit-style classes (`tests/Unit`, `tests/Feature`). `final` classes can't be PHPUnit-mocked (mock repository *interfaces* instead). If a classic `@dataProvider` misbehaves under Pest, iterate data sets with `foreach` inside the test.

## Reference

`src/Domain/Entities/Session/State/StateTransitionRules.php` defines allowed state transitions (checked by `Session::transitionToState()`).
