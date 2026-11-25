# NEW_PORTAL_PROMPTS.md

## Техническое задание на разработку корпоративного портала

**Стек:** Laravel 13.x (PHP 8.3+) + Inertia.js 3.x + React 19.x (TypeScript, React Compiler) + Ant Design 6.7.x + PostgreSQL 19+

**Аутентификация:** форма логин/пароль через AD/LDAP

---

## 0. Целевая архитектура

### 0.1. Стек и версии

- **Backend:** Laravel 13.x, PHP 8.3+
- **Аутентификация:** форма логин/пароль через AD/LDAP
- **Frontend:** Inertia.js 3.x (встроенный XHR-клиент без Axios, `useHttp` для фоновых запросов, оптимистичные обновления, layout props)
- **UI:** React 19.x + Ant Design 6.7.x (CSS-переменные, Masonry для галерей, ресайз Drawer, `variant` в ConfigProvider)
- **База данных:** PostgreSQL 19+
- **LDAP:** `adldap2/adldap2-laravel`
- **Медиа:** `spatie/laravel-medialibrary`
- **Логирование:** `spatie/laravel-activitylog`
- **Теги:** `spatie/laravel-tags`
- **Роли/разрешения:** `spatie/laravel-permission`
- **Иерархии:** `kalnoy/nestedset` (меню, категории)
- **CSRF:** нативный middleware `PreventRequestForgery` (совместим с Inertia)

### 0.2. Принципы БД

- **Первичные ключи:** bigIncrements (автоинкремент)
- **Даты/время:** `timestamptz` во всех случаях
- **Гибкие данные:** `jsonb` для AD-данных, настроек
- **Полнотекстовый поиск:** `tsvector` + GIN-индекс
- **Soft Deletes:** `deleted_at` (timestamptz, nullable)

### 0.3. Соглашение об именовании (Laravel-конвенции)

- **Таблицы:** множественное число, snake_case: `employees`, `news`, `departments`
- **Поля:** единственное число: `employee_id`, `full_name`, `created_at`
- **Pivot-таблицы:** алфавитный порядок: `access_group_employee`, `department_employee`
- **Внешние ключи:** `{таблица_ед_ч}_id` → `employee_id`
- **Никаких префиксов** `p_`, `{{%...}}`

### 0.4. Архитектурный принцип: слабосвязанные модули

- **Никакого монолитного `Tree`** — каждый модуль самостоятелен
- **Меню:** отдельная таблица `menu_items` (Nested Set)
- **Категории:** отдельная таблица `categories` (Nested Set)
- **Статические страницы:** slug-based роутинг + модель `Page`

### 0.5. Nested Sets

Для меню и категорий используется `kalnoy/nestedset` с полями `_lft`, `_rgt`, `parent_id`.
---

## 1. Сотрудники и организации — фундамент системы

### 1.1. Единая модель `Employee`

В текущей системе три сущности дублируют друг друга:
- `user` — пользователи портала (те, кто заходил)
- `telephone_user` — сотрудники из справочника (SOAP → теперь из AD)
- `department_card` — сотрудники в карточках отделов

**Решение:** единая таблица `employees` — все сотрудники из AD пополняются по крону.

#### 1.1.1. Миграция: `employees`

```php
Schema::create('employees', function (Blueprint $table) {
    $table->$table->id();
    $table->string('username')->unique()->nullable()->comment('AD sAMAccountName');
    $table->string('email')->nullable();
    $table->string('full_name')->nullable()->comment('ФИО');
    $table->string('position')->nullable()->comment('Должность');
    $table->string('rank')->nullable()->comment('Чин/звание');
    $table->string('phone')->nullable();
    $table->string('phone_additional')->nullable();
    $table->string('office')->nullable()->comment('Кабинет');
    $table->string('department_name')->nullable()->comment('Название отдела из AD');
    $table->string('organization_code', 10)->nullable();
    $table->string('photo_path')->nullable();
    $table->jsonb('ad_data')->nullable()->comment('Сырые данные из AD (memberof, dn)');
    $table->boolean('is_active')->default(true)->comment('Активен (не уволен)');
    $table->timestamptz('created_at')->useCurrent();
    $table->timestamptz('updated_at')->useCurrent();
});
$table->index('full_name');
$table->index('phone');
$table->index('organization_code');
$table->index('is_active');
```

#### 1.1.2. Полнотекстовый поиск

```php
DB::statement("ALTER TABLE employees ADD COLUMN search_vector tsvector");
DB::statement("CREATE INDEX employees_search_idx ON employees USING GIN (search_vector)");
DB::statement("
    CREATE TRIGGER employees_search_update BEFORE INSERT OR UPDATE ON employees
    FOR EACH ROW EXECUTE FUNCTION
    tsvector_update_trigger('search_vector', 'pg_catalog.russian',
        'full_name', 'position', 'phone', 'phone_additional', 'email', 'department_name')
");
```
### 1.2. Миграция: `organizations`

