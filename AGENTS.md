# AGENTS.MD — Правила для AI-агента при разработке корпоративного портала

## 1. Общий контекст

Мы разрабатываем корпоративный портал с нуля.

**Стек:**
- Laravel 13.x (PHP 8.3+)
- Inertia.js 3.x (серверный роутинг, `useHttp` для фоновых запросов)
- React 19.x + TypeScript (React Compiler, стандартные хуки)
- Ant Design 6.7.x (CSS-переменные, Masonry, drawer resize, `variant` в ConfigProvider)
- PostgreSQL 19+

## 2. Правила генерации кода

### 2.1. Миграции (БД)
- PostgreSQL-синтаксис: `uuid`+`gen_random_uuid()`, `timestamptz`, `jsonb`, `boolean`
- PK всегда UUID, FK всегда с constraint-ами (`cascadeOnDelete`/`nullOnDelete`)
- Индексы явно: `$table->index(...)`, `$table->unique(...)`
- Для tsvector/GIN — отдельный `DB::statement()` в `up()`
- SoftDeletes: `$table->timestamptz('deleted_at')->nullable()`
- Именование таблиц: множественное число, snake_case. Pivot: алфавитный порядок полей
- Для строковых полей — указывать размер: `string('name', 200)`
- FK-поля — unsigned: `uuid('author_id')->nullable()` + `constrained()->onDelete('cascade')`

### 2.2. Модели Eloquent
- Имена в единственном числе: `Employee`, `News`, `PortalUser`
- PK UUID: `protected $keyType = 'string'; public $incrementing = false;`
- Casts: `'ad_data' => 'array'`, `'is_active' => 'boolean'`
- Relations: только те, что нужны по логике. Не генерируй лишние.
- Scopes именуются: `scopePublished`, `scopeByOrganization`
- Трейты: `HasFactory` всегда, `SoftDeletes` где нужно, Spatie-трейты по необходимости
- PHPDoc для свойств обязателен
- **Оптимизация:** всегда использовать `select()` для ограничения полей в списках
- **Оптимизация:** использовать `loadMissing()` вместо `load()` для условной загрузки
- **Eager loading:** избегать N+1 — `with()` только нужных relations
- **Query scope:** цепочечные scope для комбинирования фильтров

### 2.3. Контроллеры
- Админка: `App\Http\Controllers\Admin\*` (Inertia, отдают React-страницы)
- API: `App\Http\Controllers\Api\*` (JSON, для `useHttp` и autocomplete)
- Публичные: `App\Http\Controllers\*` (Inertia-страницы)
- Валидация — через FormRequest (отдельный файл), не в контроллере
- Inertia-ответ: `return Inertia::render('News/Index', [...])`
- JSON-ответ: `return response()->json(...)`
- **Resource Controller:** использовать `index/store/update/destroy` для CRUD-модулей
- **Resource Route:** `Route::resource('news', NewsController::class)`
- **Action Controller:** `__invoke` для одноточечных контроллеров (Dashboard, Departments)

### 2.4. React-компоненты (Inertia)
- Каждый компонент — отдельный файл в `resources/ts/pages/{Module}/`
- Именование: `Index.tsx`, `Show.tsx`, `Form.tsx`
- Типы props — через interface (TypeScript)
- Ant Design: `import { Table, Button } from 'antd'`
- Иконки: `@ant-design/icons`
- Таблицы: `Table` с `onChange` для серверной пагинации/сортировки
- Формы: `Form` + Inertia `useForm` для submit-ов
- Фоновые действия: `useHttp` (лайки, оценки, закрытие уведомлений)
- Layout: через `AdminLayout` для админ-страниц, `MainLayout` для публичных
- **Path alias:** использовать `@/` для относительных импортов (`@/layouts/AdminLayout`)
- **Server-side pagination:** данные приходят с сервера, клиент только отображает
- **Server-side sorting:** сортировка через query params, не на клиенте

### 2.5. Именование файлов и классов
- Миграции: `год_месяц_день_описание.php`
- Модели: `Employee.php`, `News.php`, `PortalUser.php`
- Контроллеры: `NewsController.php`, `EmployeeController.php`
- FormRequests: `StoreNewsRequest.php`, `UpdateNewsRequest.php`
- React: `Index.tsx`, `Show.tsx`, `Form.tsx`, `MenuTree.tsx`
- API ресурсы: `EmployeeResource.php`
- Policies: `EmployeePolicy.php`, `OrganizationPolicy.php`
- Middleware: `Access.php`, `HandleInertiaRequests.php`

## 3. Правила по пакетам

- **Медиа:** `spatie/laravel-medialibrary`. Коллекции: `files`, `images`, `videos`. Конверсии: `thumb`.
- **Логи действий:** `spatie/laravel-activitylog`. `log_name` = имя модели. CRUD — автоматически.
- **Теги:** `spatie/laravel-tags` (для новостей).
- **Роли/разрешения:** `spatie/laravel-permission`. Модель: `PortalUser`.
- **Иерархии:** `kalnoy/nestedset` (меню, категории). Поля: `_lft`, `_rgt`, `parent_id`.
- **LDAP:** `directorytree/ldaprecord-laravel`.

