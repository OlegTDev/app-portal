<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\PortalUser;

class DepartmentPolicy
{
    /**
     * Determine if the user can view the department.
     */
    public function view(?PortalUser $user, Department $department): bool
    {
        return true; // Departments are public
    }

    /**
     * Determine if the user can create departments.
     */
    public function create(PortalUser $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('moderator');
    }

    /**
     * Determine if the user can update the department.
     */
    public function update(PortalUser $user, Department $department): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can delete the department.
     */
    public function delete(PortalUser $user, Department $department): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can manage access groups for departments.
     */
    public function manageAccess(PortalUser $user): bool
    {
        return $user->can('access-group', 'department-editor');
    }
}
