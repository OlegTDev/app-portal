<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PortalUser;
use Tests\TestCase;

class EmployeeSearchTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('ldap.enabled', false);
    }

    public function test_search_requires_auth(): void
    {
        $response = $this->get('/api/employees/search?q=ivanov');

        $response->assertUnauthorized();
    }

    public function test_search_returns_results(): void
    {
        Employee::factory()->create([
            'full_name' => 'Иванов Иван',
            'position' => 'Разработчик',
            'phone' => '+7-999-111-1111',
            'email' => 'ivanov@domain.local',
            'is_active' => true,
        ]);

        Employee::factory()->create([
            'full_name' => 'Петров Петр',
            'position' => 'Менеджер',
            'is_active' => true,
        ]);

        $portalUser = $this->createPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/api/employees/search?q=Иванов');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('Иванов Иван', $data[0]['full_name']);
    }

    public function test_search_returns_empty_for_no_match(): void
    {
        Employee::factory()->create(['full_name' => 'Иванов Иван']);

        $portalUser = $this->createPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/api/employees/search?q=zzzzz');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json());
    }

    public function test_search_returns_empty_for_short_query(): void
    {
        Employee::factory()->create(['full_name' => 'Иванов Иван']);

        $portalUser = $this->createPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/api/employees/search?q=И');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json());
    }

    public function test_search_only_returns_active_employees(): void
    {
        Employee::factory()->create([
            'full_name' => 'Активный',
            'is_active' => true,
        ]);
        Employee::factory()->create([
            'full_name' => 'Уволенный',
            'is_active' => false,
        ]);

        $portalUser = $this->createPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/api/employees/search?q=Активный');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('Активный', $data[0]['full_name']);
    }

    public function test_search_response_contains_correct_fields(): void
    {
        Employee::factory()->create([
            'full_name' => 'Иванов Иван',
            'position' => 'Разработчик',
            'phone' => '+7-999-111-1111',
            'phone_additional' => '+7-999-222-2222',
            'office' => 'Офис 205',
            'email' => 'ivanov@domain.local',
            'department_name' => 'IT',
            'organization_code' => '8600',
            'photo_path' => '/photos/ivanov.jpg',
            'is_active' => true,
        ]);

        $portalUser = $this->createPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/api/employees/search?q=Иванов');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('full_name', $data[0]);
        $this->assertArrayHasKey('position', $data[0]);
        $this->assertArrayHasKey('phone', $data[0]);
        $this->assertArrayHasKey('phone_additional', $data[0]);
        $this->assertArrayHasKey('office', $data[0]);
        $this->assertArrayHasKey('email', $data[0]);
        $this->assertArrayHasKey('department_name', $data[0]);
        $this->assertArrayHasKey('organization_code', $data[0]);
        $this->assertArrayHasKey('photo_path', $data[0]);
    }

    public function test_search_results_limited_to_20(): void
    {
        // Create more than 20 employees
        for ($i = 0; $i < 25; $i++) {
            Employee::factory()->create([
                'full_name' => "Сотрудник {$i}",
                'is_active' => true,
            ]);
        }

        $portalUser = $this->createPortalUser();

        $response = $this->actingAs($portalUser, 'portal')
            ->get('/api/employees/search?q=Сотрудник');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(20, $data);
    }

    protected function createPortalUser(): PortalUser
    {
        $employee = \App\Models\Employee::factory()->create();
        $portalUser = \App\Models\PortalUser::factory()->create([
            'employee_id' => $employee->id,
        ]);

        return $portalUser;
    }
}