```php
Schema::create('organizations', function (Blueprint $table) {
    $table->string('code', 10)->primary()->comment('Код НО: 8600, 8601, ...');
    $table->string('name');
    $table->string('short_name')->nullable();
    $table->string('parent_code', 10)->nullable()->comment('Реорганизована в ...');
    $table->integer('sort_order')->default(0);
    $table->text('description')->nullable()->comment('Историческая справка');
    $table->string('upload_images_path')->nullable();
    $table->timestamptz('created_at')->useCurrent();
    $table->timestamptz('updated_at')->useCurrent();
    $table->timestamptz('ended_at')->nullable()->comment('Дата окончания деятельности');
    $table->foreign('parent_code')->references('code')->on('organizations')->nullOnDelete();
});
```

### 1.3. Pivot: `employee_organization`

```php
Schema::create('employee_organization', function (Blueprint $table) {
    $table->unsignedBigInteger('employee_id');
    $table->string('organization_code', 10);
    $table->primary(['employee_id', 'organization_code']);
    $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
    $table->foreign('organization_code')->references('code')->on('organizations')->cascadeOnDelete();
});
```

### 1.4. Миграция: `portal_users` (пользователи, входящие в портал)

```php
Schema::create('portal_users', function (Blueprint $table) {
    $table->$table->id();
    $table->unsignedBigInteger('employee_id')->unique();
    $table->string('password')->nullable()->comment('bcrypt hash');
    $table->rememberToken();
    $table->string('organization_code', 10)->nullable();
    $table->boolean('blocked')->default(false);
    $table->timestamptz('last_login_at')->nullable();
    $table->string('last_action_url')->nullable();
    $table->timestamptz('created_at')->useCurrent();
    $table->timestamptz('updated_at')->useCurrent();
    $table->timestamptz('deleted_at')->nullable();
    $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
    $table->foreign('organization_code')->references('code')->on('organizations')->nullOnDelete();
});
```

### 1.5. AD-синхронизация

- **Scheduled Command:** `php artisan ad:sync` (раз в час/день)
- **Логика:** обход AD → upsert в `employees` по `username`
- **Новые:** создаются с `is_active = true`
- **Существующие:** обновляются (ФИО, должность, phone, department, memberof → jsonb)
- **Уволенные (не найдены в AD):** `is_active = false`, `updated_at = now()`
- **Поле `ad_data`** (jsonb): хранит сырые данные для аудита и отладки

### 1.6. Поиск сотрудников (React-компонент)

- Ant Design `AutoComplete` с debounce 300ms
- API: `GET /api/employees/search?q=...`
- Controller исползует PostgreSQL full-tet search:
  `WHERE search_vector @@ plainto_tsquery('russian', :q)`
- Возвращает top-20 резултатов: `full_name`, `position`, `phone`, `office`, `photo_path`, `organization_code`
---

## 2. Аутентификация

### 2.1. AD-аутентификация

```php
// app/Services/LdapService.php — обёртка над adldap2
class LdapService
{
    public function findUser(string $username): ?array;    // поиск по sAMAccountName
    public function authenticate(string $usr, string $pwd): bool;
}
```

- **Флоу:**
  1. Пользователь заходит на `/login`
  2. Вводит логин и пароль
  3. `LdapService::authenticate(username, password)` — проверка через AD
  4. При успехе: поиск/создание `Employee` → `PortalUser` → сессия → redirect на `/`
  5. При неудаче: ошибка «Неверный логин или пароль»

### 2.2. Форма логина

- Роут: `GET/POST /login`
- Валидация: username (sAMAccountName) + password
- Проверка: `LdapService::authenticate(username, password)`
- При успехе: сохраняем в сессию, redirect на `/`
- При неудаче: ошибка «Неверный логин или пароль»

### 2.3. Сессионная аутентификация

Исползуется Laravel Sanctum (SPA-режим) для сессионной аутентифкации.
Сесии хранятся в БД (таблца `sessions`). Inertia автоматически отправляет XSRF-токен.
---

## 3. Авторизация (единая система)

В текущей сисеме тр мехнизма: Yii2 RBAC + GrantAccess + group/group_user.

**Решение:** одна сисема на базе `access_groups`, `access_group_employee`, `access_group_ad_groups` + Spatie Permission для ролей.

### 3.1. Миграция: `access_groups`

```php
Schema::create('access_groups', function (Blueprint $table) {
    $table->$table->id();
    $table->string('unique_key', 100)->unique()->comment('Ключ: meeting-vks-fns-editor');
    $table->string('title', 200);
    $table->text('note')->nullable();
    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->timestamptz('created_at')->useCurrent();
    $table->timestamptz('updated_at')->useCurrent();
    $table->foreign('created_by')->references('id')->on('employees')->nullOnDelete();
    $table->foreign('updated_by')->references('id')->on('employees')->nullOnDelete();
});
```

