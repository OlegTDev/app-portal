<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\PortalUser;

class MenuItemPolicy
{
    /**
     * Determine if the user can view menu items.
     */
    public function view(?PortalUser $user, MenuItem $menuItem): bool
    {
        return $menuItem->is_enabled;
    }

    /**
     * Determine if the user can create menu items.
     */
    public function create(PortalUser $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can update the menu item.
     */
    public function update(PortalUser $user, MenuItem $menuItem): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can delete the menu item.
     */
    public function delete(PortalUser $user, MenuItem $menuItem): bool
    {
        return $user->hasRole('admin');
    }
}
