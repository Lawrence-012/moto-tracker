@forelse($travelOrders as $index => $travelOrder)
    <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
        <td class="px-2 py-2 text-xs text-[#004d00] font-medium">{{ $travelOrders->firstItem() + $loop->index }}</td>
        <td class="px-2 py-2 text-xs text-[#004d00] font-medium max-w-[150px] break-words">{{ $travelOrder->destination }}</td>
        <td class="px-2 py-2 text-xs text-[#006400] whitespace-nowrap">
            @if($travelOrder->date_from->format('Y-m-d') == $travelOrder->date_to->format('Y-m-d'))
                {{ $travelOrder->date_from->format('M j, Y') }}
            @elseif($travelOrder->date_from->format('Y-m') == $travelOrder->date_to->format('Y-m'))
                {{ $travelOrder->date_from->format('M j') }} - {{ $travelOrder->date_to->format('j, Y') }}
            @elseif($travelOrder->date_from->format('Y') == $travelOrder->date_to->format('Y'))
                {{ $travelOrder->date_from->format('M j') }} - {{ $travelOrder->date_to->format('M j, Y') }}
            @else
                {{ $travelOrder->date_from->format('M j, Y') }} - {{ $travelOrder->date_to->format('M j, Y') }}
            @endif
        </td>
        <td class="px-2 py-2 text-xs text-[#006400] whitespace-nowrap">
            @if($travelOrder->departure_time)
                {{ date('g:i A', strtotime($travelOrder->departure_time)) }}
            @else
                <span class="text-gray-400">N/A</span>
            @endif
        </td>
        <td class="px-2 py-2 text-xs text-gray-600 max-w-[150px] break-words">
            <div class="text-[#004d00] font-medium">{{ $travelOrder->purpose }}</div>
        </td>
        @php
            $statusMessage = '';
            $statusClass = '';
            
            // Check if travel order is fully approved based on status and approval timestamps
            $isFullyApproved = false;
            
            if($travelOrder->status === 'approved') {
                $statusMessage = 'Approved';
                $statusClass = 'bg-green-100 text-green-800';
                $isFullyApproved = true;
            } elseif($travelOrder->status === 'pending') {
                // Determine approval status based on approver timestamps
                $approver1Approved = !is_null($travelOrder->approver1_approved_at);
                $approver2Approved = !is_null($travelOrder->approver2_approved_at);
                
                // Get employee's role flags from officer table
                $isUnitHead = $travelOrder->employee->officer && $travelOrder->employee->officer->unit_head;
                $isDivisionHead = $travelOrder->employee->officer && $travelOrder->employee->officer->division_head;
                $isVP = $travelOrder->employee->officer && $travelOrder->employee->officer->vp;
                $isPresident = $travelOrder->employee->officer && $travelOrder->employee->officer->president;
                
                if(!$isDivisionHead && !$isVP && !$isPresident && !$isUnitHead) {
                    // REGULAR EMPLOYEE
                    // Approval chain: Approver 2 (lower rank) -> Approver 1 (higher rank)
                    if(!$approver1Approved && !$approver2Approved) {
                        // No one has approved yet - show who Approver 2 is
                        if($travelOrder->approver2_employee_id) {
                            $approver2 = App\Models\Employee::find($travelOrder->approver2_employee_id);
                            if($approver2 && $approver2->officer && $approver2->officer->unit_head) {
                                $statusMessage = 'Waiting for Unit Head Approval';
                            } elseif($approver2 && $approver2->officer && $approver2->officer->division_head) {
                                $statusMessage = 'Waiting for Division Head Approval';
                            } elseif($approver2 && $approver2->officer && $approver2->officer->vp) {
                                $statusMessage = 'Waiting for VP Approval';
                            } elseif($approver2 && $approver2->officer && $approver2->officer->president) {
                                $statusMessage = 'Waiting for President Approval';
                            } elseif($approver2 && $approver2->position) {
                                // Fallback: show position name
                                $statusMessage = 'Waiting for ' . ($approver2->position->position_name ?? 'Approval');
                            } else {
                                $statusMessage = 'Waiting for Approval';
                            }
                        } else {
                            $statusMessage = 'Waiting for Approval';
                        }
                        $statusClass = 'bg-blue-100 text-blue-800';
                    } elseif($approver2Approved && !$approver1Approved) {
                        // Approved by lower rank, waiting for higher rank
                        if($travelOrder->approver1_employee_id) {
                            $approver1 = App\Models\Employee::find($travelOrder->approver1_employee_id);
                            if($approver1 && $approver1->officer && $approver1->officer->division_head) {
                                $statusMessage = 'Waiting for Division Head Approval';
                            } elseif($approver1 && $approver1->officer && $approver1->officer->vp) {
                                $statusMessage = 'Waiting for VP Approval';
                            } elseif($approver1 && $approver1->officer && $approver1->officer->president) {
                                $statusMessage = 'Waiting for President Approval';
                            } elseif($approver1 && $approver1->position) {
                                // Fallback: show position name
                                $statusMessage = 'Waiting for ' . ($approver1->position->position_name ?? 'Final Approval');
                            } else {
                                $statusMessage = 'Waiting for Final Approval';
                            }
                        } else {
                            $statusMessage = 'Waiting for Final Approval';
                        }
                        $statusClass = 'bg-purple-100 text-purple-800';
                    } elseif($approver1Approved && $approver2Approved) {
                        // Both approved - should be in "approved" status but just in case
                        $statusMessage = 'Approved';
                        $statusClass = 'bg-green-100 text-green-800';
                        $isFullyApproved = true;
                    }
                } elseif($isUnitHead && !$isDivisionHead && !$isVP && !$isPresident) {
                    // UNIT HEAD ROLE ONLY
                    // Check actual approvers' roles instead of assuming
                    if(!$approver1Approved && !$approver2Approved) {
                        // No one has approved yet - check who Approver 2 is (approves first)
                        if($travelOrder->approver2_employee_id) {
                            $approver2 = App\Models\Employee::find($travelOrder->approver2_employee_id);
                            if($approver2 && $approver2->officer && $approver2->officer->division_head) {
                                $statusMessage = 'Waiting for Division Head Approval';
                            } elseif($approver2 && $approver2->officer && $approver2->officer->vp) {
                                $statusMessage = 'Waiting for VP Approval';
                            } else {
                                $statusMessage = 'Waiting for Approval';
                            }
                        } else {
                            $statusMessage = 'Waiting for Approval';
                        }
                        $statusClass = 'bg-purple-100 text-purple-800';
                    } elseif($approver2Approved && !$approver1Approved) {
                        // Approved by first approver, waiting for second approver
                        if($travelOrder->approver1_employee_id) {
                            $approver1 = App\Models\Employee::find($travelOrder->approver1_employee_id);
                            if($approver1 && $approver1->officer && $approver1->officer->vp) {
                                $statusMessage = 'Waiting for VP Approval';
                            } elseif($approver1 && $approver1->officer && $approver1->officer->president) {
                                $statusMessage = 'Waiting for President Approval';
                            } else {
                                $statusMessage = 'Waiting for Final Approval';
                            }
                        } else {
                            $statusMessage = 'Waiting for Final Approval';
                        }
                        $statusClass = 'bg-indigo-100 text-indigo-800';
                    } elseif($approver1Approved && $approver2Approved) {
                        $statusMessage = 'Approved';
                        $statusClass = 'bg-green-100 text-green-800';
                        $isFullyApproved = true;
                    }
                } elseif($isDivisionHead) {
                    // DIVISION HEAD EMPLOYEE
                    // Check actual approvers' roles instead of assuming
                    if(!$approver1Approved && !$approver2Approved) {
                        // No one has approved yet - check who Approver 2 is (approves first)
                        if($travelOrder->approver2_employee_id) {
                            $approver2 = App\Models\Employee::find($travelOrder->approver2_employee_id);
                            if($approver2 && $approver2->officer && $approver2->officer->vp) {
                                $statusMessage = 'Waiting for VP Approval';
                            } elseif($approver2 && $approver2->officer && $approver2->officer->president) {
                                $statusMessage = 'Waiting for President Approval';
                            } else {
                                $statusMessage = 'Waiting for Approval';
                            }
                        } else {
                            $statusMessage = 'Waiting for Approval';
                        }
                        $statusClass = 'bg-indigo-100 text-indigo-800';
                    } elseif($approver2Approved && !$approver1Approved) {
                        // Approved by first approver, waiting for second approver
                        if($travelOrder->approver1_employee_id) {
                            $approver1 = App\Models\Employee::find($travelOrder->approver1_employee_id);
                            if($approver1 && $approver1->officer && $approver1->officer->president) {
                                $statusMessage = 'Waiting for President Approval';
                            } else {
                                $statusMessage = 'Waiting for Final Approval';
                            }
                        } else {
                            $statusMessage = 'Waiting for Final Approval';
                        }
                        $statusClass = 'bg-pink-100 text-pink-800';
                    } elseif($approver1Approved && $approver2Approved) {
                        $statusMessage = 'Approved';
                        $statusClass = 'bg-green-100 text-green-800';
                        $isFullyApproved = true;
                    }
                } elseif($isVP) {
                    // VP EMPLOYEE
                    // Check actual approvers' roles instead of assuming
                    if(!$approver1Approved && !$approver2Approved) {
                        // No one has approved yet - check who Approver 2 is (approves first)
                        if($travelOrder->approver2_employee_id) {
                            $approver2 = App\Models\Employee::find($travelOrder->approver2_employee_id);
                            if($approver2 && $approver2->officer && $approver2->officer->president) {
                                $statusMessage = 'Waiting for President Approval';
                            } else {
                                $statusMessage = 'Waiting for Approval';
                            }
                        } else {
                            $statusMessage = 'Waiting for Approval';
                        }
                        $statusClass = 'bg-pink-100 text-pink-800';
                    } elseif($approver2Approved && !$approver1Approved) {
                        // Approved by first approver, waiting for second approver
                        if($travelOrder->approver1_employee_id) {
                            $approver1 = App\Models\Employee::find($travelOrder->approver1_employee_id);
                            if($approver1 && $approver1->officer && $approver1->officer->president) {
                                $statusMessage = 'Waiting for President Approval';
                            } else {
                                $statusMessage = 'Waiting for Final Approval';
                            }
                        } else {
                            $statusMessage = 'Waiting for Final Approval';
                        }
                        $statusClass = 'bg-pink-100 text-pink-800';
                    } elseif($approver1Approved && $approver2Approved) {
                        $statusMessage = 'Approved';
                        $statusClass = 'bg-green-100 text-green-800';
                        $isFullyApproved = true;
                    }
                } elseif($isPresident) {
                    // PRESIDENT EMPLOYEE - Auto-approved
                    $statusMessage = 'Approved';
                    $statusClass = 'bg-green-100 text-green-800';
                    $isFullyApproved = true;
                } else {
                    $statusMessage = 'Pending';
                    $statusClass = 'bg-yellow-100 text-yellow-800';
                }
            } elseif($travelOrder->status === 'cancelled') {
                // In cancelled tab, just show "Cancelled" regardless of who cancelled
                if(isset($tab) && $tab == 'cancelled') {
                    $statusMessage = 'Cancelled';
                    $statusClass = 'bg-red-100 text-red-800';
                } else {
                    // Show who cancelled/rejected the travel order in other tabs
                    $approver1Approved = !is_null($travelOrder->approver1_approved_at);
                    $approver2Approved = !is_null($travelOrder->approver2_approved_at);
                    
                    // Get employee's role flags
                    $isUnitHead = $travelOrder->employee->officer && $travelOrder->employee->officer->unit_head;
                    $isDivisionHead = $travelOrder->employee->officer && $travelOrder->employee->officer->division_head;
                    $isVP = $travelOrder->employee->officer && $travelOrder->employee->officer->vp;
                    $isPresident = $travelOrder->employee->officer && $travelOrder->employee->officer->president;
                    
                    if(!$approver2Approved && !$approver1Approved) {
                        // Cancelled by first approver (Approver 2 - lower rank)
                        if($travelOrder->approver2_employee_id) {
                            $approver2 = App\Models\Employee::find($travelOrder->approver2_employee_id);
                            if($approver2 && $approver2->officer && $approver2->officer->unit_head) {
                                $statusMessage = 'Cancelled by Unit Head';
                            } elseif($approver2 && $approver2->officer && $approver2->officer->division_head) {
                                $statusMessage = 'Cancelled by Division Head';
                            } elseif($approver2 && $approver2->officer && $approver2->officer->vp) {
                                $statusMessage = 'Cancelled by VP';
                            } elseif($approver2 && $approver2->officer && $approver2->officer->president) {
                                $statusMessage = 'Cancelled by President';
                            } else {
                                $statusMessage = 'Cancelled';
                            }
                        } else {
                            $statusMessage = 'Cancelled';
                        }
                        $statusClass = 'bg-red-100 text-red-800';
                    } elseif($approver2Approved && !$approver1Approved) {
                        // Cancelled by second approver (Approver 1 - higher rank)
                        if($travelOrder->approver1_employee_id) {
                            $approver1 = App\Models\Employee::find($travelOrder->approver1_employee_id);
                            if($approver1 && $approver1->officer && $approver1->officer->division_head) {
                                $statusMessage = 'Cancelled by Division Head';
                            } elseif($approver1 && $approver1->officer && $approver1->officer->vp) {
                                $statusMessage = 'Cancelled by VP';
                            } elseif($approver1 && $approver1->officer && $approver1->officer->president) {
                                $statusMessage = 'Cancelled by President';
                            } else {
                                $statusMessage = 'Cancelled';
                            }
                        } else {
                            $statusMessage = 'Cancelled';
                        }
                        $statusClass = 'bg-red-100 text-red-800';
                    } else {
                        $statusMessage = 'Cancelled';
                        $statusClass = 'bg-red-100 text-red-800';
                    }
                }
            } elseif($travelOrder->is_completed || $travelOrder->status === 'completed') {
                $statusMessage = 'Completed';
                $statusClass = 'bg-green-100 text-green-800';
            } else {
                // Default fallback - use appropriate color based on status
                $statusMessage = ucfirst($travelOrder->status);
                if ($travelOrder->status === 'completed') {
                    $statusClass = 'bg-green-100 text-green-800';
                } elseif ($travelOrder->status === 'approved') {
                    $statusClass = 'bg-green-100 text-green-800';
                } elseif ($travelOrder->status === 'pending') {
                    $statusClass = 'bg-yellow-100 text-yellow-800';
                } elseif ($travelOrder->status === 'cancelled') {
                    $statusClass = 'bg-red-100 text-red-800';
                } else {
                    $statusClass = 'bg-blue-100 text-blue-800';
                }
            }
        @endphp
        <td class="px-4 py-3 text-xs text-[#006400] max-w-xs break-words">
            <span class="px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded {{ $statusClass }}">
                {{ $statusMessage }}
            </span>
        </td>
        <td class="px-4 py-3 text-center text-xs">
            <div class="action-buttons flex justify-center" style="gap: 4px;">
                <button type="button" 
                        onclick="viewTravelOrderDetails({{ $travelOrder->id }})" 
                        class="btn view-btn border inline-flex items-center justify-center"
                        title="View Travel Order">
                    <i class="fas fa-eye"></i> View
                </button>
                
                {{-- Draft Tab - Show View, Edit, Submit for Approval, and Delete buttons --}}
                @if(isset($tab) && $tab == 'draft')
                    <button type="button" 
                            onclick="editDraftTravelOrder({{ $travelOrder->id }})"
                            class="btn edit-btn border inline-flex items-center justify-center"
                            title="Edit Travel Request">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button type="button" 
                            class="btn btn-primary border inline-flex items-center justify-center"
                            onclick="submitDraftForApproval({{ $travelOrder->id }})"
                            title="Submit for Approval">
                        <i class="fas fa-paper-plane"></i> Submit for Approval
                    </button>
                    <button type="button" 
                            class="btn archive-btn border delete-draft-btn inline-flex items-center justify-center"
                            data-id="{{ $travelOrder->id }}"
                            data-title="{{ $travelOrder->destination }}"
                            title="Delete Travel Request">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                
                {{-- Pending Tab - Show Edit and Cancel buttons (if can edit) --}}
                @elseif((isset($tab) && $tab == 'pending') || !isset($tab))
                    @php
                        // Determine if travel order can be edited/cancelled based on employee role
                        $canEditOrCancel = false;
                        
                        if($travelOrder->employee->is_head && !$travelOrder->employee->is_divisionhead && !$travelOrder->employee->is_vp && !$travelOrder->employee->is_president) {
                            // Unit Head employee: can edit if not approved by Division Head AND VP
                            $canEditOrCancel = (!$travelOrder->divisionhead_approved && !$travelOrder->vp_approved);
                        } elseif(!$travelOrder->employee->is_divisionhead && !$travelOrder->employee->is_vp && !$travelOrder->employee->is_president && !$travelOrder->employee->is_head) {
                            // Regular employee: can edit if not approved by Unit Head OR not approved by Division Head
                            $canEditOrCancel = (!$travelOrder->head_approved || !$travelOrder->divisionhead_approved);
                        } elseif($travelOrder->employee->is_divisionhead && !$travelOrder->employee->is_vp && !$travelOrder->employee->is_president) {
                            // Division Head employee: can edit if not approved by VP
                            $canEditOrCancel = !$travelOrder->vp_approved;
                        } elseif($travelOrder->employee->is_vp && !$travelOrder->employee->is_president) {
                            // VP employee: can edit if not approved by President
                            $canEditOrCancel = !$travelOrder->president_approved;
                        } elseif($travelOrder->employee->is_president) {
                            // President employee: can always edit/cancel
                            $canEditOrCancel = true;
                        }
                    @endphp
                    @if($canEditOrCancel)
                        <button type="button" 
                                onclick="editDraftTravelOrder({{ $travelOrder->id }})"
                                class="btn edit-btn border inline-flex items-center justify-center"
                                title="Edit Travel Order">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button type="button" 
                                class="btn archive-btn border cancel-btn inline-flex items-center justify-center"
                                onclick="confirmCancel({{ $travelOrder->id }})"
                                title="Cancel Travel Order">
                            <i class="fas fa-times-circle"></i> Cancel
                        </button>
                    @endif
                
                {{-- Approved Tab - Show Cancel button only (if can edit) --}}
                @elseif(isset($tab) && $tab == 'approved')
                    @php
                        // Determine if travel order can be edited/cancelled based on employee role
                        $canEditOrCancelApproved = false;
                        
                        if($travelOrder->employee->is_head && !$travelOrder->employee->is_divisionhead && !$travelOrder->employee->is_vp && !$travelOrder->employee->is_president) {
                            // Unit Head employee: can edit if not approved by Division Head AND VP
                            $canEditOrCancelApproved = (!$travelOrder->divisionhead_approved && !$travelOrder->vp_approved);
                        } elseif(!$travelOrder->employee->is_divisionhead && !$travelOrder->employee->is_vp && !$travelOrder->employee->is_president && !$travelOrder->employee->is_head) {
                            // Regular employee: can edit if not approved by Unit Head OR not approved by Division Head
                            $canEditOrCancelApproved = (!$travelOrder->head_approved || !$travelOrder->divisionhead_approved);
                        } elseif($travelOrder->employee->is_divisionhead && !$travelOrder->employee->is_vp && !$travelOrder->employee->is_president) {
                            // Division Head employee: can edit if not approved by VP
                            $canEditOrCancelApproved = !$travelOrder->vp_approved;
                        } elseif($travelOrder->employee->is_vp && !$travelOrder->employee->is_president) {
                            // VP employee: can edit if not approved by President
                            $canEditOrCancelApproved = !$travelOrder->president_approved;
                        } elseif($travelOrder->employee->is_president) {
                            // President employee: can always edit/cancel
                            $canEditOrCancelApproved = true;
                        }
                    @endphp
                    @if($canEditOrCancelApproved)
                        <button type="button" 
                                class="btn archive-btn border cancel-btn inline-flex items-center justify-center"
                                onclick="confirmCancel({{ $travelOrder->id }})"
                                title="Cancel Travel Order">
                            <i class="fas fa-times-circle"></i> Cancel
                        </button>
                    @endif
                
                {{-- Completed Tab - View Only --}}
                @elseif(isset($tab) && $tab == 'completed')
                    {{-- No additional buttons, View only --}}
                
                {{-- Cancelled Tab - View only --}}
                @elseif(isset($tab) && $tab == 'cancelled')
                    {{-- No action buttons, View only --}}
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="px-4 py-6 text-center text-xs text-gray-500">
            <div class="flex flex-col items-center justify-center">
                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <div class="mt-1">
                    @if(isset($tab))
                        @switch($tab)
                            @case('pending')
                                <h3 class="text-sm font-medium text-gray-900">No pending travel requests</h3>
                                <p class="mt-0.5 text-xs">You don't have any pending travel requests at the moment.</p>
                                @break
                            @case('approved')
                                <h3 class="text-sm font-medium text-gray-900">No approved travel requests</h3>
                                <p class="mt-0.5 text-xs">You don't have any approved travel requests yet.</p>
                                @break
                            @case('completed')
                                <h3 class="text-sm font-medium text-gray-900">No completed travel requests</h3>
                                <p class="mt-0.5 text-xs">You don't have any completed travel requests yet.</p>
                                @break
                            @case('cancelled')
                                <h3 class="text-sm font-medium text-gray-900">No cancelled travel requests</h3>
                                <p class="mt-0.5 text-xs">You don't have any cancelled travel requests.</p>
                                @break
                            @default
                                <h3 class="text-sm font-medium text-gray-900">No travel requests found</h3>
                                <p class="mt-0.5 text-xs">There are no travel requests matching your criteria.</p>
                        @endswitch
                    @else
                        <h3 class="text-sm font-medium text-gray-900">No pending travel requests</h3>
                        <p class="mt-0.5 text-xs">You don't have any pending travel requests at the moment.</p>
                    @endif
                </div>
            </div>
        </td>
    </tr>
