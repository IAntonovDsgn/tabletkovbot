# Code Review: TabletkovBot Backend

Дата: 2026-08-16
Способ проверки: ручной аудит + PHPStan (level 9) + runtime-проверки в Docker.

Стек: PHP 8.4, PHP-DI, Doctrine DBAL, Telegram Bot SDK, Symfony Console, Pest, MariaDB, RabbitMQ (в планах).

---

## Сводка

- PHPStan (level 9, `src` + `tests`): **69 ошибок**.
- Pest: только 2 тривиальных примера (`example`), реального покрытия нет.
- Проект в состоянии WIP: не хватает консьюмера outbox, реализаций консольных команд, тестов.

Приоритеты:
- **P0** — ломает рантайм или целые пользовательские сценарии.
- **P1** — нарушение чистой архитектуры / ООП, долг.
- **P2** — чистота, стиль, мелочи.

---

## P0. Критические баги

### P0-1. `TelegramMessageService` не создаётся через DI
`src/Infrastructure/TelegramMessageService/TelegramMessageService.php:20-26`

```php
public function __construct(
    private TelegramBotApi $telegramApi,
) {
    $this->token =
    $this->httpClient = new HttpClient(['base_uri' => $telegramConfig['base_url']]);
}
```

- `$telegramConfig` не определён в конструкторе (PHPStan: `variable.undefined`) — DI-конфиг в `bootstrap/appServiceProvider.php:57-60` передаёт `telegramConfig`, но параметра нет.
- `$this->token` (тип `string`) присваивается объект `HttpClient` (PHPStan: `assign.propertyType`) — TypeError.
- Строка `$this->token =` обрывается — результат идёт и в `token`, и в `httpClient`.
- `$keyboard` (строка 51) на левой стороне `??` не определена — при сообщении без кнопок всегда будет undefined.
- `getUpdates()` возвращает `list<Illuminate\Support\Collection>`, а декларирован `RequestDTO[]` (PHPStan: `return.type`).

**Как чинить:** убрать мёртвые поля `$token`/`$httpClient` или инициализировать корректно через DI-параметр; `$keyboard` инициализировать до `if`; `getUpdates()` строить `RequestDTO` из апдейтов.

### P0-2. `hydrate()` в репозиториях кидает TypeError
`src/Infrastructure/Database/Dbal/Repositories/MedicamentRepository.php:130` и `IntakeMarkRepository.php:104`

```php
new DateTimeImmutable($row[self::NOTIFICATION_TIME_COLUMN_NAME], Medicament::TIME_FORMAT)
```

Второй аргумент `DateTimeImmutable::__construct()` — таймзона, а передаётся строка формата. Подтверждено рантаймом:

```
TypeError: DateTimeImmutable::__construct(): Argument #2 ($timezone) must be of type ?DateTimeZone, string given
```

Любое чтение медикамента/отметки с временем падает. **Как чинить:** `DateTimeImmutable::createFromFormat(Medicament::TIME_FORMAT, $row[...])`.

### P0-3. `MedicamentRepository::findByChatId` читает одну строку
`src/Infrastructure/Database/Dbal/Repositories/MedicamentRepository.php:113`

```php
$rows = $queryBuilder->executeQuery()->fetchAssociative(); // должно быть fetchAllAssociative()
foreach ($rows as $row) { ... }
```

`fetchAssociative()` возвращает `false`/одну строку; `foreach` по `false` — пусто, по строке — «строка из колонок» вместо списка. PHPStan: `foreach.nonIterable`. Отсюда ломаются сценарии «выберите медикамент» (изменение/удаление/отметка), которые зависят от `findByChatId`.

### P0-4. `Report::DATE_FORMAT = 'dd.mm.yyyy'` — невалидный формат
`src/Domain/Entities/Report/Report.php:11,20,25`

