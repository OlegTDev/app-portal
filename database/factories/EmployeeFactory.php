<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'full_name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'phone' => fake()->phoneNumber(),
            'office' => 'Офис ' . fake()->numberBetween(100, 999),
            'department_name' => fake()->word(),
            'organization_code' => null,
            'photo_path' => null,
            'ad_data' => [],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