## 4. Запреты

- Никакого jQuery (только React + Inertia)
- Никаких Axios (Inertia 3.x — встроенный XHR + `useHttp`)
- Никаких префиксов `p_`, `tbl_`, `{{%...}}` в именах таблиц
- Никаких `DATETIME`, `bit`, `nvarchar` — только PostgreSQL-типы
- Никакой валидации в контроллерах (только FormRequest)
- Никакого `env()` вне config-файлов (только `config()`)

## 5. Порядок разработки

Всегда соблюдай порядок этапов:
1. Фундамент (employees, organizations, portal_users, AD-синхронизация, access_groups, аутентификация, меню)
2. Основной контент (категории, новости, оргструктура, телефонный справочник)
3. Бизнес-модули (декларационная кампания, автоматизация рутины)
4. Дополнительные (наставничество, тестирование)

Не перескакивай. Каждый следующий модуль может зависеть от предыдущего.

## 6. Принцип «одна задача — один ответ»

- 1 миграция, или 1 модель, или 1 контроллер, или 1 React-компонент за раз
- Исключение: модель + миграция + фабрика — могут идти вместе
- Большую задачу разбивай на части, запрашивай подтверждение после каждой
- Всегда указывай путь к файлу в начале ответа: `// app/Models/Employee.php`

## 7. Git

- фиксировать ТОЛЬКО после одобрения
- в комитах использовать русский язык и грамотные заголовки
- придерживаться [стиля](https://github.com/alik0211/git-style-guide)
- при написании комитов использовать формат conventionalcommits

## 8. Отступы

- для фронта (js, jsx, ts, css, html, scss) — 2 пробела
- для остального (PHP, markdown) — 4 пробела

## 9. Проверки

- проверяй код через линтер eslint и phpstan
- перед фиксацией: `npx tsc --noEmit` и `npx eslint` для TS, `docker compose run --rm app phpstan` для PHP
- pre-commit хук уже настроен: автоматически запускает PHPStan (staged .php) и ESLint (staged .ts/.tsx)
- пропустить проверки: `git commit --no-verify`

## 10. AntDesign

- в Modal вместо `destroyOnClose` использовать `destroyOnHidden`
- в Space вместо `direction` использовать `orientation`
- в Alert вместо `message` использовать `title`

## 11. Docker

- запускай команды artisan через `docker compose run --rm app [command]`

## 12. Laravel конвенции

### 12.1. Роутинг
- Группировка по префиксу и middleware: `Route::prefix('admin')->name('admin.')->middleware('auth:portal')->group(...)`
- Resource routes для CRUD: `Route::resource('news', NewsController::class)`
- Named routes с префиксом модуля: `admin.news.index`, `admin.news.store`

### 12.2. Коллекции
- Использовать коллекционные методы: `filter()`, `map()`, `pluck()`, `groupBy()`, `sortBy()`
- Избегать foreach для трансформации коллекций

### 12.3. Кэширование
- Использовать `Cache` facade с `remember()` паттерном
- Ключи в формате `module:context` (например, `menu:main`, `menu:left`)
- TTL по умолчанию 3600 секунд

### 12.4. События
- Использовать `Dispatchable` trait для событий
- Слушатели в `EventServiceProvider` или через автообнаружение

### 12.5. Ошибки
- Кастомные исключения для бизнес-логики
- Глобальный обработчик `App\Exceptions\Handler`

### 12.6. Безопасность
- CSRF защита через middleware
- XSS защита через экранирование в React (по умолчанию)
- CSP через headers

### 12.7. Индексы и производительность
- Индексы на FK-поля
- Индексы на часто фильтруемые поля
- GIN-индексы для jsonb-полей
- `select()` для ограничения полей в списках

### 12.8. Планировщик задач
- Использовать `Schedule` класс в `app/Console/Kernel.php`
- Кастомные artisan команды в `app/Console/Commands/`

## 13. Тестирование

### 13.1. Структура
- Feature-тесты: `tests/Feature/`
- Unit-тесты: `tests/Unit/`
- Тесты для модуля: `tests/Feature/{Module}Test.php`

### 13.2. Factory
- Фабрики для всех моделей: `database/factories/{Model}Factory.php`
- Генерация всех полей модели
- Использовать `HasFactory` trait в моделях

### 13.3. База данных
- `refreshDatabase()` для сброса между тестами
- PostgreSQL-безопасный сброс (session_replication_role = replica)
- Использовать factories, не хардкодить данные

### 13.4. Авторизация в тестах
- `actingAs($user, 'portal')` для аутентификации
- Проверка policy через `assertForbidden()` / `assertUnauthorized()`
