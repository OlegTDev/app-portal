<?php

namespace App\Providers;

use App\Models\PortalUser;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to gate mappings.
     */
    protected $policies = [
        \App\Models\News::class => \App\Policies\NewsPolicy::class,
        \App\Models\Department::class => \App\Policies\DepartmentPolicy::class,
        \App\Models\MenuItem::class => \App\Policies\MenuItemPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Access group gate: checks both direct membership and AD group membership
        Gate::define('access-group', function (?PortalUser $user, string $uniqueKey): bool {
            if (!$user) {
                return false;
            }

            $cacheKey = "access:{$user->id}:{$uniqueKey}";

            return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($user, $uniqueKey) {
                $employee = $user->employee;
                if (!$employee) {
                    return false;
                }

                // Direct membership
                $direct = \Illuminate\Support\Facades\DB::table('access_group_employee')
                    ->join('access_groups', 'access_group_employee.access_group_id', '=', 'access_groups.id')
                    ->where('access_groups.unique_key', $uniqueKey)
                    ->where('access_group_employee.employee_id', $employee->getKey())
                    ->where(fn ($q) => $q->whereNull('access_group_employee.expired_at')
                        ->orWhere('access_group_employee.expired_at', '>', now()))
                    ->exists();

                if ($direct) {
                    return true;
                }

                // AD group membership
                $memberOf = $employee->ad_data['memberof'] ?? [];
                if (empty($memberOf)) {
                    return false;
                }

                return \Illuminate\Support\Facades\DB::table('access_group_ad_groups')
                    ->join('access_groups', 'access_group_ad_groups.access_group_id', '=', 'access_groups.id')
                    ->where('access_groups.unique_key', $uniqueKey)
                    ->whereIn('access_group_ad_groups.group_dn', $memberOf)
                    ->where(fn ($q) => $q->whereNull('access_group_ad_groups.expired_at')
                        ->orWhere('access_group_ad_groups.expired_at', '>', now()))
                    ->exists();
            });
        });
    }
}