`'dd.mm.yyyy'` не является PHP-форматом даты. Подтверждено рантаймом: `createFromFormat('dd.mm.yyyy', '16.08.2026')` → `false` + 2 ошибки. Сценарий «скачать отчёт» всегда падает в `StateDownloadReportStartDateEnteredHandler` (`FORMAT_DATE_ERROR`). `getStartDate()` возвращает `DateTimeImmutable|false`.

**Как чинить:** формат `'d.m.Y'`; парсер с проверкой полной длины ввода (`!` в формате); `getStartDate()` — не парсить строку повторно, а хранить `DateTimeImmutable`.

### P0-5. `Medicament` — сломан `notificationTime`
`src/Domain/Entities/Medicament/Medicament.php:11,30-38`

- `private readonly string $notificationTime;` не инициализируется при `null` (PHPStan: `property.uninitializedReadonly`).
- `setNotificationTime()` присваивает readonly-свойство вне конструктора (PHPStan: `readOnlyAssignNotInConstructor`) — в PHP 8.1+ это `Error`.
- `getNotificationTime(): ?DateTimeImmutable` возвращает `string` (PHPStan: `return.type`).

**Как чинить:** хранить `?DateTimeImmutable`, не readonly; формат применять только при сериализации в репозитории.

### P0-6. `IntakeMark::getCreatedAt()` возвращает строку
`src/Domain/Entities/IntakeMark/IntakeMark.php:11,19,56-59`

`$createdAt` хранится как отформатированная строка, тип возврата `DateTimeImmutable`. `isIncludeInInterval()` (стр. 34-44) заново парсит и падает на `false >= DateTimeImmutable`.

**Как чинить:** хранить `DateTimeImmutable`, парсинг — только в репозитории.

### P0-7. Создание медикамента: ручной id против автоинкремента
`src/Application/BotManager/StateHandlers/StateMedicamentNameEnteredHandler.php:35-50`

```php
$lastMedicamentId = array_reduce($medicaments, ...);      // null для пустого списка
$medicament = new Medicament($text, $chatId, id: ++$lastMedicamentId);
$this->medicamentRepository->save($medicament);           // UPDATE несуществующей строки
```

- `save()` (`MedicamentRepository.php:38-45`) при непустом `id` делает `UPDATE` вместо `INSERT` → новый медикамент **не создаётся**.
- `array_reduce` по пустому массиву → `null`, `++null` — хрупко.
- `newSessionPayload` — `int`, а тип параметра `string|null` (PHPStan: `argument.type`).

**Как чинить:** убрать ручной id. `MedicamentRepositoryInterface::save()` должен возвращать `int` (lastInsertId для новых). Хендлер сохраняет и кладёт id в payload сессии строкой.

### P0-8. Отметка о приёме: нет `save()` и неверный payload
`src/Application/BotManager/StateHandlers/StateIntakeMarkHasMadeHandler.php:30-51`

- `IntakeMark` создаётся, но `intakeMarkRepository->save()` **не вызывается** — отметка теряется.
- Сравнение `$medicament->getName() === $buttonPayload`: в `StateMakeIntakeMarkSelectedHandler` (`:37-41`) кнопки создаются без `additionalPayload` → payload пустой, условие всегда ложно → `NotFoundEntityException`.
- PHPStan: `IntakeMark` constructor expects `int`, `int|null` given.

**Как чинить:** в `StateMakeIntakeMarkSelectedHandler` передавать `$medicament->getId()` как `additionalPayload`; в `StateIntakeMarkHasMadeHandler` читать id из `$buttonPayload`, находить `findById()`, вызывать `save()`.

### P0-9. Неверные переходы состояний у кнопок
`src/Application/BotManager/StateHandlers/StateChangeMedicamentSelectedHandler.php:39`, `StateDeleteMedicamentSelectedHandler.php:39`

