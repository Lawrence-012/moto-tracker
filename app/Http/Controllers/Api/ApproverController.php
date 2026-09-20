<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;

class ApproverController extends Controller
{
    /**
     * Get University Presidents
     */
    public function universityPresident(): JsonResponse
    {
        $presidents = Employee::whereHas('officer', function($query) {
                $query->where('president', true);
            })
            ->with(['user'])
            ->get()
            ->map(function($employee) {
                return [
                    'employee_id' => $employee->id,
                    'employee' => [
                        'first_name' => $employee->first_name,
                        'last_name' => $employee->last_name,
                    ]
                ];
            });
        
        return response()->json($presidents);
    }
    
    /**
     * Get Vice Presidents
     */
    public function vp(): JsonResponse
    {
        $vps = Employee::whereHas('officer', function($query) {
                $query->where('vp', true);
            })
            ->with(['user'])
            ->get()
            ->map(function($employee) {
                return [
                    'employee_id' => $employee->id,
                    'employee' => [
                        'first_name' => $employee->first_name,
                        'last_name' => $employee->last_name,
                    ]
                ];
            });
        
        return response()->json($vps);
    }
    
    /**
     * Get Division Heads
     */
    public function divisionHead(): JsonResponse
    {
        $divisionHeads = Employee::whereHas('officer', function($query) {
                $query->where('division_head', true);
            })
            ->with(['user'])
            ->get()
            ->map(function($employee) {
                return [
                    'employee_id' => $employee->id,
                    'employee' => [
                        'first_name' => $employee->first_name,
                        'last_name' => $employee->last_name,
                    ]
                ];
            });
        
        return response()->json($divisionHeads);
    }
    
    /**
     * Get Unit Heads
     */
    public function unitHead(): JsonResponse
    {
        $unitHeads = Employee::whereHas('officer', function($query) {
                $query->where('unit_head', true);
            })
            ->with(['user'])
            ->get()
            ->map(function($employee) {
                return [
                    'employee_id' => $employee->id,
                    'employee' => [
                        'first_name' => $employee->first_name,
                        'last_name' => $employee->last_name,
                    ]
                ];
            });
        
        return response()->json($unitHeads);
    }
}