@endforelse

<script>
// Delete confirmation is now handled by table-search.js
// This prevents duplicate event listeners

// Handle Delete Cancelled button click
document.addEventListener('DOMContentLoaded', function() {
    // Handle Delete Cancelled button click
    document.querySelectorAll('.delete-cancelled-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const travelOrderId = this.dataset.id;
            const travelOrderTitle = this.dataset.title;
            
            Swal.fire({
                title: 'Delete Cancelled Travel Order?',
                html: `Are you sure you want to permanently delete "<strong>${travelOrderTitle}</strong>"?<br>This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Delete Permanently',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading SweetAlert for 2 seconds
                    Swal.fire({
                        title: 'Deleting...',
                        html: 'Please wait while we delete this travel order',
                        timer: 2000,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    }).then(() => {
                        // After 2 seconds, submit the delete form
                        const form = document.getElementById(`delete-form-${travelOrderId}`);
                        if (form) {
                            form.submit();
                        } else {
                            // Use the dedicated delete-cancelled route
                            fetch(`/travel-orders/${travelOrderId}/delete-cancelled`, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({})
                            })
                            .then(response => {
                                // Handle both JSON responses and redirects
                                const contentType = response.headers.get('content-type');
                                if (contentType && contentType.includes('application/json')) {
                                    return response.json().then(data => {
                                        if (response.ok && data.success) {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Deleted!',
                                                text: data.message || 'Cancelled travel order deleted successfully.',
                                                timer: 2000,
                                                showConfirmButton: false
                                            }).then(() => {
                                                location.reload();
                                            });
                                        } else {
                                            throw new Error(data.message || 'Failed to delete travel order.');
                                        }
                                    });
                                } else {
                                    // For redirects (HTML response), assume success and reload
                                    if (response.ok) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Deleted!',
                                            text: 'Cancelled travel order deleted successfully.',
                                            timer: 2000,
                                            showConfirmButton: false
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        throw new Error('Failed to delete travel order.');
                                    }
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'An error occurred while deleting the travel order.'
                                });
                            });
                        }
                    });
                }
            });
        });
    });
});

// Modal for travel order details
function viewTravelOrderDetails(id) {
    // Fetch travel order details via AJAX
    fetch(`/api/travel-orders/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showTravelOrderModal(data.data);
            } else {
                alert('Error loading travel order details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading travel order details');
        });
}

function showTravelOrderModal(travelOrder) {
    // Parse and format dates
    const dateFrom = new Date(travelOrder.date_from);
    const dateTo = new Date(travelOrder.date_to);
    
    let departureDateDisplay;
    const fromMonth = dateFrom.toLocaleString('en-US', { month: 'long' });
    const fromDay = dateFrom.getDate();
    const fromYear = dateFrom.getFullYear();
    
    const toMonth = dateTo.toLocaleString('en-US', { month: 'long' });
    const toDay = dateTo.getDate();
    const toYear = dateTo.getFullYear();
    
    if (dateFrom.getTime() === dateTo.getTime()) {
        // Same date: March 12, 2026
        departureDateDisplay = `${fromMonth} ${fromDay}, ${fromYear}`;
    } else if (fromMonth === toMonth && fromYear === toYear) {
        // Same month: March 12-13, 2026
        departureDateDisplay = `${fromMonth} ${fromDay}-${toDay}, ${fromYear}`;
    } else if (fromYear === toYear) {
        // Same year: March 12 - April 1, 2026
        departureDateDisplay = `${fromMonth} ${fromDay} - ${toMonth} ${toDay}, ${fromYear}`;
    } else {
        // Different years: December 31, 2025 - January 1, 2026
        departureDateDisplay = `${fromMonth} ${fromDay}, ${fromYear} - ${toMonth} ${toDay}, ${toYear}`;
    }
    
    // Format departure time
    let departureTimeDisplay = 'N/A';
    if (travelOrder.departure_time) {
        const timeMatch = travelOrder.departure_time.match(/(\d{1,2}):(\d{2})(?::\d{2})?/);
        if (timeMatch) {
            let hours = parseInt(timeMatch[1]);
            const minutes = timeMatch[2];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            departureTimeDisplay = `${hours}:${minutes} ${ampm}`;
        } else {
            departureTimeDisplay = travelOrder.departure_time;
        }
    }
    
    // Create modal HTML with TABLE LAYOUT - EXACT COPY FROM MOTORPOOL ADMIN INDEX.PHP
    const modalHtml = `
        <div id="travelOrderModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4 transition-all duration-200">
            <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border-t-4 border-[#1e6031] transform transition-all duration-200 scale-95 opacity-0" id="travelOrderModalContent">
                <!-- Modal Header - DSU Themed -->
                <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between" style="background: linear-gradient(135deg, #1e6031 0%, #2d7a3e 50%, #1e6031 100%);">
                    <div class="flex items-center gap-3">
                        <!-- Icon Circle -->
                        <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                            <!-- Document Icon -->
                            <svg class="h-6 w-6 text-[#1e6031]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-white">Travel Order Details</h3>
                    </div>
                </div>
                
                <!-- Modal Content -->
                <div class="px-6 py-4">
                    <div class="text-sm text-gray-800">
                        <table class="w-full">
                            <tbody>
                                <!-- Employee Information Section -->
                                <tr>
                                    <td colspan="2" class="font-bold text-gray-900 py-1.5 border-b">Employee Information</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Employee Name:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${travelOrder.employee?.full_name || ''}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Email:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${travelOrder.employee?.user?.email || 'N/A'}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Position:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${travelOrder.position?.position_name || 'N/A'}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Contact Number:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${travelOrder.employee?.contact_num || '-'}
                                    </td>
                                </tr>
                                
                                <!-- Travel Details Section -->
                                <tr>
                                    <td colspan="2" class="font-bold text-gray-900 py-1.5 border-b mt-2">Travel Details</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Departure Date:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${departureDateDisplay}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Departure Time:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${departureTimeDisplay}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Destination:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${travelOrder.destination}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Purpose:</td>
                                    <td class="text-gray-900 py-0.5 align-top">${travelOrder.purpose}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Status:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        ${(() => {
                                            let statusText = travelOrder.status.charAt(0).toUpperCase() + travelOrder.status.slice(1);
                                            let statusClass = '';
                                            
                                            // Determine status color based on status value
                                            if (travelOrder.status === 'completed') {
                                                statusClass = 'bg-green-100 text-green-800';
                                            } else if (travelOrder.status === 'approved') {
                                                statusClass = 'bg-green-100 text-green-800';
                                            } else if (travelOrder.status === 'pending') {
                                                statusClass = 'bg-yellow-100 text-yellow-800';
                                            } else if (travelOrder.status === 'cancelled') {
                                                statusClass = 'bg-red-100 text-red-800';
                                            } else {
                                                statusClass = 'bg-blue-100 text-blue-800';
                                            }
                                            
                                            // Show detailed approval status for approved travel orders
                                            if (travelOrder.status === 'approved') {
                                                // Check if itinerary exists and its approval status
                                                const itinerary = travelOrder.itinerary;
                                                
                                                if (!itinerary) {
                                                    // No itinerary created yet
                                                    statusText = 'Approved - Assigning Driver';
                                                } else {
                                                    // Itinerary exists - driver has been assigned
 
                                                    statusText = 'Approved - Driver Assigned';
                                                }
                                            }
                                            
                                            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">${statusText}</span>`;
                                        })()}
                                    </td>
                                </tr>
                                
                                ${travelOrder.status === 'cancelled' || travelOrder.rejection_reason ? `
                                ${travelOrder.rejected_by ? `
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Rejected by:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        <span class="text-sm text-gray-900 font-medium">${travelOrder.rejected_by.full_name || 'Unknown'}</span>
                                    </td>
                                </tr>
                                ` : ''}
                                <tr>
                                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Cancellation Reason:</td>
                                    <td class="text-gray-900 py-0.5 align-top">
                                        <span class="text-sm text-gray-700">${travelOrder.rejection_reason || 'No reason provided'}</span>
                                    </td>
                                </tr>
                                ` : ''}
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white gap-2">
                    <button onclick="window.viewTravelOrderExcel(${travelOrder.id}); closeModal();" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <i class="fas fa-file-excel mr-1"></i> View Excel
                    </button>
                    <button onclick="closeModal()" class="px-4 py-1.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page and show it
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Trigger animation - EXACT COPY FROM MOTORPOOL ADMIN
    setTimeout(() => {
        const modal = document.getElementById('travelOrderModal');
        const modalContent = document.getElementById('travelOrderModalContent');
        
        // Show modal backdrop immediately
        modal.classList.remove('hidden');
        
        // Trigger reflow
        void modal.offsetWidth;
        
        // Animate in
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        // Close modal when clicking outside (on backdrop)
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function handleEscape(e) {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', handleEscape);
            }
        });
    }, 10);
}