Из `CHANGE_MEDICAMENT_SELECTED` переходы разрешены только на `SELECTED_MEDICAMENT_FOR_CHANGE`, `MENU`, `NOTIFIED` (см. `StateTransitionRules`). Кнопки же ведут на `CHANGE_MEDICAMENT_NAME_SELECTED` → `TransitionStateNotAllowedException` при нажатии. Аналогично в удалении: должно быть `SELECTED_MEDICAMENT_FOR_DELETE`. Ни в одном из них id медикамента не передаётся в `additionalPayload` (в `StateChangeMedicamentSelectedHandler:40` ещё и `int|null` вместо `string|null`, PHPStan: `argument.type`).

`StateChangeMedicamentSelectedMedicamentHandler.php:32,37-41`: сравнение `$medicament->getId() === $buttonPayload` — `int` vs `string` строго, никогда не сработает; `is_null($medicamentId)` — PHPStan `function.alreadyNarrowedType`, строка 41 мёртвая.

**Как чинить:** выставить верные состояния, прокинуть id медикамента строкой, в сравнении делать `(string) $medicament->getId() === $buttonPayload`.

### P0-10. Null-безопасность сессии
`findByChatId()` возвращает `?Session`, а вызовы методов без проверки:
- `Manager.php:78` — `errorHandler()` вызывает `resetState()` на `?Session`.
- `StateNotificationEnabledHandler.php:26-27`, `StateNotificationDisabledHandler.php:25-26`, `StateNotificationsSelectedHandler.php:28`, `StateMedicamentNotificationTimeEnteredHandler.php:43`.

**Как чинить:** либо `findByChatId(): Session` с автосозданием, либо явные проверки/guard-вызов `errorHandler`.

### P0-11. Outbox: сериализация кнопок даёт `[{}]` и гидрируется в `mixed`
`src/Domain/Entities/Message/MessageButton.php` (все свойства приватные)

Подтверждено рантаймом: `json_encode([new MessageButton(...)])` → `[{}]`. После чтения из `message_outbox` `json_decode(..., true)` даёт массивы `{}`, а `TelegramMessageService::sendMessage` вызывает `$button->getNewState()` на массиве → fatal.

Дополнительно: `OutboxRepository::hydrate` (стр. 83-91) — `json_decode` в `mixed` (PHPStan: `argument.type`); `Message::getText()` (стр. 23-26) падает на nullable-значении `EnumMessageText` (PHPStan: `property.nonObject`).

**Как чинить:** `MessageButton implements JsonSerializable` (или публичный маппинг); типизировать кнопки при гидрировании; в `Message::getText()` обработать null.

### P0-12. `WebhookController` — неверный API SDK и 500 на неизвестных апдейтах
`src/Presentation/Api/WebhookController.php:62-76`

- PHPStan: `Update::getCallbackQuery()`, `Collection::getChat()`, `Collection::getText()` — undefined methods (неверное использование SDK).
- `throw new CouldNotUploadInputFile()` для не message/callback-апдейтов (стр. 75) — по смыслу это не ошибка загрузки файла; в `public/index.php` ловится как 500 → Telegram ретраит вебхук бесконечно.

**Как чинить:** работать через корректный API SDK (например, `$update->message`, `$update->callbackQuery` — magic-свойства или `getUpdateContent()`); для необработанных типов апдейтов вернуть `http_response_code(200)`.

