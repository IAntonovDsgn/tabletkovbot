# TabletkovBot

> Telegram-бот для учёта приёма медикаментов с напоминаниями и отчётами.

**Версия:** 1.0.0

## О проекте

TabletkovBot помогает пользователю вести список медикаментов, получать
напоминания о приёме и формировать PDF-отчёты об отметках приёма.

**Возможности:**

- Добавление / переименование / удаление медикаментов и времени напоминания
- Отметка о фактическом приёме медикамента
- Включение и отключение напоминаний
- Планировщик ежедневных напоминаний (catch-up по времени `H:i` в TZ `Asia/Yekaterinburg`)
- Формирование PDF-отчётов за выбранный период (mpdf)
- Асинхронная доставка сообщений через Transactional Outbox + RabbitMQ

**Стек:** PHP 8.4, MariaDB 10.11, RabbitMQ, Nginx, Docker Compose,
PHP-DI, Doctrine DBAL/Migrations, Monolog, FastRoute, Telegram Bot SDK, Pest, PHPStan (level 10).

**Архитектура (Clean Architecture):**

- `src/Domain` — сущности + интерфейсы репозиториев
- `src/Application` — use cases (`StateManager`, `Outbox`, `Notifications`, `Message`, `UnitOfWork`)
- `src/Infrastructure` — DBAL-репозитории, HTTP Router, RabbitMQ, Telegram transport, миграции
- `src/Presentation` — `Api/WebhookController` (точка входа `public/index.php`) и консольные команды

## Развёртывание

Требования: Docker + Docker Compose.

```sh
git clone <repo-url> tg-bot-tabletkovbot
cd tg-bot-tabletkovbot
cp .env.example .env      # затем укажите TELEGRAM_BOT_TOKEN
./first-run.sh
```

Скрипт соберёт образы, установит зависимости, поднимет инфраструктуру (DB, RabbitMQ),
дождётся готовности БД и применит миграции. Webhook будет доступен на `http://${APP_URL}:${NGINX_HOST_PORT}`.

## Команды

### Тесты и анализ кода (на хосте)

```sh
./check-full-project-from-docker.sh   # PHPStan + Pest
```

### Тесты (в контейнере app)

```sh
docker exec tabletkovbot-app sh -c 'cd /var/www/tabletkovbot && composer test'
```

### Форматирование кода — cs-fixer (в контейнере app)

```sh
docker exec tabletkovbot-app sh -c 'cd /var/www/tabletkovbot && composer cs-fixer'
```

### Консольные команды (в контейнере app)

```sh
docker exec tabletkovbot-app sh -c 'cd /var/www/tabletkovbot && composer console -- <имя-команды>'
```

### Миграции (в контейнере app)

```sh
docker exec tabletkovbot-app sh -c 'cd /var/www/tabletkovbot && composer console -- migrations:migrate'
```

### PHPStan по одному файлу (на хосте)

```sh
./run-phpstan-from-docker.sh src/Infrastructure/Http/Router.php
```

## Переменные окружения

Файл `.env` в корне проекта (шаблон — `.env.example`).

| Переменная | Описание | Пример |
| --- | --- | --- |
| `COMPOSE_FILE` | Путь к compose-файлу | `docker/docker-compose.yml` |
| `COMPOSE_PROJECT_NAME` | Префикс имени проекта и контейнеров | `tabletkovbot` |
| `APP_URL` | Хост приложения (для webhook) | `127.0.0.1` |
| `NGINX_HOST_PORT` | Порт на хосте для Nginx | `8000` |
| `APP_TIMEZONE` | Часовой пояс приложения | `Asia/Yekaterinburg` |
| `TELEGRAM_BOT_TOKEN` | Токен бота от @BotFather | |
| `TG_BOT_BASE_URL` | Базовый URL Telegram API | `https://api.telegram.org` |
| `MYSQL_ROOT_PASSWORD` | Пароль root MariaDB | `123` |
| `MYSQL_USER` | Пользователь БД | `user` |
| `MYSQL_PASSWORD` | Пароль пользователя БД | `123` |
| `MYSQL_DATABASE` | Имя базы данных | `db` |
| `MYSQL_PORT` | Порт MariaDB | `3306` |
| `MYSQL_HOST` | Хост MariaDB (имя сервиса) | `db` |
| `RABBITMQ_USER` | Пользователь RabbitMQ | `guest` |
| `RABBITMQ_PASSWORD` | Пароль RabbitMQ | `guest` |
| `RABBITMQ_HOST` | Хост RabbitMQ | |
| `RABBITMQ_PORT` | Порт RabbitMQ | `5672` |
| `RABBITMQ_VHOST` | Виртуальный хост RabbitMQ | `/` |
| `RABBITMQ_MESSAGE_QUEUE` | Очередь исходящих сообщений Telegram | `telegram.send-message` |
| `RABBITMQ_REPORT_QUEUE` | Очередь генерации отчётов | `report.generate` |
| `OUTBOX_BATCH_SIZE` | Размер пачки outbox-relay | `50` |
| `OUTBOX_POLL_INTERVAL_MS` | Интервал опроса outbox, мс | `1000` |
| `OUTBOX_MAX_ATTEMPTS` | Макс. попыток публикации из outbox | `4` |
| `NOTIFY_POLL_INTERVAL_MS` | Интервал планировщика напоминаний, мс | `60000` |
| `REPORT_POLL_INTERVAL_MS` | Интервал consumer отчётов, мс | `1000` |
| `REPORT_MAX_DAYS` | Макс. период отчёта, дней | `365` |
| `REPORT_MAX_SEND_ATTEMPTS` | Макс. попыток отправки отчёта | `3` |
| `REPORT_TEMP_FILE_TTL` | Время жизни временных файлов, сек | `3600` |
| `LOG_STDOUT` | Логировать в stdout (иначе только в файл) | `true` |
| `LOG_MAX_BYTES` | Макс. размер лог-файла, байт | `10485760` |
| `LOG_MAX_BACKUPS` | Кол-во ротаций лог-файла | `1` |

## Лицензия и авторские права

© 2026 Antonov Igor. Все права защищены.