### 3.2. Pivot: `access_group_employee`

```php
Schema::create('access_group_employee', function (Blueprint $table) {
    $table->unsignedBigInteger('access_group_id');
    $table->unsignedBigInteger('employee_id');
    $table->timestamptz('expired_at')->nullable();
    $table->timestamptz('created_at')->useCurrent();
    $table->primary(['access_group_id', 'employee_id']);
    $table->foreign('access_group_id')->references('id')->on('access_groups')->cascadeOnDelete();
    $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
});
```

### 3.3. `access_group_ad_groups`

```php
Schema::create('access_group_ad_groups', function (Blueprint $table) {
    $table->$table->id();
    $table->unsignedBigInteger('access_group_id');
    $table->string('group_dn', 200)->comment('DN группы в AD');
    $table->string('group_name', 100)->comment('Название группы для отбражения');
    $table->timestamptz('expired_at')->nullable();
    $table->timestamptz('created_at')->useCurrent();
    $table->timestamptz('updated_at')->useCurrent();
    $table->foreign('access_group_id')->references('id')->on('access_groups')->cascadeOnDelete();
    $table->index('group_dn');
});
```

### 3.4. Gate::can() с проверкой через обе связи

```php
// App\Providers\AuthServiceProvider.php
Gate::define('access-group', function (?PortalUser $user, string $uniqueKey) {
    if (!$user) return false;

    $cacheKey = "access:{$user->id}:{$uniqueKey}";
    return Cache::remember($cacheKey, 3600, function () use ($user, $uniqueKey) {
        $employee = $user->employee;
        if (!$employee) return false;

        // Прямое членство
        $direct = DB::table('access_group_employee')
            ->join('access_groups', 'access_group_employee.access_group_id', '=', 'access_groups.id')
            ->where('access_groups.unique_key', $uniqueKey)
            ->where('access_group_employee.employee_id', $employee->id)
            ->where(fn ($q) => $q->whereNull('access_group_employee.expired_at')
                ->orWhere('access_group_employee.expired_at', '>', now()))
            ->exists();
        if ($direct) return true;

        // Членство через AD-группу
        $memberOf = $employee->ad_data['memberof'] ?? [];
        if (empty($memberOf)) return false;
        return DB::table('access_group_ad_groups')
            ->join('access_groups', 'access_group_ad_groups.access_group_id', '=', 'access_groups.id')
            ->where('access_groups.unique_key', $uniqueKey)
            ->whereIn('access_group_ad_groups.group_dn', $memberOf)
            ->where(fn ($q) => $q->whereNull('access_group_ad_groups.expired_at')
                ->orWhere('access_group_ad_groups.expired_at', '>', now()))
            ->exists();
    });
});
```

### 3.5. Spatie Permission для базовых ролей

- Роли: admin, moderator
- Разрешения: создаются через миграцию + Seeder
- Проверка: `$user->can('admin')`, `$user->hasRole('moderator')`
- Пользователи → Spatie `model_has_roles` (morphToMany с portal_users)

### 3.6. Polices для моделей

