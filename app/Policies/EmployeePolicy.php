<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\PortalUser;

class EmployeePolicy
{
    /**
     * Determine if the user can view employees list.
     */
    public function view(?PortalUser $user): bool
    {
        return true; // Employees are visible to all authenticated users
    }

    /**
     * Determine if the user can view the employee profile.
     */
    public function viewProfile(PortalUser $user, Employee $employee): bool
    {
        return true; // Any authenticated user can view any employee
    }

    /**
     * Determine if the user can update the employee.
     */
    public function update(PortalUser $user, Employee $employee): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can manage access groups for employees.
     */
    public function manageAccess(PortalUser $user): bool
    {
        return $user->can('access-group', 'employee-editor');
    }
}
