<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index(Request $request): Response
    {
        $this->authorize('view', Employee::class);

        $query = Employee::with(['organizations'])
            ->select(['id', 'username', 'full_name', 'position', 'phone', 'phone_additional',
                     'office', 'department_name', 'organization_code', 'photo_path', 'is_active', 'created_at']);

        // Filter by active status (default: show active only)
        if (!$request->filled('is_active')) {
            $query->where('is_active', true);
        } elseif ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        // Search by name, username, position
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'ilike', "%{$search}%")
                    ->orWhere('username', 'ilike', "%{$search}%")
                    ->orWhere('position', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        $employees = $query->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        // Transform for Inertia
        $employees->getCollection()->transform(function (Employee $employee) {
            return [
                'id' => $employee->id,
                'username' => $employee->username,
                'full_name' => $employee->full_name,
                'position' => $employee->position,
                'phone' => $employee->phone,
                'phone_additional' => $employee->phone_additional,
                'office' => $employee->office,
                'department_name' => $employee->department_name,
                'organization_code' => $employee->organization_code,
                'photo_path' => $employee->photo_path,
                'is_active' => $employee->is_active,
                'organizations' => $employee->organizations->map(fn ($org) => [
                    'code' => $org->getAttributes()['code'] ?? null,
                    'name' => $org->getAttributes()['name'] ?? null,
                ]),
                'created_at' => $employee->created_at?->toDateTimeString(),
            ];
        });

        $organizations = Organization::orderBy('sort_order')
            ->orderBy('name')
            ->get(['code', 'name']);

        return Inertia::render('Admin/Employees/Index', [
            'employees' => $employees,
            'organizations' => $organizations,
            'filters' => [
                'organization_code' => $request->input('organization_code'),
                'is_active' => $request->input('is_active'),
                'search' => $request->input('search'),
            ],
        ]);
    }

    /**
     * Display the specified employee profile.
     */
    public function show(string $id): Response
    {
        $this->authorize('viewProfile', new Employee());

        $employee = Employee::with(['organizations'])
            ->where('id', $id)
            ->firstOrFail();

        $data = [
            'id' => $employee->id,
            'full_name' => $employee->full_name,
            'position' => $employee->position,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'office' => $employee->office,
            'organizations' => $employee->organizations->map(fn ($org) => [
                'code' => $org->getAttributes()['code'] ?? null,
                'name' => $org->getAttributes()['name'] ?? null,
            ]),
        ];

        return Inertia::render('Admin/Employees/Show', ['employee' => $data]);
    }
}