### P0-13. Консоль: битые команды и регистр namespace
- `GetTelegramUpdatesCommand.php:5,27` и `SendTelegramMessageCommand.php:14,37` ссылаются на несуществующие `App\Application\Query\Message\GetUpdates\Handler` и `App\Application\Command\Message\SendMessage\Handler` (этих namespace'ов нет). PHPStan: `class.notFound`.
- `Console.php:3-6,18-21` — namespace `App\Presentation\console` (нижний регистр) не совпадает с реальным `App\Presentation\Console` (PHPStan: `class.nameCase`).
- `SendTelegramMessageCommand.php:20` задаёт имя `app:tg-bot-send-message`, а регистрация в `Console.php:20` — `app:send-telegram-message` (рассинхрон).
- `SendTelegramMessageCommand.php:32` — `is_int($input->hasArgument(...))` всегда `false` (PHPStan: `function.impossibleType`).

**Как чинить:** поправить регистр; реализовать или убрать битые команды (реальная реализация — в рамках консьюмера outbox/RabbitMQ); выровнять имена.

### P0-14. Прочее
- `SwaggerController.php:20` — `toJson()` на `OpenApi|null` (PHPStan: `method.nonObject`); нужен guard.
- `Router.php:29-31` — `$_SERVER` значения без типов (PHPStan: `argument.type`); нужны приведения.
- `StateTransitionRules.php:107` — доступ по ключу без гарантии существования (PHPStan: `offsetAccess.notFound`); добавить `?? []`.
- `phpstan.neon` — только `level: 9`, без `paths`: команда `phpstan analyse` без аргументов не работает («At least one path must be specified»).
- `Manager.php:82` — `Message` получает `EnumMessageText::ERROR|string` вместо `?EnumMessageText` (PHPStan: `argument.type`).

---

## P1. Чистая архитектура и ООП

### P1-1. Outbox-запись вне транзакции
`src/Application/BotManager/Manager.php:58-62`

`sessionRepository->save()` и `commit()` выполняются, и только **после** `commit()` идёт запись в outbox. Весь смысл паттерна outbox — атомарность «изменения + исходящее сообщение». Сейчас при падении между `commit` и `save` сообщение теряется, а при падении внутри транзакции outbox-запись откатывается — итог: либо потеря, либо рассинхрон. Консьюмера (`getPendingMessages`/`markAsSent`) в проекте нет вовсе — сообщения в outbox никто не отправляет.

**Рекомендация:** outbox-save выполнять в рамках той же транзакции до `commit()`. Отправку реализовать консьюмером.

### P1-2. Нарушение Dependency Inversion
`Manager.php:25` зависит от конкретного `OutboxRepository` вместо `OutboxRepositoryInterface`. При этом интерфейс в `Application\Persistence` уже есть, а импорт — инфраструктурный класс. Аналогично в других местах.

### P1-3. Разнобой в размещении интерфейсов репозиториев
Одни интерфейсы в `Domain\Entities\*` (`MedicamentRepositoryInterface`, `SessionRepositoryInterface`, ...), другие — в `Application\Persistence` (`OutboxRepositoryInterface`, `UnitOfWorkInterface`). Нет единого правила «интерфейс живёт у потребителя». `UnitOfWork` (транзакция) — чисто инфраструктурный, но интерфейс в Application.

### P1-4. Application-слой выполняет персистентность
State-хендлеры сами вызывают `repository->save()`: `StateNotificationEnabledHandler:28`, `StateNotificationDisabledHandler:27`, `StateMedicamentNotificationTimeEnteredHandler:51`, `StateChangeNameMedicamentEnteredHandler:43`, `StateDeleteMedicamentConfirmedHandler:36`. Сохранение происходит до возврата в `Manager`, т.е. вне единой точки управления транзакцией и вне транзакции с outbox.

**Рекомендация (направление):** хендлеры возвращают «интенты»/команды; оркестратор (Manager / CQS-шина) применяет их и сохраняет в одной транзакции.

### P1-5. UI и копирайт живут в Domain
`Domain\Entities\Message\Message`, `MessageButton`, `EnumMessageText` — это Telegram-вывод (тексты кнопок, сообщений), а не домен. Enum `EnumMessageText` смешивает доменные «события» (ошибка, успех) с UI-текстом. Слои завязываются на конкретные фразы («Выберите медикамент»). Хендлеры Application строят `Message`/`MessageButton` напрямую.

**Рекомендация:** вынести «исходящие сообщения» в Application/описание ответа, UI-тексты — в отдельный ресурс, домен не должен знать про кнопки.

### P1-6. God-класс `Manager`
`Manager::process()` — оркестрация транзакции, загрузка/сохранение сессии, выбор состояния, вызов хендлера, работа с outbox, обработка ошибок. 5+ ответственностей. PHPStan также фиксирует проблемы типизации.

**Рекомендация:** выделить: загрузчик сессии, выбор перехода, применённый хендлер, обработчик ошибок, сервис outbox.

### P1-7. `getNextState()` «угадывает» состояние
`Manager.php:90-109` — при отсутствии payload берёт единственный разрешённый переход (без `NOTIFIED`/`MENU`) либо `MENU`. Это дублирует знания state-машины и хрупко: молчаливый fallback на MENU при 0/2+ вариантах.

### P1-8. Payload сессии — голый `string`
В `sessions.payload` хранится и id медикамента, и прочее без типизации. `StateMedicamentNameEnteredHandler` кладёт id, `StateMedicamentNotificationTimeEnteredHandler` и `StateDeleteMedicamentConfirmedHandler` читают как `string|null`→`int`. Нет value-object'а, нет валидации, PHPStan не видит типы.

### P1-9. `UnitOfWork` — не Unit of Work
`UnitOfWork.php` — обёртка над `beginTransaction/commit/rollBack`. Нет отслеживания изменений агрегатов, нет грязных сущностей. Название вводит в заблуждение; либо переименовать (`Transaction`), либо реализовать настоящий UoW.

### P1-10. `StateHandlerFactory` — 20 параметров конструктора
`StateHandlerFactory.php:29-50` инжектирует 20 хендлеров. Каждый новый state — правка фабрики и DI. PHPStan дополнительно подтверждает типовые проблемы в хендлерах.

**Рекомендация:** маппинг `EnumState => handler` через DI-конфиг или итерация по всем `StateHandlerInterface`; в перспективе — CQS-шина.

### P1-11. Нет идемпотентности и секрета вебхука
`WebhookController` не проверяет `X-Telegram-Bot-Api-Secret-Token`, нет дедупликации по `update_id`. Telegram ретраит неотвеченные апдейты → риск двойной обработки.

### P1-12. Вебхук и polling конфликтуют
Реализованы оба пути: вебхук (`WebhookController`) и `getUpdates` (`TelegramMessageService::getUpdates` + консольная команда). В продакшене Telegram позволяет только один режим.

### P1-13. Дублирование репозитория-логики
`SessionRepository::save()` (стр. 53-83) сначала `SELECT`, затем `INSERT/UPDATE` — гонка/двойной запрос. Лучше `INSERT ... ON DUPLICATE KEY UPDATE` (или upsert). То же по сути у `MedicamentRepository::save` с ветвлением по `id`.

### P1-14. Отчёт никуда не отправляется
`StateHandlerResponseDTO` содержит `?Report`, но `Manager` его игнорирует. Флоу «скачать отчёт» заканчивается текстом `REPORT_READY` без файла. Функциональность не дописана (WIP), но важно зафиксировать.

### P1-15. `Illuminate\Container` + Facade ради `Log`
`bootstrap/bootstrap.php:24-29` поднимает Illuminate-контейнер и Facade, чтобы `Log::error()` работал в `index.php`/`Manager`. Тяжелая зависимость для логирования.

**Рекомендация:** использовать PSR-3 `LoggerInterface` напрямую.

### P1-16. Хардкод таймзоны в домене
`Medicament::DATE_TIME_ZONE = 'Asia/Yekaterinburg'` — таймзона пользователя/инфраструктуры в доменной сущности. Формат и таймзона должны конфигурироваться/прокидываться.

### P1-17. Роутер в Infrastructure
`Infrastructure\Http\Router` — HTTP/роутинг это Presentation-уровень. `Infrastructure` не должен знать о контроллерах.

### P1-18. Swagger генерируется на каждый запрос
`SwaggerController::getJson()` сканирует каталоги и генерирует OpenAPI на каждый `GET /swagger-json`. Дорого; кэшировать или генерировать при сборке.

### P1-19. `storage/logs/app.log` и `.env` в репозитории
`.env` загружается из `docker/.env` (bootstrap:13) — связка к конкретному layout; путь и способ должны быть конфигурируемыми. Файл логов — в `.gitignore` (ок), но конфиг `config/logging.php` жёсткий.

---

## P2. Чистота и стиль

- `EnumMessageText.php:3` — лишний пробел в namespace (`namespace  App\...`).
- `EnumMessageText::ENTER_DATE` — опечатка «начала начала».
- `StateTransitionRules::isTransitionToStateAllowed` — `$result = false; if (...) $result = true;` → можно вернуть `in_array(...)` напрямую; добавить `declare(strict_types=1)`.
- Нет `declare(strict_types=1)` в большинстве файлов.
- Константы `const string ...` без модификатора видимости (public по умолчанию) в репозиториях — ок, но смешано с `const int`/`const bool` (разные типы без явного указания в одном стиле).
- `tests/Pest.php:44` — функция `something()` без типа возврата (PHPStan: `missingType.return`); мёртвый код из шаблона — удалить.
- `.php-cs-fixer.cache` есть, конфига `.php-cs-fixer.dist.php` нет — настройки форматтера не версионируются.
- `Manager.php` — импорт `Doctrine\DBAL\Exception` и `Throwable` в `@throws`.
- Несогласованные имена констант состояния: `SELECTED_MEDICAMENT_FOR_CHANGE` создан, но не используется в кнопках (см. P0-9).

---

## Направление развития

### 1. Outbox-consumer на RabbitMQ
Контейнер `rabbitmq` уже в `docker/docker-compose.yml`. Схема:
1. `Manager` пишет исходящее сообщение в `message_outbox` **в той же транзакции** (исправить P1-1).
2. Отдельный процесс (консольная команда-консьюмер под supervisor) читает pending-сообщения, публикует в RabbitMQ.
3. Worker отправляет в Telegram через `MessageServiceInterface`, на успех `markAsSent`, на ошибку — retry/queue.
4. Уведомления о приёме медикаментов — второй consumer, выбирает сессии с `is_notification_enabled = 1` и `notification_time`, генерирует сообщение, публикует.

### 2. CQS-шина вместо фабрики хендлеров
Разбить Application-уровень на `Command`/`Query` c Handlers, маршрутизация по команде, единый transaction-boundary и сохранение outbox внутри.

### 3. Типизированный payload сессии
Value-object (напр., `SessionPayload`) вместо голой строки; строгая валидация id.

### 4. Тесты
- Юнит: `StateTransitionRules`, `Report` (парсинг/валидация даты), `Medicament`, `IntakeMark`.
- Интеграционные (DBAL + in-memory/fake): флоу «добавление», «изменение», «удаление» медикамента, «отметка о приёме», «отчёт».
- Функциональные: `Manager::process` с fake-репозиториями и outbox.

### 5. Прочее
- `phpstan.neon`: добавить `paths` и `treatPhpDocTypesAsCertain` при необходимости.
- Секрет вебхука + дедупликация по `update_id`.
- Убрать Illuminate/Facade для логов, PSR-3 напрямую.
- Роутер → Presentation, swagger-кэш, `declare(strict_types=1)`.
- Единая точка: `Message`/кнопки как `JsonSerializable`.

---

## Статус исправлений

| ID | Статус |
|----|--------|
| P0-1 … P0-14 | исправлено (PHPStan 0 ошибок, Pest 2/2, DI-контейнер собирается) |
| P1-1 … P1-19 | направление (см. «Развитие») |
| P2 | по мере рефакторинга |