// Helper function to close modal
function closeModal() {
    const modal = document.getElementById('travelOrderModal');
    const modalContent = document.getElementById('travelOrderModalContent');
    
    if (modal && modalContent) {
        // Animate out
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        
        // Remove modal after animation completes
        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = '';
        }, 200);
    }
}

function viewTravelOrderExcel(id) {
    // Show confirmation dialog before proceeding
    Swal.fire({
        title: 'Download Excel File?',
        text: 'You are about to download the Excel file. Do you want to proceed?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, proceed!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // User confirmed, proceed with fetching travel order details
            // Fetch travel order details to determine the correct Excel URL based on the position selected during creation
            fetch(`/api/travel-orders/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const travelOrder = data.data;
                        const position = travelOrder.position;
                        
                        let excelUrl;
                        if (position) {
                            // Determine Excel URL based on the POSITION SELECTED during travel order creation
                            if (position.is_president) {
                                // President's travel order - use president Excel route
                                excelUrl = `/president/travel-orders/${id}/excel`;
                            } else if (position.is_vp && !position.is_president) {
                                // VP's travel order - use VP Excel route
                                excelUrl = `/vp/travel-orders/${id}/excel`;
                            } else if (position.is_division_head && !position.is_vp && !position.is_president) {
                                // Division head's travel order - use division head Excel route
                                excelUrl = `/divisionhead/travel-orders/${id}/excel`;
                            } else if (position.is_unit_head && !position.is_division_head && !position.is_vp && !position.is_president) {
                                // Unit head's travel order - use unit head Excel route
                                excelUrl = `/unithead/travel-orders/${id}/excel`;
                            } else {
                                // Regular employee's travel order - use regular employee Excel route
                                excelUrl = `/travel-orders/${id}/excel`;
                            }
                        } else {
                            // Fallback to regular employee Excel if no position is found
                            excelUrl = `/travel-orders/${id}/excel`;
                        }
                        
                        // Open Excel in modal instead of new tab
                        openExcelModal(excelUrl);
                    } else {
                        alert('Error loading travel order details for Excel generation');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading travel order details for Excel generation');
                });
        }
    });
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.id === 'travelOrderModal') {
        closeTravelOrderModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTravelOrderModal();
    }
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out forwards;
    }
    
    .animate-slideIn {
        animation: slideIn 0.3s ease-out forwards;
    }
`;
document.head.appendChild(style);

// Include SweetAlert2 library
if (typeof Swal === 'undefined') {
    // Dynamically load SweetAlert2 if not already included
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    document.head.appendChild(script);
}

// Global variable to store the current cancel ID
let currentCancelId = null;

// Function to confirm cancellation with SweetAlert2 (with reason input like reject)
function confirmCancel(id) {
    // Store the current ID
    currentCancelId = id;
    
    // Check if modal already exists
    let modal = document.getElementById('cancelModal');
    let modalContent = document.getElementById('cancelModalContent');
    
    if (!modal) {
        // Create modal only if it doesn't exist
        const modalHtml = `
            <div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4 transition-all duration-200 hidden">
                <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full transform transition-all duration-200 scale-95 opacity-0 border-t-4 border-red-600" id="cancelModalContent">
                    <!-- Modal Header -->
                    <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between" style="background: linear-gradient(135deg, #dc3545 0%, #e83e8c 50%, #dc3545 100%);">
                        <div class="flex items-center gap-3">
                            <!-- Icon Circle -->
                            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-lg">
                                <!-- Red X icon -->
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white">Cancel Travel Order?</h3>
                        </div>
                    </div>
                    
                    <!-- Modal Content -->
                    <div class="px-6 py-4">
                        <div class="text-sm text-gray-800">
                            <p class="text-base font-semibold text-gray-900 mb-3">Please provide a reason for cancelling this travel order:</p>
                            
                            <textarea id="cancelReason" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all duration-200" rows="4" placeholder="Please explain why you are cancelling this travel order..." style="min-height: 100px; resize: vertical;"></textarea>
                            <p class="text-xs text-gray-500 mt-2"><i class="fas fa-exclamation-triangle"></i> This can't be undone.</p>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white gap-2">
                        <button onclick="confirmCancelAction()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all duration-200 text-sm font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-times-circle mr-1"></i> Yes, Cancel
                        </button>
                        <button onclick="closeCancelModal(true)" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-sm font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-check-circle mr-1"></i> No, Keep it
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Append modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Get references
        modal = document.getElementById('cancelModal');
        modalContent = document.getElementById('cancelModalContent');
        
        // Add click-outside-to-close functionality (preserves data)
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                // Click outside - just close modal, keep data
                closeCancelModal(false);
            }
        });
        
        // Add Escape key listener
        document.addEventListener('keydown', handleEscapeKey);
    }
    
    // Show modal (data is preserved if user previously typed)
    document.body.style.overflow = 'hidden';
    modal.classList.remove('hidden');
    
    // Animate modal in
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

// Handle Escape key to close modal (preserves data)
function handleEscapeKey(event) {
    if (event.key === 'Escape') {
        closeCancelModal(false);
    }
}

// Close cancel modal
function closeCancelModal(clearData = false) {
    const modal = document.getElementById('cancelModal');
    const modalContent = document.getElementById('cancelModalContent');
    
    if (modal && modalContent) {
        // Clear form data only if explicitly requested (when "No, Keep it" is clicked)
        if (clearData) {
            const textarea = document.getElementById('cancelReason');
            if (textarea) {
                textarea.value = '';
            }
        }
        
        // Animate out
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        
        // Hide modal after animation completes (don't remove from DOM)
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 200);
    }
}

// Confirm cancel action
function confirmCancelAction() {
    const reason = document.getElementById('cancelReason').value;
    
    // Validate that reason is provided and not just whitespace
    if (!reason || reason.trim() === '') {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Reason',
            text: 'Please provide a reason for cancellation',
            confirmButtonText: 'OK',
            customClass: {
                popup: 'animate__animated animate__shakeX'
            }
        });
        return;
    }
    
    // Close the custom modal first
    closeCancelModal();
    
    // Then proceed with cancellation
    setTimeout(() => {
        cancelTravelOrder(currentCancelId, reason);
    }, 200);
}

// Function to cancel the travel order via AJAX
function cancelTravelOrder(id, reason) {
    // Show loading indicator for exactly 2 seconds
    Swal.fire({
        title: 'Cancelling...',
        text: 'Please wait while we cancel your travel order.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        timer: 2000,  // Exactly 2 seconds
        didOpen: () => {
            Swal.showLoading();
        },
        willClose: () => {
            // After 2 seconds loading, send cancellation with reason
            fetch(`/travel-orders/${id}/cancel`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ reason: reason })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message immediately for 2 seconds then redirect
                    Swal.fire({
                        icon: 'success',
                        title: 'Cancelled!',
                        text: data.message,
                        timer: 2000,  // Exactly 2 seconds
                        showConfirmButton: false,
                        didOpen: () => {
                            // Auto close after 2 seconds
                        }
                    }).then((result) => {
                        // Redirect to cancelled tab after success message closes
                        window.location.href = window.location.href.replace(/\?.*/, '') + '?tab=cancelled';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to cancel the travel order.'
                    });
                }
            })
            .catch(error => {
                console.error('Error cancelling travel order:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while trying to cancel the travel order.'
                });
            });
        }
    });
}
</script>

{{-- Draft Travel Orders Table Rows --}}
@if(isset($tab) && $tab === 'draft')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle Delete Draft button click
    document.querySelectorAll('.delete-draft-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const travelOrderId = this.dataset.id;
            const travelOrderTitle = this.dataset.title;
            
            Swal.fire({
                title: 'Delete Draft?',
                html: `Are you sure you want to delete this Travel Order?<br>This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading SweetAlert for 2 seconds
                    Swal.fire({
                        title: 'Deleting...',
                        html: 'Please wait while we delete your draft',
                        timer: 2000,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    }).then(() => {
                        // After 2 seconds, show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Draft deleted successfully.',
                            timer: 2000,
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then(() => {
                            // After success message closes, submit the form
                            const form = document.getElementById(`delete-form-${travelOrderId}`);
                            if (form) {
                                form.submit();
                            } else {
                                fetch(`/travel-orders/${travelOrderId}/delete-draft`, {
                                    method: 'DELETE',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({})
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        location.reload();
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: data.message || 'Failed to delete draft.'
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'An error occurred while deleting the draft.'
                                    });
                                });
                            }
                        });
                    });
                }
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting draft:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while trying to delete the draft.'
                            });
                        });
                    }
                }
            });
        });
    });
});
</script>
@endif

