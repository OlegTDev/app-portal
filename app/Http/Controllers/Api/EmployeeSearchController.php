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

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $employees = Employee::where('is_active', true)
            ->whereRaw("search_vector @@ plainto_tsquery('russian', ?)", [$query])
            ->orderByRaw("ts_rank(search_vector, plainto_tsquery('russian', ?)) DESC", [$query])
            ->limit(20)
            ->get([
                'id', 'full_name', 'position', 'phone', 'phone_additional',
                'office', 'email', 'department_name', 'organization_code', 'photo_path',
            ]);

        return response()->json($employees);
    }
}
