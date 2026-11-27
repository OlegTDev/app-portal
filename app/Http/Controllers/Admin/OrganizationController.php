<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    /**
     * Display a listing of organizations.
     */
    public function index(Request $request): Response
    {
        $this->authorize('view', Organization::class);

        $query = Organization::with(['parent', 'children'])
            ->orderBy('sort_order')
            ->orderBy('name');

        // Filter by parent
        if ($request->filled('parent_code')) {
            $query->where('parent_code', $request->input('parent_code'));
        }

        // Search by name or code
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%")
                    ->orWhere('short_name', 'ilike', "%{$search}%");
            });
        }

        $organizations = $query->paginate(20)->withQueryString();

        // Build tree for display
        $allOrganizations = Organization::with(['parent', 'children'])->orderBy('sort_order')->orderBy('name')->get();
        $tree = $this->buildOrganizationTree($allOrganizations);

        return Inertia::render('Admin/Organizations/Index', [
            'organizations' => $organizations,
            'tree' => $tree,
            'parents' => $this->getOrganizationOptions(),
        ]);
    }

    /**
     * Store a newly created organization.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Organization::class);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:organizations,code'],
            'name' => ['required', 'string', 'max:200'],
            'short_name' => ['nullable', 'string', 'max:200'],
            'parent_code' => ['nullable', 'string', 'max:10', 'exists:organizations,code'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'upload_images_path' => ['nullable', 'string', 'max:500'],
            'ended_at' => ['nullable', 'date'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        Organization::create($validated);

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Организация создана');
    }

    /**
     * Update the specified organization.
     */
    public function update(Request $request, string $code): RedirectResponse
    {
        $this->authorize('update', new Organization());

        $organization = Organization::where('code', $code)->firstOrFail();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:organizations,code,' . $code . ',code'],
            'name' => ['required', 'string', 'max:200'],
            'short_name' => ['nullable', 'string', 'max:200'],
            'parent_code' => ['nullable', 'string', 'max:10', 'exists:organizations,code'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'upload_images_path' => ['nullable', 'string', 'max:500'],
            'ended_at' => ['nullable', 'date'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $organization->update($validated);

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Организация обновлена');
    }

    /**
     * Remove the specified organization.
     */
    public function destroy(string $code): RedirectResponse
    {
        $this->authorize('delete', new Organization());

        $organization = Organization::where('code', $code)->firstOrFail();

        // Check if organization has children
        if ($organization->children()->exists()) {
            return back()->withErrors(['delete' => 'Нельзя удалить организацию с дочерними организациями']);
        }

        // Check if organization has employees (via pivot table or direct organization_code)
        $hasEmployees = $organization->employees()->exists() ||
                        \App\Models\Employee::where('organization_code', $code)->exists();

        if ($hasEmployees) {
            return back()->withErrors(['delete' => 'Нельзя удалить организацию со сотрудниками']);
        }

        $organization->delete();

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Организация удалена');
    }

    /**
     * Build organization tree from flat collection.
     */
    protected function buildOrganizationTree(\Illuminate\Support\Collection $organizations, ?string $parentId = null): array
    {
        $tree = [];

        foreach ($organizations as $org) {
            if ($org->parent_code === $parentId) {
                $tree[] = [
                    'code' => $org->code,
                    'name' => $org->name,
                    'short_name' => $org->short_name,
                    'parent_code' => $org->parent_code,
                    'sort_order' => $org->sort_order,
                    'description' => $org->description,
                    'children' => $this->buildOrganizationTree($organizations, $org->code),
                ];
            }
        }

        usort($tree, fn ($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        return $tree;
    }

    /**
     * Get organization options for dropdown.
     */
    protected function getOrganizationOptions(): array
    {
        return Organization::orderBy('sort_order')
            ->orderBy('name')
            ->get(['code', 'name'])
            ->map(fn ($org) => [
                'label' => $org->name,
                'value' => $org->code,
            ])
            ->toArray();
    }
}