```php
// NewsPolicy, DepartmentPolicy, AutomationRoutinePolicy, TestPolicy ...
// view, create, update, delete — базовые проверки
// manageAccess — проверка через Gate::can('access-goup', 'news-editor')
---

## 4. Ядро портала

### 4.1. Меню (`menu_items`, Nested Set)

**Админ-часть:**
- Управление пунктами меню в админке (вкладка «Меню»)
- CRUD + Nested Set drag-n-drop дерево в Ant Design Tree
- Два типа: главное (`position = 'main'`) и левое (`position = 'left'`)
- Ключевые параметры: `name`, `link`, `icon`, `target`, `is_blank`, `sort_order`

```php
Schema::create('menu_items', function (Blueprint $table) {
    $table->$table->id();
    $table->nestedSet(); // _lft, _rgt, parent_id (kalnoy/nestedset)
    $table->string('name', 100);
    $table->string('link', 500)->nullable();
    $table->string('icon', 50)->nullable()->comment('Иконка Ant Design');
    $table->string('target', 10)->nullable()->comment('_blank|_self');
    $table->string('position', 10)->default('main')->comment('main|left');
    $table->integer('sort_order')->default(0);
    $table->boolean('is_enabled')->default(true);
    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->timestamptz('created_at')->useCurrent();
    $table->timestamptz('updated_at')->useCurrent();
    $table->timestamptz('deleted_at')->nullable();
    $table->foreign('created_by')->references('id')->on('employees')->nullOnDelete();
    $table->foreign('updated_by')->references('id')->on('employees')->nullOnDelete();
});
Schema::table('menu_items', fn ($t) => $t->index(['_lft', '_rgt', 'parent_id', 'position']));
```

**React-компонент (Inertia):**
- `MenuTree` — Ant Design Tree с drag-n-drop
- Кеширование: `Redis::remember('menu:main', 3600, fn => MenuItem::with('children')...)`
- На frontend: Inertia layout props через `HandleInertiaRequests::share()`

### 4.2. Категории (`categories`, Nested Set)

Универсальные категории для разделов новостей и страниц:

```php
Schema::create('categories', function (Blueprint $table) {
    $table->$table->id();
    $table->nestedSet();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('module', 30)->default('news')->comment('news|page');
    $table->string('organization_code', 10)->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('is_enabled')->default(true);
    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->timestamptz('created_at')->useCurrent();
    $table->timestamptz('updated_at')->useCurrent();
    $table->timestamptz('deleted_at')->nullable();
    $table->foreign('organization_code')->references('code')->on('organizations')->nullOnDelete();
    $table->foreign('created_by')->references('id')->on('employees')->nullOnDelete();
    $table->foreign('updated_by')->references('id')->on('employees')->nullOnDelete();
});
```

### 4.3. Файлы (Spatie Media Library)

Все загрузки (новости, модули, отделы) — через `spatie/laravel-medialibrary`:
- Модель реализует `InteractsWithMedia`
- Загрузка: `$model->addMedia($req->file('upload'))->toMediaCollection('files')`
- Галереи: `toMediaCollection('images')`, `toMediaCollection('videos')`
- Миниатюры: встроенные конверсии Spatie (`registerMediaConversions()`)
- Файловая система: S3-совместимый диск (local/DO Spaces/MinIO)

### 4.4. Журнал действий (Spatie Activity Log)

- Пакет: `spatie/laravel-activitylog`
- Логирует все CRUD-операции
- Поля: `log_name`, `description`, `subject_type`, `subject_id`, `causer_type`, `causer_id`, `properties` (jsonb), `created_at`
- Дополнительно: лог аутентификации (вход/выход/неудача) — отдельный `log_name = 'auth'`

### 4.5. Кеширование (Redis)

- **Меню:** `menu:main`, `menu:left` — сбрасываются при изменении любого пункта
- **Access Groups:** `access:{userId}:{uniqueKey}` — сбрасывается при изменении состава группы
- **DeclareCampaign:** `declare_campaign_usn_last_data` — сбрасывается при сохранении
- **Сотрудники:** `employee:{id}` — сбрасывается при AD-синхронизации
---

## 5. Новости

### 5.1. Миграция: `news`

```php
Schema::create('news', function (Blueprint $table) {
    $table->$table->id();
    $table->string('title', 500);
    $table->text('summary')->nullable()->comment('Краткое описание');
    $table->text('body')->comment('Основной текст');
    $table->unsignedBigInteger('category_id')->nullable();
    $table->string('organization_code', 10)->nullable();
    $table->string('department_name', 500)->nullable()->comment('Отдел (автор материала)');
    $table->date('published_at')->comment('Начало публикации');
    $table->date('expired_at')->comment('Окончание публикации');
    $table->timestamptz('pinned_at')->nullable()->comment('Закреплена до');
    $table->boolean('is_enabled')->default(true);
    $table->boolean('on_main_page')->default(false)->comment('Новость дня на главной');
    $table->integer('likes_count')->default(0);
    $table->integer('comments_count')->default(0);
    $table->integer('visits_count')->default(0);
    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->timestamptz('created_at')->useCurrent();
    $table->timestamptz('updated_at')->useCurrent();
    $table->timestamptz('deleted_at')->nullable();
    $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
    $table->foreign('organization_code')->references('code')->on('organizations')->nullOnDelete();
    $table->foreign('created_by')->references('id')->on('employees')->nullOnDelete();
    $table->foreign('updated_by')->references('id')->on('employees')->nullOnDelete();
});

