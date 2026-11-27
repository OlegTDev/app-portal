<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PortalUser;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('ldap.enabled', false);
        // Create roles in testing database
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'portal']);
        Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'portal']);
    }

    public function test_login_form_returns(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => $assert->component('Auth/Login'));
    }

    public function test_successful_login_creates_employee_and_portal_user(): void
    {
        $response = $this->withCsrfToken()
            ->post('/login', [
                'username' => 'admin',
                'password' => 'any_password',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect('/');

        // Employee should be created
        $this->assertDatabaseHas('employees', [
            'username' => 'admin',
            'full_name' => 'Администратор Домена',
            'is_active' => true,
        ]);

        // PortalUser should be created
        $employee = Employee::where('username', 'admin')->first();
        $this->assertDatabaseHas('portal_users', [
            'employee_id' => $employee->id,
        ]);

        // User should be authenticated
        $this->assertAuthenticated('portal');
    }

    public function test_failed_login_shows_error(): void
    {
        $response = $this->withCsrfToken()
            ->post('/login', [
                'username' => 'nonexistent_user',
                'password' => 'any_password',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('username');
        $this->assertFalse($this->app['auth']->guard('portal')->check());
    }

    public function test_authenticated_user_redirected_from_login(): void
    {
        $employee = Employee::factory()->create(['username' => 'testuser']);
        $portalUser = PortalUser::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($portalUser, 'portal');

        $response = $this->get('/login');

        $response->assertRedirect('/');
    }

    public function test_blocked_user_cannot_login(): void
    {
        $employee = Employee::factory()->create(['username' => 'blocked_user']);
        $portalUser = PortalUser::factory()->create([
            'employee_id' => $employee->id,
            'blocked' => true,
        ]);

        // Manually create the employee record that FakeLdapService would create
        $this->assertDatabaseHas('employees', ['username' => 'blocked_user']);

        // Block the portal user
        $portalUser->update(['blocked' => true]);

        $response = $this->withCsrfToken()
            ->post('/login', [
                'username' => 'blocked_user',
                'password' => 'any_password',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('username');
    }

    public function test_logout(): void
    {
        $employee = Employee::factory()->create(['username' => 'logout_user']);
        $portalUser = PortalUser::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($portalUser, 'portal');

        $response = $this->withCsrfToken()
            ->post('/logout');

        $response->assertStatus(302);
        $response->assertRedirect('/');
        $this->assertFalse($this->app['auth']->guard('portal')->check());
    }
}
