<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    /**
     * The default CSRF token closure/seed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF verification during testing
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        // Disable Inertia page existence check during testing
        // (pages live in resources/ts/Pages, not resources/js/Pages)
        $this->app['config']->set('inertia.testing.ensure_pages_exist', false);

        // For PostgreSQL: disable FK checks before dropping tables
        if ($this->app['config']->get('database.default') === 'pgsql') {
            $this->app['db']->connection()->statement('SET session_replication_role = replica');

            // Re-enable FK checks after migration
            $this->beforeApplicationDestroyed(function () {
                $this->app['db']->connection()->statement('SET session_replication_role = DEFAULT');
            });
        }
    }

    /**
     * Create a new test request with a valid CSRF token.
     */
    protected function withCsrfToken(): static
    {
        $this->startSession();

        return $this->withHeader('X-CSRF-TOKEN', $this->app['session.store']->token())
                     ->withHeader('X-XSRF-TOKEN', $this->app['session.store']->token());
    }

    /**
     * Refresh the database state with PostgreSQL-safe table dropping.
     */
    protected function refreshDatabase(): void
    {
        $this->migrateFresh();
        $this->beginDatabaseTransaction();
    }

    /**
     * Run the fresh migration command with PostgreSQL-safe table dropping.
     */
    protected function migrateFresh(): void
    {
        $driver = $this->app['config']->get('database.default');

        // For PostgreSQL: disable FK checks before dropping tables
        if ($driver === 'pgsql') {
            $this->app['db']->connection()->statement('SET session_replication_role = replica');
        }

        // Run migrations
        Artisan::call('migrate:fresh', [
            '--force' => true,
            '--path' => database_path('migrations'),
            '--realpath' => true,
        ]);

        // Also run spatie/laravel-permission migrations
        Artisan::call('migrate', [
            '--force' => true,
            '--path' => base_path('vendor/spatie/laravel-permission/database/migrations'),
            '--realpath' => true,
        ]);

        // Re-enable FK checks after migration
        if ($driver === 'pgsql') {
            $this->app['db']->connection()->statement('SET session_replication_role = DEFAULT');
        }
    }
}
