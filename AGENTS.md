# AGENTS.md

Telegram bot "TabletkovBot" (PHP 8.4, clean architecture). Runs entirely in Docker; the repo is mounted into the app container.

## Run everything inside the Docker container

All PHP tooling runs in the **`tabletkovbot-app`** container, with the project mounted at `/var/www/tabletkovbot`. Host-side `composer`/`php`/`vendor/bin/*` do not work standalone.

```sh
# PHPStan (full project check) — recommended after any change
./run-phpstan-full-project-from-docker.sh

# Tests
docker exec tabletkovbot-app /var/www/tabletkovbot/vendor/bin/pest

# Console (app commands + Doctrine migrations)
docker exec tabletkovbot-app php /var/www/tabletkovbot/src/Presentation/Console/Console.php <cmd>
```

Note: The correct container name for running PHP commands is `tabletkovbot-app`. Verify the container is up with `docker ps`. Other services: `tabletkovbot-db` (MariaDB 10.11), `tabletkovbot-rabbitmq`, `tabletkovbot-nginx`.

## Environment

Env vars live in **`docker/.env`** (not project root). `bootstrap/bootstrap.php` loads `docker/.env` via `Symfony\Dotenv`; docker-compose also uses `env_file: docker/.env`. New settings must be added there. DB config: `config/database.php` (pdo_mysql, MariaDB). Telegram token: `config/telegram.php`.

## Architecture

- `src/Domain` — entities + repository interfaces; no infra dependencies.
- `src/Application` — use-case layer: `BotManager` state machine, `Services`, `Persistence` (outbox/UnitOfWork interfaces). The `Manager` class orchestrates transactions, session, and state transitions.
- `src/Infrastructure` — DBAL repos, HTTP `Router`, `TelegramMessageService`, PHP-DI bootstrap, Doctrine migrations.
- `src/Presentation` — `Api/WebhookController` (web entry `public/index.php`) and `Console/Console.php`.
- DI is PHP-DI with autowiring; repository/service **interfaces** are bound explicitly in `bootstrap/appServiceProvider.php`. After changing a constructor, verify resolution (e.g. `docker exec tabletkovbot-app php -r 'require ".../bootstrap/bootstrap.php"; $c->get(<class>::class); echo "OK";'`).

Adding a new conversation state: create `State<X>Handler` implementing `StateHandlerInterface`, then register it in `src/Application/BotManager/StateHandlerFactory.php` (constructor + `match` arm). PHPStan level 9 + `match` means every `EnumState` case must be covered.

## Hard-earned quirks (do not regress)

- **Strict Types**: All PHP files in `src/` now include `declare(strict_types=1);`. Ensure all function/method calls respect scalar type hints to avoid `TypeError`.
- **PHPStan level 9 forbids casting `mixed`.** DBAL `fetchAssociative()`/`fetchAllAssociative()` return `array<string, mixed>`; narrow each value with `is_*` checks before casting. Use the `HydrateRowsTrait` trait in `src/Infrastructure/Database/Dbal/Repositories/Concerns/HydratesRows.php` (`toInt`, `toBool`, `toString`, `toStringOrNull`).
- **Telegram SDK** (`irazasyed/telegram-bot-sdk`) uses magic `__get`/`__call` + `@property`. Use property access (`$update->callbackQuery`, `$message->chat->id`) — not methods like `getCallbackQuery()` — or PHPStan fails.
- `MedicamentRepositoryInterface::save()` **now returns `int`** (DB lastInsertId); do not assign manual ids. The interface and concrete implementations (`MedicamentRepository`) are aligned.
- Dates: user-facing `Report::DATE_FORMAT = 'd.m.Y'`; DB rows parsed with `createFromFormat()` and checked for `=== false` (never `new DateTimeImmutable($string)` directly).
- Outbox buttons: `MessageButton implements JsonSerializable` (`new_state` enum value + `additional_payload`); `Message::$text` is nullable.
- State handlers that load a medicament by id must also verify ownership: `$medicament->getChatId() !== $chatId` → throw `NotFoundEntityException`.
- **PHPUnit Mocking Final Classes**: `final` classes (e.g., `StateHandlerFactory`) cannot be mocked by PHPUnit. If a dependency needs to be mocked, consider making the class non-`final` or using alternative mocking strategies.
- **Pest Data Providers**: When using `@dataProvider` with Pest, if traditional PHPUnit style fails, consider using a `foreach` loop within the test method to iterate through data sets as a workaround.

## Reference

`docs/code-review.md` is the code review report (P0 fixes, P1 roadmap — incl. RabbitMQ consumer for the outbox). `src/Domain/Entities/Session/State/StateTransitionRules.php` defines state transitions.