// Полнотекстовый поиск новостей
DB::statement("ALTER TABLE news ADD COLUMN search_vector tsvector");
DB::statement("CREATE INDEX news_search_idx ON news USING GIN (search_vector)");
DB::statement("
    CREATE TRIGGER news_search_update BEFORE INSERT OR UPDATE ON news
    FOR EACH ROW EXECUTE FUNCTION
    tsvector_update_trigger('search_vector', 'pg_catalog.russian', 'title', 'summary', 'body')
");
```

### 5.2. Модель `News`

```php
class News extends Model
{
    use HasFactory, SoftDeletes, InteractsWithMedia, HasTags;

    // Scopes
    public function scopePublished($query): void;
    public function scopeByOrganization($query, string $code): void;
    public function scopeByCategory($query, string $categoryId): void;
    public function scopeOnMainPage($query): void;
    public function scopeNotExpired($query): void;

    // Relations
    public function category(): BelongsTo;
    public function organization(): BelongsTo;
    public function creator(): BelongsTo;  // → employees
    public function likes(): MorphMany;
    public function comments(): MorphMany;

    // Media collections
    public function registerMediaCollections(): void;
    public function registerMediaConversions(): void;
}
```

### 5.3. CRUD (админ-часть)

- Контроллер: `Admin\NewsController` (толькo для `admin` + `moderator`)
- Ant Design Form с полным набором полей
- Загрузка изображений/видео/файлов через Spatie Media Library (Ant Upload + Dragger)
- Текстовый редактор: `@ant-design/react-suneditor` (или другой RTE)
- Предпросмотр миниатюры
- Управление датами публикации (DatePicker)
- Теги: Ant Design Select mode="tags"
- При закреплении новости — `pinned_at` устанавливается в указанную дату

### 5.4. Публичные страницы

- **Лента новостей:** `NewsController@index` — фильтрация по организации, категории, тегам
- **Просмотр:** `NewsController@show` — полный текст, галерея (Masonry), видео, файлы, лайки
- **Главная:** `HomeController@index` — последние новости
- **Inertia-навигация:** фильтры без перезагрузки (useHttp для подгрузки при прокрутке)

### 5.5. Лайки (toggle)

```php
// API: POST /news/{news}/like
// Toggle: если лайк есть → удаляем; если нету → создаём
// Таблица: news_likes (morph, employee_id, news_id, created_at)
// Ответ: { liked: bool, likes_count: int }
// Inertia: useHttp для POST без перезагрузки, оптимистичное обновление
```
---

## 6. Оргструктура (Departments)

### 6.1. Миграция: `departments`

```php
Schema::create('departments', function (Blueprint $table) {
    $table->$table->id();
    $table->string('organization_code', 10);
    $table->string('department_index', 5)->comment('Индекс отдела');
    $table->string('name');
    $table->string('short_name', 50)->nullable();
    $table->boolean('use_card')->default(false)->comment('Использовать карточку отдела');
    $table->integer('sort_order')->default(0);
    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->timestamptz('created_at')->useCurrent();
    $table->timestamptz('updated_at')->useCurrent();
    $table->foreign('organization_code')->references('code')->on('organizations')->cascadeOnDelete();
    $table->foreign('created_by')->references('id')->on('employees')->nullOnDelete();
    $table->foreign('updated_by')->references('id')->on('employees')->nullOnDelete();
});
```

### 6.2. Pivot: `department_employee`

```php
Schema::create('department_employee', function (Blueprint $table) {
    $table->unsignedBigInteger('department_id');
    $table->unsignedBigInteger('employee_id');
    $table->string('position')->nullable()->comment('Должность в отделе');
    $table->string('rank')->nullable()->comment('Чин в отделе');
    $table->string('phone')->nullable()->comment('Телефон (если отличается от основного)');
    $table->text('responsibilities')->nullable()->comment('Обязанности');
    $table->integer('sort_order')->default(0);
    $table->string('photo_path')->nullable()->comment('Фото для карточки отдела');
    $table->primary(['department_id', 'employee_id']);
    $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();
    $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
});
```

### 6.3. CRUD отделов

- Админ-часть: создание/редактирование отделов
- Добавление сотрудников в отдел (Ant Design Select с поиском)
- Сортировка сотрудников drag-n-drop (`@dnd-kit`)
- Загрузка фото сотрудников для карточек отделов

### 6.4. Публичный просмотр

- Страница отдела: карточки сотрудников (Ant Card с аватаром, ФИО, должность, контакты)
- Фильтрация по организации

---

## 7. Телефонный справочник

### 7.1. Основан на `employees` + `departments` из AD

Так как все сотрудники импортируются из AD в `employees`, телефонный справочник — это поисковый интерфейс над этой таблицей.

### 7.2. Древовидная структура организации

Строится на основе поля `department_name` в `employees` и связей `department_employee`:
- `organization_code` — организация
- `department_name` — название отдела (из AD)
- `position` — должность

**Отображение:** Ant Design Tree: узлы первого уровня — организации, второго — отделы (группировка по `department_name`), третьего — сотрудники.

### 7.3. Поиск

```php
// GET /api/employees/search?q=...
Employee::where('is_active', true)
    ->whereRaw("search_vector @@ plainto_tsquery('russian', ?)", [$query])
    ->orderByRaw("ts_rank(search_vector, plainto_tsquery('russian', ?)) DESC", [$query])
    ->limit(20)
    ->get(['id', 'full_name', 'position', 'phone', 'phone_additional',
           'office', 'email', 'department_name', 'organization_code', 'photo_path']);
```

### 7.4. React-компонент

- `EmployeeSearch` — Ant Design `AutoComplete` с debounce 300ms
- При выборе сотрудника → карточка (Card / Drawer) с полной информацией
- Фото загружается через `photo_path` (если нет → default avatar)
- Кеширование фото (браузерное + CDN)
---

## 8. Декларационная кампания УСН

### 8.1. Миграция: `declare_campaign_usn`

```php
Schema::create('declare_campaign_usn', function (Blueprint $table) {
    $table->$table->id();
    $table->string('year', 4);
    $table->date('date')->comment('Дата ввода');
    $table->date('deadline')->comment('Срок уплаты');
    $table->string('organization_code', 10);
    $table->integer('np_count')->comment('Количество НП (ед.)');
    $table->integer('np_reliable_count')->comment('НП, представивших верные Уведомления (ед.)');
    $table->integer('np_not_required_count')->comment('НП, которым Уведомление не требуется (ед.)');
    $table->decimal('accrued_amount', 15, 2)->nullable()->comment('Сумма начисленного налога, руб.');
    $table->unsignedBigInteger('created_by')->nullable();
    $table->timestamptz('created_at')->useCurrent();
    $table->timestamptz('updated_at')->useCurrent();
    $table->foreign('organization_code')->references('code')->on('organizations')->cascadeOnDelete();
    $table->foreign('created_by')->references('id')->on('employees')->nullOnDelete();
    $table->unique(['year', 'date', 'deadline', 'organization_code']);
});
```

### 8.2. Модель с вычисляемыми полями

```php
class DeclareCampaignUsn extends Model
{
    // Computed: previous_date, previous_np_reliable_count, previous_np_not_required_count
    public function getPreviousDataAttribute(): ?array
    {
        return self::where('organization_code', $this->organization_code)
            ->where('deadline', $this->deadline)
            ->where('date', '<', $this->date)
            ->orderBy('date', 'desc')
            ->first();
    }
}
```

### 8.3. Групповое создание/редактирование

- **Роут:** GET/POST `/admin/declare-campaign-usn/form`
- **Фильтр:** выбор года (`year`), даты (`date`), срока уплаты (`deadline`)
- **При сабмите:** создаются (или загружаются) модели для всех организаций (код 86__)
- **React:** Ant Design editable Table — массовый ввод по всем организациям

### 8.4. Публичный просмотр

- **GET `/declare-campaign-usn`** — сводная таблица с группировкой по `deadline`
- Последние данные (кешируются в Redis)
- Сравнение с предыдущим периодом

### 8.5. Роль модератора

Проврка через Gae: `$user->can('acces-grup', 'declare-campaign-usn-moderator')`
---

## 9. Автоматизация рутины

### 9.1. Миграция: `automation_routines`

```php
Schema::create('automation_routines', function (Blueprint $table) {
    $table->$table->id();
    $table->string('title', 300);
    $table->text('description')->nullable();
    $table->string('region_mail_account', 300)->nullable()->comment('Реквизиты письма Управления');
    $table->string('owner_department', 250)->comment('Владелец (структурное подразделение)');
    $table->string('ftp_path', 250)->nullable();
    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->timestamptz('created_at')->useCurrent();
    $table->timestamptz('updated_at')->useCurrent();
    $table->foreign('created_by')->references('id')->on('employees')->nullOnDelete();
    $table->foreign('updated_by')->references('id')->on('employees')->nullOnDelete();
});
```

### 9.2. Миграции: downloads, rates, feedbacks

```php
// Загрузки ПМ
Schema::create('automation_routine_downloads', function (Blueprint $table) {
    $table->$table->id();
    $table->unsignedBigInteger('automation_routine_id');
    $table->string('filename', 500);
    $table->unsignedBigInteger('employee_id');
    $table->timestamptz('created_at')->useCurrent();
    $table->foreign('automation_routine_id')->references('id')->on('automation_routines')->cascadeOnDelete();
    $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
});

// Оценки (1-5)
Schema::create('automation_routine_rates', function (Blueprint $table) {
    $table->$table->id();
    $table->unsignedBigInteger('automation_routine_id');
    $table->smallInteger('rate')->comment('1-5');
    $table->unsignedBigInteger('employee_id');
    $table->timestamptz('created_at')->useCurrent();
    $table->foreign('automation_routine_id')->references('id')->on('automation_routines')->cascadeOnDelete();
    $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
    $table->unique(['automation_routine_id', 'employee_id']);
});

// Фидбек (reject/accepted)
Schema::create('automation_routine_feedbacks', function (Blueprint $table) {
    $table->$table->id();
    $table->unsignedBigInteger('automation_routine_id');
    $table->string('result', 30)->comment('reject|accepted');
    $table->unsignedBigInteger('employee_id');
    $table->timestamptz('created_at')->useCurrent();
    $table->foreign('automation_routine_id')->references('id')->on('automation_routines')->cascadeOnDelete();
    $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
    $table->unique(['automation_routine_id', 'employee_id']);
});
```

### 9.3. CRUD + Медиа

- Модель `AutomationRoutine` использует Spatie Media Library:
  - `program_files` — загружаемые ПМ (программные модули)
  - `instruction` — инструкция (single file)

### 9.4. Система оценок

- **API:** POST `/api/automation-routine/{id}/rate` — body: `{ rate: 1-5 }`
- **Toggle:** повторный POST с тем же значением → удаляет оценку; с другим → обновляет
- **Статистика:** count + avg (кешируется)

### 9.5. React-виджет уведомлений

- Ant Design `Notification` + `useHttp`
- **Логика проверки (на backend):**
  1. Сотрудник скачал ПМ но не оценил → предлагаем оценить
  2. Сотрудник просматривал ПМ но не скачал и не отказался → предлагаем скачать
  3. Активность за последние N дней (default: 7)
- **Кнопки:**
  - «Перейти» → `router.visit('/automation-routine/{id}')`
  - «Не хочу» → POST `/api/automation-routine/{id}/reject` → закрыть
  - «Закрыть» → POST `/api/automation-routine/close` → закрыть + cookie
---

## 10. Наставничество

### 10.1. Миграции

```php
// mentor_ways — направления наставничества
Schema::create('mentor_ways', function (Blueprint $table) {
    $table->$table->id();
    $table->string('name', 200);
    $table->timestamptz('created_at')->useCurrent();
    $table->timestamptz('updated_at')->useCurrent();
});

// mentor_posts — посты наставников
Schema::create('mentor_posts', function (Blueprint $table) {
    $table->$table->id();
    $table->unsignedBigInteger('mentor_way_id');
    $table->string('organization_code', 10);
    $table->string('title', 500);
    $table->text('body');
    $table->unsignedBigInteger('created_by')->nullable();
    $table->integer('likes_count')->default(0);
    $table->integer('comments_count')->default(0);
    $table->integer('visits_count')->default(0);
    $table->timestamptz('created_at')->useCurrent();
    $table->timestamptz('updated_at')->useCurrent();
    $table->timestamptz('deleted_at')->nullable();
    $table->foreign('mentor_way_id')->references('id')->on('mentor_ways')->cascadeOnDelete();
    $table->foreign('organization_code')->references('code')->on('organizations')->cascadeOnDelete();
    $table->foreign('created_by')->references('id')->on('employees')->nullOnDelete();
});

// mentor_role_assignments — назначение наставников
Schema::create('mentor_role_assignments', function (Blueprint $table) {
    $table->$table->id();
    $table->string('role_name', 100);
    $table->unsignedBigInteger('employee_id');
    $table->timestamptz('created_at')->useCurrent();
    $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
});
```

### 10.2. Полиморфные лайки и комментарии

Вместо отдельных таблиц (`mentor_post_like`, `mentor_post_visit`) — полиморфные:

```php
Schema::create('likes', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('employee_id');
    $table->morphs('likable');
    $table->timestamptz('created_at')->useCurrent();
    $table->unique(['employee_id', 'likable_type', 'likable_id']);
    $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
});
```

### 10.3. CRUD и публичная часть

- CRUD постов с файлами (Spatie Media Library)
- Управление направлениями (`mentor_ways`)
- Назначение наставников (`mentor_role_assignments`)
- Публичная часть: список постов по направлениям, просмотр, лайки, комментарии, Inertia-навигация
---

## 11. Тестирование сотрудников

### 11.1. Миграции

```php
// tests — тесты
Schema::create('tests', function (Blueprint $table) {
    $table->$table->id();
    $table->string('name', 250);
    $table->text('description')->nullable();
    $table->timestamptz('started_at');
    $table->timestamptz('expired_at');
    $table->smallInteger('attempts_limit')->nullable()->comment('Лимит попыток');
    $table->smallInteger('questions_count')->default(0);
    $table->smallInteger('time_limit_minutes')->nullable();
    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->timestamptz('created_at')->useCurrent();
    $table->timestamptz('updated_at')->useCurrent();
    $table->foreign('created_by')->references('id')->on('employees')->nullOnDelete();
    $table->foreign('updated_by')->references('id')->on('employees')->nullOnDelete();
});

