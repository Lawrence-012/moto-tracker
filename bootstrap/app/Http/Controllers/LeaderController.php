<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Division;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaderController extends Controller
{
    public function index()
    {
        // Check if user is admin (not motorpool admin)
        if (!Auth::user()->isAdmin()) {
            return redirect()->route('dashboard');
        }
        
        // Get President
        $president = Employee::whereHas('officer', function ($query) {
                    $query->where('president', true);
                })->first();
        
        return view('admin.leaders.index', compact('president'));
    }
    
    public function offices()
    {
        // Check if user is admin (not motorpool admin)
        if (!Auth::user()->isAdmin()) {
            return redirect()->route('dashboard');
        }
        
        // Get all offices with their VPs and related counts
        $offices = Office::with(['positions' => function($query) {
            $query->whereHas('employee.officer', function ($officerQuery) {
                            $officerQuery->where('vp', true);
                        })->with('employee.user');
        }])
        ->with(['divisions' => function($query) {
            $query->withCount('units');
        }])
        ->withCount(['divisions', 'employees'])
        ->get();
        
        // Add units count manually by calculating from divisions
        foreach ($offices as $office) {
            $office->units_count = $office->divisions->sum('units_count');
        }
        
        return view('admin.leaders.offices', compact('offices'));
    }
    
    public function showOffice(Office $office)
    {
        // Check if user is admin (not motorpool admin)
        if (!Auth::user()->isAdmin()) {
            return redirect()->route('dashboard');
        }
        
        // Load office with divisions and their heads
        $office->load([
            'divisions.positions.employee.officer',
            'positions.employee.officer'
        ]);
        
        // Get VP for this office - must have vp=1 AND linked position must have matching office_id
        $vp = $office->employees()->where(function($query) use ($office) {
            $query->whereHas('officer', function($officerQuery) use ($office) {
                $officerQuery->where('vp', true)
                             ->whereHas('position', function($posQuery) use ($office) {
                                 // Verify the linked emp_position has matching office_id
                                 $posQuery->where('office_id', $office->id);
                             });
            });
        })->first();
        
        return view('admin.leaders.office-show', compact('office', 'vp'));
    }
    public function showDivision($divisionId)
{
    $division = Division::with([
        'units.positions.employee.officer',
        'units.subunits',
        'positions.employee.officer',
        'office'
    ])->findOrFail($divisionId);

    // Manually load unit heads for each unit
    foreach ($division->units as $unit) {
        $unit->load(['positions.employee.officer']);
    }

    // Counts
    $division->loadCount(['units']);
    $division->subunits_count = $division->units->sum(fn($unit) => $unit->subunits->count());
    $division->employees_count = $division->positions->pluck('employee_id')->unique()->count();

    // Division Head - must have division_head=1 AND linked position must have matching division_id
    $divisionHead = $division->employees()->where(function($query) use ($division) {
        $query->whereHas('officer', function($officerQuery) use ($division) {
            $officerQuery->where('division_head', true)
                         ->whereHas('position', function($posQuery) use ($division) {
                             // Verify the linked emp_position has matching division_id
                             $posQuery->where('division_id', $division->id_division);
                         });
        });
    })->first();

    // Employees for modal - get all employees except those who are President or Vice President
    $employees = Employee::with(['positions', 'officer'])
        ->whereDoesntHave('officer', function ($query) {
            $query->where('president', true)
                  ->orWhere('vp', true);
        })
        ->orderBy('first_name')
        ->orderBy('last_name')
        ->get();

    return view('admin.leaders.division-show', compact(
        'division',
        'divisionHead',
        'employees'
    ));
}
    public function showUnit(Unit $unit)
    {
        // Check if user is admin (not motorpool admin)
        if (!Auth::user()->isAdmin()) {
            return redirect()->route('dashboard');
        }
        
        // Load unit with its head
        $unit->load([
            'positions.employee.officer',
            'division.office'
        ]);
        
        // Load counts separately
        $unit->loadCount(['subunits']);
        
        // Count employees related to this unit through positions
        $unit->employees_count = $unit->positions->pluck('employee_id')->unique()->count();
        
        // Get Unit Head - must have unit_head=1 AND linked position must have matching unit_id
        $unitHead = $unit->employees()->where(function($query) use ($unit) {
            $query->whereHas('officer', function($officerQuery) use ($unit) {
                $officerQuery->where('unit_head', true)
                             ->whereHas('position', function($posQuery) use ($unit) {
                                 // Verify the linked emp_position has matching unit_id
                                 $posQuery->where('unit_id', $unit->id);
                             });
            });
        })->first();
        
        return view('admin.leaders.unit-show', compact('unit', 'unitHead'));
    }
    
    public function edit($type, $id = null)
    {
        // Check if user is admin (not motorpool admin)
        if (!Auth::user()->isAdmin()) {
            return redirect()->route('dashboard');
        }
        
        $employee = null;
        $organization = null;
        
        if ($id) {
            if ($type === 'vp') {
                $organization = Office::find($id);
            } elseif ($type === 'division_head') {
                $organization = Division::find($id);
            } elseif ($type === 'unit_head') {
                $organization = Unit::find($id);
            }
        }
        
        // Get current employee for this role if exists - check officer table only
        if ($organization) {
            if ($type === 'vp') {
                $employee = $organization->employees()->where(function($query) {
                                $query->whereHas('officer', function($officerQuery) {
                                    $officerQuery->where('vp', true);
                                });
                            })->first();
            } elseif ($type === 'division_head') {
                $employee = $organization->employees()->where(function($query) {
                                $query->whereHas('officer', function($officerQuery) {
                                    $officerQuery->where('division_head', true);
                                });
                            })->first();
            } elseif ($type === 'unit_head') {
                $employee = $organization->employees()->where(function($query) {
                                $query->whereHas('officer', function($officerQuery) {
                                    $officerQuery->where('unit_head', true);
                                });
                            })->first();
            }
        }
        
        // Get all employees for selection (both active and inactive)
        $employees = Employee::all();
        
        return view('admin.leaders.edit', compact('type', 'employee', 'organization', 'employees'));
    }
    
    public function update(Request $request)
    {
        // Check if user is admin (not motorpool admin)
        if (!Auth::user()->isAdmin()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
            }
            return redirect()->route('dashboard');
        }
        
        $request->validate([
            'type' => 'required|string',
            'organization_id' => 'nullable|integer',
            'employee_id' => 'nullable|exists:employees,id',
        ]);
        
        // Additional validation to prevent assigning President as VP
        if ($request->type === 'vp' && $request->employee_id) {
            $employee = Employee::find($request->employee_id);
            if ($employee && $employee->officer && $employee->officer->president) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Cannot assign the University President as a Vice President.'], 422);
                }
                return redirect()->back()->with('error', 'Cannot assign the University President as a Vice President.');
            }
        }
        
        // Additional validation to prevent assigning VP to President's office
        if ($request->type === 'vp' && $request->organization_id) {
            $office = Office::find($request->organization_id);
            if ($office && strpos($office->office_name, 'Office of the University President') !== false) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Cannot assign a Vice President to the Office of the University President.'], 422);
                }
                return redirect()->back()->with('error', 'Cannot assign a Vice President to the Office of the University President.');
            }
        }
        
        // Handle the leadership role assignment
        try {
            switch ($request->type) {
                case 'president':
                    // Reset current president
                    $currentPresident = Employee::whereHas('officer', function ($query) {
                        $query->where('president', true);
                    })->first();
                    
                    if ($currentPresident && $currentPresident->officer) {
                        $currentPresident->officer->update([
                            'president' => false
                        ]);
                    }
                    
                    // Set new president if selected
                    if ($request->employee_id) {
                        $employee = Employee::find($request->employee_id);
                        
                        // Get the employee's primary position from emp_positions
                        $primaryPosition = $employee->positions()->where('is_primary', true)->first();
                        
                        // If no primary position, get any position
                        if (!$primaryPosition) {
                            $primaryPosition = $employee->positions()->first();
                        }
                        
                        if (!$primaryPosition) {
                            throw new \Exception('Employee has no positions in emp_positions table');
                        }
                        
                        // Update or create officer record - LINK to emp_position_id
                        $existingOfficer = $employee->officer;
                        
                        if ($existingOfficer) {
                            $existingOfficer->update([
                                'emp_position_id' => $primaryPosition->id,
                                'unit_head' => 0,
                                'division_head' => 0,
                                'vp' => 0,
                                'president' => 1,
                            ]);
                            $officer = $existingOfficer;
                        } else {
                            $officer = $employee->officer()->create([
                                'emp_position_id' => $primaryPosition->id,
                                'unit_head' => 0,
                                'division_head' => 0,
                                'vp' => 0,
                                'president' => 1,
                            ]);
                        }
                    }
                    break;
                    
                case 'vp':
                    // Remove VP role from current employee for this office
                    if ($request->organization_id) {
                        $office = Office::find($request->organization_id);
                        if ($office) {
                            // Remove from officer table
                            $currentVp = $office->employees()->whereHas('officer', function ($officerQuery) {
                                $officerQuery->where('vp', true);
                            })->first();
                            if ($currentVp && $currentVp->officer) {
                                $currentVp->officer->update([
                                    'vp' => false
                                ]);
                            }
                        }
                    }
                    // Set new VP if selected
                    if ($request->employee_id) {
                        $employee = Employee::find($request->employee_id);
                        
                        // Get the employee's position for this specific office from emp_positions
                        $employeePosition = $employee->positions()
                            ->where('office_id', $request->organization_id)
                            ->first();
                        
                        // If no position exists for this office, use primary position or any position
                        if (!$employeePosition) {
                            $employeePosition = $employee->positions()->where('is_primary', true)->first();
                        }
                        if (!$employeePosition) {
                            $employeePosition = $employee->positions()->first();
                        }
                        
                        if (!$employeePosition) {
                            throw new \Exception('Employee has no positions in emp_positions table');
                        }
                        
                        // Update or create officer record - LINK to emp_position_id
                        $existingOfficer = $employee->officer;
                        
                        if ($existingOfficer) {
                            $existingOfficer->update([
                                'emp_position_id' => $employeePosition->id,
                                'unit_head' => 0,
                                'division_head' => 0,
                                'vp' => 1,
                                'president' => 0,
                            ]);
                            $officer = $existingOfficer;
                        } else {
                            $officer = $employee->officer()->create([
                                'emp_position_id' => $employeePosition->id,
                                'unit_head' => 0,
                                'division_head' => 0,
                                'vp' => 1,
                                'president' => 0,
                            ]);
                        }
                    }
                    break;
                    
                case 'division_head':
                    // Remove Division Head role from current employee for this division
                    if ($request->organization_id) {
                        $division = Division::find($request->organization_id);
                        if ($division) {
                            // Remove from officer table
                            $currentDivisionHead = $division->employees()->whereHas('officer', function ($officerQuery) {
                                $officerQuery->where('division_head', true);
                            })->first();
                            if ($currentDivisionHead && $currentDivisionHead->officer) {
                                $currentDivisionHead->officer->update([
                                    'division_head' => false
                                ]);
                            }
                        }
                    }
                    // Set new Division Head if selected
                    if ($request->employee_id) {
                        $employee = Employee::find($request->employee_id);
                        
                        // Get the employee's position for this specific division from emp_positions
                        $employeePosition = $employee->positions()
                            ->where('division_id', $request->organization_id)
                            ->first();
                        
                        // If no position exists for this division, use primary position or any position
                        if (!$employeePosition) {
                            $employeePosition = $employee->positions()->where('is_primary', true)->first();
                        }
                        if (!$employeePosition) {
                            $employeePosition = $employee->positions()->first();
                        }
                        
                        if (!$employeePosition) {
                            throw new \Exception('Employee has no positions in emp_positions table');
                        }
                        
                        // Update or create officer record - LINK to emp_position_id
                        $existingOfficer = $employee->officer;
                        
                        if ($existingOfficer) {
                            $existingOfficer->update([
                                'emp_position_id' => $employeePosition->id,
                                'unit_head' => 0,
                                'division_head' => 1,
                                'vp' => 0,
                                'president' => 0,
                            ]);
                            $officer = $existingOfficer;
                        } else {
                            $officer = $employee->officer()->create([
                                'emp_position_id' => $employeePosition->id,
                                'unit_head' => 0,
                                'division_head' => 1,
                                'vp' => 0,
                                'president' => 0,
                            ]);
                        }
                    }
                    break;
                    
                case 'unit_head':
                    // Remove Unit Head role from current employee for this unit
                    if ($request->organization_id) {
                        $unit = Unit::find($request->organization_id);
                        if ($unit) {
                            // Remove from officer table - ONLY if linked position matches THIS unit
                            $currentUnitHead = $unit->employees()->where(function($query) use ($unit) {
                                $query->whereHas('officer', function($officerQuery) use ($unit) {
                                    $officerQuery->where('unit_head', true)
                                                 ->whereHas('position', function($posQuery) use ($unit) {
                                                     $posQuery->where('unit_id', $unit->id);
                                                 });
                                });
                            })->first();
                            
                            if ($currentUnitHead && $currentUnitHead->officer) {
                                \Log::info('Removing Unit Head role', [
                                    'employee_id' => $currentUnitHead->id,
                                    'employee_name' => $currentUnitHead->full_name,
                                    'unit_id' => $unit->id,
                                    'officer_emp_position_id' => $currentUnitHead->officer->emp_position_id,
                                ]);
                                $currentUnitHead->officer->update([
                                    'unit_head' => 0
                                ]);
                            }
                        }
                    }
                    // Set new Unit Head if selected
                    if ($request->employee_id) {
                        $employee = Employee::find($request->employee_id);
                        
                        // Get the employee's position for this specific unit from emp_positions
                        $employeePosition = $employee->positions()
                            ->where('unit_id', $request->organization_id)
                            ->first();
                        
                        // If no position exists for this unit, use primary position or any position
                        if (!$employeePosition) {
                            $employeePosition = $employee->positions()->where('is_primary', true)->first();
                        }
                        if (!$employeePosition) {
                            $employeePosition = $employee->positions()->first();
                        }
                        
                        if (!$employeePosition) {
                            throw new \Exception('Employee has no positions in emp_positions table');
                        }
                        
                        // Update or create officer record - LINK to emp_position_id
                        $existingOfficer = $employee->officer;
                        
                        if ($existingOfficer) {
                            $existingOfficer->update([
                                'emp_position_id' => $employeePosition->id,
                                'unit_head' => 1,
                                'division_head' => 0,
                                'vp' => 0,
                                'president' => 0,
                            ]);
                            $officer = $existingOfficer;
                        } else {
                            $officer = $employee->officer()->create([
                                'emp_position_id' => $employeePosition->id,
                                'unit_head' => 1,
                                'division_head' => 0,
                                'vp' => 0,
                                'president' => 0,
                            ]);
                        }
                    }
                    break;
            }
            
            return response()->json(['success' => true, 'message' => 'Leadership role updated successfully']);
        } catch (\Exception $e) {
            \Log::error('Leadership role update error: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}