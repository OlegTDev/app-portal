# AGENT.MD — Правила для AI-агента при разработке корпоративного портала

## 1. Общий контекст

Мы разрабатываем корпоративный портал с нуля.
Техническое задание — в `NEW_PORTAL_PROMPTS.md`. Всегда читай соответствующую секцию перед генерацией кода.

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

### 2.2. Модели Eloquent
- Имена в единственном числе: `Employee`, `News`, `PortalUser`
- PK UUID: `protected $keyType = 'string'; public $incrementing = false;`
- Casts: `'ad_data' => 'array'`, `'is_active' => 'boolean'`
- Relations: только те, что нужны по логике. Не генерируй лишние.
- Scopes именуются: `scopePublished`, `scopeByOrganization`
- Трейты: `HasFactory` всегда, `SoftDeletes` где нужно, Spatie-трейты по необходимости
- PHPDoc для свойств обязателен

### 2.3. Контроллеры
- Админка: `App\Http\Controllers\Admin\*` (Inertia, отдают React-страницы)
- API: `App\Http\Controllers\Api\*` (JSON, для `useHttp` и autocomplete)
- Публичные: `App\Http\Controllers\*` (Inertia-страницы)
- Валидация — через FormRequest (отдельный файл), не в контроллере
- Inertia-ответ: `return Inertia::render('News/Index', [...])`
- JSON-ответ: `return response()->json(...)`

### 2.4. React-компоненты (Inertia)
- Каждый компонент — отдельный файл в `resources/ts/pages/{Module}/`
- Именование: `Index.tsx`, `Show.tsx`, `Form.tsx`
- Типы props — через interface (TypeScript)
- Ant Design: `import { Table, Button } from 'antd'`
- Иконки: `@ant-design/icons`
- Таблицы: `Table` с `onChange` для серверной пагинации/сортировки
- Формы: `Form` + Inertia `useForm` для submit-ов
- Фоновые действия: `useHttp` (лайки, оценки, закрытие уведомлений)
- Layout: через Inertia layout props

### 2.5. Именование файлов и классов
- Миграции: `год_месяц_день_описание.php`
- Модели: `Employee.php`, `News.php`, `PortalUser.php`
- Контроллеры: `NewsController.php`, `EmployeeController.php`
- FormRequests: `StoreNewsRequest.php`, `UpdateNewsRequest.php`
- React: `Index.tsx`, `Show.tsx`, `Form.tsx`, `MenuTree.tsx`
- API ресурсы: `EmployeeResource.php`

## 3. Правила по пакетам

- **Медиа:** `spatie/laravel-medialibrary`. Коллекции: `files`, `images`, `videos`. Конверсии: `thumb`.
- **Логи действий:** `spatie/laravel-activitylog`. `log_name` = имя модели. CRUD — автоматически.
- **Теги:** `spatie/laravel-tags` (для новостей).
- **Роли/разрешения:** `spatie/laravel-permission`. Модель: `PortalUser`.
- **Иерархии:** `kalnoy/nestedset` (меню, категории). Поля: `_lft`, `_rgt`, `parent_id`.
- **LDAP:** `directorytree/ldaprecord-laravel`.

## 4. Запреты

- Никакого jQuery (только React + Inertia)
- Никакого Axios (Inertia 3.x — встроенный XHR + `useHttp`)
- Никаких префиксов `p_`, `tbl_`, `{{%...}}` в именах таблиц
- Никаких `DATETIME`, `bit`, `nvarchar` — только PostgreSQL-типы

## 5. Порядок разработки

Всегда соблюдай порядок этапов из секции 13 `NEW_PORTAL_PROMPTS.md`:
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
- для фронта (js, jsx, ts, css, html, scss) используй отступ в 2 пробела
- для отсальных 4 пробела


## 9. Проверки
- проверяй код через линтер eslint и phpstan

## 10. AntDesign
- в Modal вместо destroyOnClose использовать destroyOnHidden
- в Space вместо direction использовать orientation
- в Alert вместо message использовать title
