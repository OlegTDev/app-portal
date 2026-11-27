<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PortalUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PortalUser>
 */
class PortalUserFactory extends Factory
{
    protected $model = PortalUser::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'password' => bcrypt('password'),
            'organization_code' => null,
            'blocked' => false,
            'last_login_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
