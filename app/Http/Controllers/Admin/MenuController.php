<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    /**
     * Display the menu management page.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Menu/Index', [
            'mainMenu' => $this->getMenuTree('main'),
            'leftMenu' => $this->getMenuTree('left'),
        ]);
    }

    /**
     * Store a newly created menu item.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'link' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
            'target' => ['nullable', 'string', 'max:10'],
            'position' => ['required', 'string', 'in:main,left'],
            'parent_id' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $validated['is_enabled'] = $validated['is_enabled'] ?? true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['created_by'] = $request->user('portal')->employee_id;
        $validated['updated_by'] = $request->user('portal')->employee_id;

        MenuItem::create($validated);

        $this->clearMenuCache();

        return redirect()->route('admin.menu.index');
    }

    /**
     * Update the specified menu item.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $menuItem = MenuItem::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'link' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
            'target' => ['nullable', 'string', 'max:10'],
            'position' => ['required', 'string', 'in:main,left'],
            'parent_id' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $validated['is_enabled'] = $validated['is_enabled'] ?? true;
        $validated['updated_by'] = $request->user('portal')->employee_id;

        $menuItem->update($validated);

        $this->clearMenuCache();

        return redirect()->route('admin.menu.index');
    }

    /**
     * Remove the specified menu item.
     */
    public function destroy(string $id): RedirectResponse
    {
        MenuItem::findOrFail($id)->delete();

        $this->clearMenuCache();

        return redirect()->route('admin.menu.index');
    }

    /**
     * Reorder menu items via drag-n-drop.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'string'],
            'items.*.parent_id' => ['nullable', 'string'],
            'items.*.sort_order' => ['required', 'integer'],
        ]);

        foreach ($validated['items'] as $itemData) {
            $menuItem = MenuItem::find($itemData['id']);

            if ($menuItem) {
                $menuItem->update([
                    'parent_id' => $itemData['parent_id'],
                    'sort_order' => $itemData['sort_order'],
                    'updated_by' => $request->user('portal')->employee_id,
                ]);
            }
        }

        $this->clearMenuCache();

        return redirect()->route('admin.menu.index');
    }

    /**
     * Get menu tree for a given position.
     */
    protected function getMenuTree(string $position): array
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
     */
    protected function buildTree(\Illuminate\Support\Collection $items, ?string $parentId = null): array
    {
        $tree = [];

        foreach ($items as $item) {
            if ($item->parent_id === $parentId) {
                $tree[] = [
                    'id' => $item->id,
                    'key' => $item->id,
                    'label' => $item->name,
                    'icon' => $item->icon,
                    'link' => $item->link,
                    'target' => $item->target,
                    'position' => $item->position,
                    'sort_order' => $item->sort_order,
                    'is_enabled' => $item->is_enabled,
                    'parent_id' => $item->parent_id,
                    'children' => $this->buildTree($items, $item->id),
                ];
            }
        }

        usort($tree, fn ($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        return $tree;
    }

    /**
     * Clear menu cache.
     */
    protected function clearMenuCache(): void
    {
        Cache::forget('menu:main');
        Cache::forget('menu:left');
    }
}
