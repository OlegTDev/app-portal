<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeSearchController extends Controller
{
    /**
     * Search employees by query.
     */
    public function __invoke(Request $request)
    {
        $query = $request->input('q', '');

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $employees = Employee::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('full_name', 'ilike', "%{$query}%")
                    ->orWhere('position', 'ilike', "%{$query}%")
                    ->orWhere('phone', 'ilike', "%{$query}%")
                    ->orWhere('email', 'ilike', "%{$query}%");
            })
            ->orderBy('full_name')
            ->limit(20)
            ->get([
                'id', 'full_name', 'position', 'phone', 'phone_additional',
                'office', 'email', 'department_name', 'organization_code', 'photo_path',
            ]);

        return response()->json($employees);
    }
}
