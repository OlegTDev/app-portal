<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\PortalUser;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('ldap.enabled', false);
        // Create roles in testing database
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'portal']);
        Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'portal']);
        // Clean employees to avoid count interference from previous tests
        \App\Models\Employee::query()->delete();
    }

    public function test_employees_index_requires_auth(): void
    {
        $response = $this->get('/admin/employees');

        $response->assertRedirect('/login');
    }

    public function test_employees_index_returns_page(): void
    {
        $portalUser = $this->createAdminPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/admin/employees');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => $assert->component('Admin/Employees/Index'));
    }

    public function test_employees_list_shows_all(): void
    {
        $e1 = Employee::factory()->create(['full_name' => 'Иванов Иван']);
        $e2 = Employee::factory()->create(['full_name' => 'Петров Петр']);

        $portalUser = $this->createAdminPortalUser($e1);

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/admin/employees');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => $assert->has('employees.data', 2));
    }

    public function test_employees_search_filters(): void
    {
        $e1 = Employee::factory()->create(['full_name' => 'Иванов Иван', 'position' => 'Разработчик']);
        $e2 = Employee::factory()->create(['full_name' => 'Петров Петр', 'position' => 'Менеджер']);

        $portalUser = $this->createAdminPortalUser($e1);

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/admin/employees?search=Иванов');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => $assert->has('employees.data', 1));
    }

    public function test_employees_filter_by_active_status(): void
    {
        $e1 = Employee::factory()->create(['full_name' => 'Активный', 'is_active' => true]);
        $e2 = Employee::factory()->create(['full_name' => 'Уволенный', 'is_active' => false]);

        $portalUser = $this->createAdminPortalUser($e1);

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/admin/employees?is_active=1');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => $assert->has('employees.data', 1));
    }

    public function test_employee_show(): void
    {
        $employee = Employee::factory()->create([
            'full_name' => 'Иванов Иван',
            'position' => 'Разработчик',
            'email' => 'ivanov@domain.local',
            'phone' => '+7-999-111-1111',
            'office' => 'Офис 205',
        ]);

        $portalUser = $this->createAdminPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->get("/admin/employees/{$employee->id}");

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($assert) => $assert
                ->component('Admin/Employees/Show')
                ->where('employee.id', $employee->id)
                ->where('employee.full_name', 'Иванов Иван')
                ->where('employee.position', 'Разработчик')
                ->where('employee.email', 'ivanov@domain.local')
                ->where('employee.phone', '+7-999-111-1111')
                ->where('employee.office', 'Офис 205')
        );
    }

    public function test_employee_show_with_organizations(): void
    {
        $org = Organization::create(['code' => '8600', 'name' => 'Тестовая организация']);
        $employee = Employee::factory()->create([
            'full_name' => 'Иванов Иван',
            'organization_code' => '8600',
        ]);

        // Link employee to organization via pivot (composite key)
        DB::table('employee_organization')->insert([
            'employee_id' => $employee->id,
            'organization_code' => $org->code,
        ]);

        $portalUser = $this->createAdminPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->get("/admin/employees/{$employee->id}");

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($assert) => $assert
                ->where('employee.organizations.0.code', '8600')
                ->where('employee.organizations.0.name', 'Тестовая организация')
        );
    }

    public function test_employee_show_not_found(): void
    {
        $portalUser = $this->createAdminPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/admin/employees/999999');

        $response->assertStatus(404);
    }

    public function test_inactive_employees_still_visible(): void
    {
        Employee::factory()->create(['full_name' => 'Уволенный сотрудник', 'is_active' => false]);

        $portalUser = $this->createAdminPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/admin/employees');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => $assert->has('employees.data', 1));
    }

    protected function createAdminPortalUser(?Employee $employee = null): PortalUser
    {
        if (!$employee) {
            $employee = Employee::factory()->create();
        }

        $portalUser = PortalUser::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $portalUser->assignRole('admin');

        return $portalUser;
    }
}
