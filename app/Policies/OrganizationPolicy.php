<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\PortalUser;

class OrganizationPolicy
{
    /**
     * Determine if the user can view organizations list.
     */
    public function view(?PortalUser $user): bool
    {
        return true; // Organizations are visible to all authenticated users
    }

    /**
     * Determine if the user can create organizations.
     */
    public function create(PortalUser $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can update the organization.
     */
    public function update(PortalUser $user, ?Organization $organization): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can delete the organization.
     */
    public function delete(PortalUser $user, ?Organization $organization): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can manage access groups for organizations.
     */
    public function manageAccess(PortalUser $user): bool
    {
        return $user->can('access-group', 'organization-editor');
    }
}