// test_questions — вопросы
Schema::create('test_questions', function (Blueprint $table) {
    $table->$table->id();
    $table->unsignedBigInteger('test_id');
    $table->text('question_text');
    $table->smallInteger('question_type')->default(1)->comment('1: single, 2: multiple');
    $table->string('attach_file_path', 200)->nullable();
    $table->smallInteger('weight')->default(1);
    $table->unsignedBigInteger('created_by')->nullable();
    $table->timestamptz('created_at')->useCurrent();
    $table->foreign('test_id')->references('id')->on('tests')->cascadeOnDelete();
    $table->foreign('created_by')->references('id')->on('employees')->nullOnDelete();
});

// test_answers — ответы
Schema::create('test_answers', function (Blueprint $table) {
    $table->$table->id();
    $table->unsignedBigInteger('question_id');
    $table->text('answer_text');
    $table->string('attach_file_path', 200)->nullable();
    $table->smallInteger('weight')->default(1)->comment('Вес правильного ответа');
    $table->timestamptz('created_at')->useCurrent();
    $table->foreign('question_id')->references('id')->on('test_questions')->cascadeOnDelete();
});

// test_results — результаты
Schema::create('test_results', function (Blueprint $table) {
    $table->$table->id();
    $table->unsignedBigInteger('test_id');
    $table->unsignedBigInteger('employee_id');
    $table->string('organization_code', 10);
    $table->timestamptz('created_at')->useCurrent();
    $table->foreign('test_id')->references('id')->on('tests')->cascadeOnDelete();
    $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
    $table->foreign('organization_code')->references('code')->on('organizations')->cascadeOnDelete();
});
```
### 11.2. Конструктор тестов

- **React Wizard:** Ant Design Steps для пошагового создания:
  - Шаг 1: основные параметры (название, описание, сроки, лимиты)
  - Шаг 2+: добавление вопросов с ответами (динамически)
- **Типы вопросов:** одиночный выбор (radio) / множественный (checkboxes)
- **Вес:** у каждого ответа — `weight` (0 для неправильных, >0 для правильных)
- **Загрузка файлов:** прикрепление файла к вопросу (чертеж, схема)

### 11.3. Прохождение тестов

- **Старт:** проверка — не истек ли срок, не исчерпаны ли попытки
- **Таймер:** React-компонент с отсчётом времени (если установлен `time_limit_minutes`)
- **Форма:** один вопрос на экране или все сразу (настройка в тесте)
- **Сабмит:** отправка всех ответов → сохранение в `test_results`

### 11.4. Результаты и статистика

- Сумма весов вопросов / набранный вес = процент
- Статистика по организациям (React-чарт)
- Доступ к статистике: Gate `can('access-group', 'test-statistic-viewer')`
---

## 12. Технические стандарты

### 12.1. Типы данных PostgreSQL

| Тип | Назначение |
|---|---|
| `bigIncrements` | Все первичные ключи |
| `timestamptz` | Все даты/время |
| `jsonb` | Гибкие данные (AD, настройки) |
| `boolean` | Флаги |
| `text` / `varchar` | Строки |
| `tsvector` + GIN | Полнотекстовый поиск |
### 12.2. Телефонный справочник

Телефонный справочник работает через AD и PostgreSQL (см. раздел 7).

### 12.3. Inertia-паттерны

- Навигация без перезагрузки: Inertia `visit()` / `router.get()`
- Фоновые запросы: `useHttp` (встроен в Inertia 3.x)
- Частичные перерисовки: Inertia `partial` reloads
- Модальные окна: Ant Design Modal + `preserveState`
- Формы: Ant Design `Form` + Inertia `useForm`

### 12.4. Ant Design-паттерны

- Таблицы с серверной пагинацией: `Table` с `onChange`
- Пагинация на стороне сервера: Laravel `paginate()` + API-ресурсы
- Действия над строками: Ant `Dropdown` в последней колонке
- Валидация: Laravel FormRequest → ошибки в Inertia props → `Form` отображает

### 12.5. Полнотекстовый поиск

- PostgreSQL `tsvector` + GIN индекс (без отдельного поискового движка)
- Для справочника: `employees.search_vector`
- Для новостей: `news.search_vector`
- На будущее: `pgvector` (векторный поиск) или `parade_search` (Laravel Scout для PostgreSQL)
---

## 13. Приоритеты реализации

### Этап 1 — Фундамент (2-3 недели)

- Миграция: `employees`, `organizations`, `employee_organization`, `portal_users`
- AD-синхронизация (scheduled command)
- Аутентификация (логин/пароль через AD/LDAP)
- Миграция: `access_groups`, `access_group_employee`, `access_group_ad_groups`
- Gate + Spatie Permission для ролей
- Базовый layout (Inertia + Ant ConfigProvider, Layout, Sider)
- Меню (`menu_items` + React Tree)

### Этап 2 — Основной контент (3-4 недели)

- Категории (`categories` Nested Set)
- Новости (CRUD + публичный просмотр + лайки)
- Оргструктура (CRUD отделов + карточки сотрудников)
- Телефонный справочник (поиск по `employees`)

### Этап 3 — Бизнес-модули (2-3 недели)

- Декларационная кампания УСН
- Автоматизация рутины (CRUD + оценки + уведомления)

### Этап 4 — Дополнительные модули (2-3 недели)

- Наставничество
- Тестирование сотрудников
- Spatie Activity Log + финальная полировка
