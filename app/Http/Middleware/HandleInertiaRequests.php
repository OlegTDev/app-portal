<?php

namespace App\Http\Middleware;

use App\Models\MenuItem;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests
{
    /**
     * Share common data with Inertia pages.
     */
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share(array_merge($this->shareAuth($request), [
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'menu' => fn () => $this->shareMenu($request),
        ]));

        return $next($request);
    }

    /**
     * Share authentication data with Inertia pages.
     */
    protected function shareAuth(Request $request): array
    {
        return [
            'auth' => function () use ($request) {
                $user = $request->user('portal');

                if (!$user instanceof \App\Models\PortalUser) {
                    return null;
                }

                /** @var \App\Models\Employee|null $employee */
                $employee = $user->employee;

                return [
                    'user' => [
                        'id' => $user->id,
                        'username' => $employee?->username,
                        'full_name' => $employee?->full_name,
                        'photo_path' => $employee?->photo_path,
                        'roles' => $user->roles->pluck('name')->toArray(),
                        'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    ],
                ];
            },
        ];
    }

    /**
     * Share menu data with Inertia pages (cached in Redis).
     */
    protected function shareMenu(Request $request): array
    {
        $mainMenu = Cache::remember('menu:main', 3600, function () {
            return $this->buildMenuTree('main');
        });

        $leftMenu = Cache::remember('menu:left', 3600, function () {
            return $this->buildMenuTree('left');
        });

        return [
            'main' => $mainMenu,
            'left' => $leftMenu,
        ];
    }

    /**
     * Build menu tree for a given position.
     */
    protected function buildMenuTree(string $position): array
    {
        $items = MenuItem::enabled()
            ->where('position', $position)
            ->with('children')
            ->orderRoot()
            ->get();

        return $this->buildTree($items);
    }

    /**
     * Recursively build menu tree from flat collection.
     *
     * @param  \Illuminate\Support\Collection  $items
     * @return array<int, array{key: mixed, label: mixed, icon: mixed, link: mixed, target: mixed, children: array, sort_order?: int}>
     */
    protected function buildTree(\Illuminate\Support\Collection $items, ?string $parentId = null): array
    {
        $tree = [];

        foreach ($items as $item) {
            if ($item->parent_id === $parentId) {
                $tree[] = [
                    'key' => $item->id,
                    'label' => $item->name,
                    'icon' => $item->icon,
                    'link' => $item->link,
                    'target' => $item->target,
                    'sort_order' => $item->sort_order ?? 0,
                    'children' => $this->buildTree($items, $item->id),
                ];
            }
        }

        // Sort by sort_order
        usort($tree, fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        return $tree;
    }
}
