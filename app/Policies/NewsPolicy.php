<?php

namespace App\Policies;

use App\Models\News;
use App\Models\PortalUser;

class NewsPolicy
{
    /**
     * Determine if the user can view the news.
     */
    public function view(?PortalUser $user, News $news): bool
    {
        if (!$user) {
            return false;
        }

        // Published and not expired
        return $news->is_enabled
            && $news->published_at <= now()
            && $news->expired_at >= now();
    }

    /**
     * Determine if the user can create news.
     */
    public function create(PortalUser $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('moderator');
    }

    /**
     * Determine if the user can update the news.
     */
    public function update(PortalUser $user, News $news): bool
    {
        if (!$user->hasRole('admin')) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can delete the news.
     */
    public function delete(PortalUser $user, News $news): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can manage access groups for news.
     */
    public function manageAccess(PortalUser $user): bool
    {
        return $user->can('access-group', 'news-editor');
    }
}
