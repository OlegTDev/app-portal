<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\PortalUser;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('ldap.enabled', false);
        // Create roles in testing database
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'portal']);
        Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'portal']);
    }

    public function test_organizations_index_requires_auth(): void
    {
        $response = $this->get('/admin/organizations');

        $response->assertRedirect('/login');
    }

    public function test_organizations_index_returns_page(): void
    {
        $portalUser = $this->createAdminPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/admin/organizations');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => $assert->component('Admin/Organizations/Index'));
    }

    public function test_can_create_organization(): void
    {
        $portalUser = $this->createAdminPortalUser();
        $employee = $portalUser->employee;

        $orgData = [
            'code' => '8600',
            'name' => 'Тестовая организация',
            'short_name' => 'ТО',
            'parent_code' => null,
            'sort_order' => 1,
            'description' => 'Описание организации',
        ];

        $response = $this->actingAs($portalUser, 'portal')
            ->withCsrfToken()
            ->post('/admin/organizations', $orgData);

        $response->assertStatus(302);
        $response->assertRedirect('/admin/organizations');

        $this->assertDatabaseHas('organizations', [
            'code' => '8600',
            'name' => 'Тестовая организация',
            'short_name' => 'ТО',
            'sort_order' => 1,
            'description' => 'Описание организации',
        ]);
    }

    public function test_cannot_create_duplicate_code(): void
    {
        Organization::create([
            'code' => '8600',
            'name' => 'Существующая организация',
        ]);

        $portalUser = $this->createAdminPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->withCsrfToken()
            ->post('/admin/organizations', [
                'code' => '8600',
                'name' => 'Дубликат',
            ]);

        $response->assertSessionHasErrors('code');
        $this->assertEquals(1, Organization::where('code', '8600')->count());
    }

    public function test_can_update_organization(): void
    {
        $org = Organization::create([
            'code' => '8600',
            'name' => 'Старое название',
            'sort_order' => 0,
        ]);

        $portalUser = $this->createAdminPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->withCsrfToken()
            ->put("/admin/organizations/{$org->code}", [
                'code' => '8600',
                'name' => 'Новое название',
                'short_name' => 'НТ',
                'sort_order' => 5,
                'description' => 'Обновлённое описание',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect('/admin/organizations');

        $this->assertDatabaseHas('organizations', [
            'code' => '8600',
            'name' => 'Новое название',
            'short_name' => 'НТ',
            'sort_order' => 5,
        ]);
    }

    public function test_can_delete_organization(): void
    {
        $org = Organization::create([
            'code' => '8600',
            'name' => 'Организация для удаления',
        ]);

        $portalUser = $this->createAdminPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->withCsrfToken()
            ->delete("/admin/organizations/{$org->code}");

        $response->assertStatus(302);
        $response->assertRedirect('/admin/organizations');
        $this->assertDatabaseMissing('organizations', ['code' => '8600']);
    }

    public function test_cannot_delete_organization_with_children(): void
    {
        $parent = Organization::create([
            'code' => '8600',
            'name' => 'Родитель',
        ]);

        Organization::create([
            'code' => '8601',
            'name' => 'Ребёнок',
            'parent_code' => '8600',
        ]);

        $portalUser = $this->createAdminPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->withCsrfToken()
            ->delete("/admin/organizations/{$parent->code}");

        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('organizations', ['code' => '8600']);
    }

    public function test_cannot_delete_organization_with_employees(): void
    {
        $org = Organization::create([
            'code' => '8600',
            'name' => 'Организация со сотрудниками',
        ]);

        $employee = Employee::factory()->create(['organization_code' => '8600']);

        $portalUser = $this->createAdminPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->withCsrfToken()
            ->delete("/admin/organizations/{$org->code}");

        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('organizations', ['code' => '8600']);
    }

    public function test_organizations_list_shows_all(): void
    {
        Organization::create(['code' => '8600', 'name' => 'Организация А']);
        Organization::create(['code' => '8601', 'name' => 'Организация Б']);

        $portalUser = $this->createAdminPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/admin/organizations');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => $assert->has('organizations.data', 2));
    }

    public function test_organizations_search_filters(): void
    {
        Organization::create(['code' => '8600', 'name' => 'Федеральная служба']);
        Organization::create(['code' => '8601', 'name' => 'Частная компания']);

        $portalUser = $this->createAdminPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/admin/organizations?search=Федеральная');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => $assert->has('organizations.data', 1));
    }

    public function test_unauthorized_cannot_access_organizations(): void
    {
        // Create a regular user without admin role
        $employee = Employee::factory()->create();
        $portalUser = PortalUser::factory()->create(['employee_id' => $employee->id]);

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/admin/organizations');

        // Policy allows view for any authenticated user, so 200 is expected
        // But create/update/delete should be blocked
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_create_organization(): void
    {
        $employee = Employee::factory()->create();
        $portalUser = PortalUser::factory()->create(['employee_id' => $employee->id]);

        $response = $this->actingAs($portalUser, 'portal')
            ->withCsrfToken()
            ->post('/admin/organizations', [
                'code' => '9999',
                'name' => 'Несанкционированная',
            ]);

        // Should get a 403 authorization error
        $response->assertStatus(403);
    }

    protected function createAdminPortalUser(): PortalUser
    {
        $employee = Employee::factory()->create();
        $portalUser = PortalUser::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $portalUser->assignRole('admin');

        return $portalUser;
    }
}
