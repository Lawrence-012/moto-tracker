// Itinerary Creation - Dynamic Filtering Script
// Handles filtering of drivers, vehicles, and travel orders based on conflicts and capacity

(function() {
    'use strict';

    // Store original options for restoration
    let originalVehicles = [];
    let originalDrivers = [];
    let originalTravelOrders = [];

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
       console.log('Initializing itinerary form filters...');
        
        // Store original vehicle options
       const vehicleSelect = document.getElementById('vehicle_id');
       if (vehicleSelect) {
            originalVehicles = Array.from(vehicleSelect.options).map(opt => ({
                value: opt.value,
                text: opt.text,
                seatingCapacity: parseInt(opt.getAttribute('data-seating-capacity') || '0')
            }));
           console.log('Stored', originalVehicles.length, 'vehicles');
        }
        
        // Store original driver options  
       const driverSelect = document.getElementById('driver_id');
       if (driverSelect) {
            originalDrivers = Array.from(driverSelect.options).map(opt => ({
                value: opt.value,
                text: opt.text
            }));
           console.log('Stored', originalDrivers.length, 'drivers');
        }
        
        // Store original travel order options
       const travelOrderSelect = document.getElementById('travel_order_id');
       if (travelOrderSelect) {
            originalTravelOrders = Array.from(travelOrderSelect.options).map(opt => ({
                value: opt.value,
                text: opt.text
            }));
           console.log('Stored', originalTravelOrders.length, 'travel orders');
        }
        
        // Add event listeners for date changes
       const dateFromInput = document.getElementById('date_from');
       const dateToInput = document.getElementById('date_to');
        
       if (dateFromInput) {
            dateFromInput.addEventListener('change', function() {
               console.log('Date From changed:', this.value);
                filterVehiclesAndDrivers();
            });
        }
        
       if (dateToInput) {
            dateToInput.addEventListener('change', function() {
               console.log('Date To changed:', this.value);
                filterVehiclesAndDrivers();
            });
        }
        
       if (travelOrderSelect) {
            travelOrderSelect.addEventListener('change', function() {
               console.log('Travel Order changed:', this.value);
                prefillTravelOrderData(this);
                setTimeout(() => {
                    filterVehiclesAndDrivers();
                }, 100);
            });
        }
        
       if (vehicleSelect) {
            vehicleSelect.addEventListener('change', function() {
               console.log('Vehicle changed:', this.value);
                filterTravelOrdersByCapacity();
            });
        }
    });

    /**
     * Filter vehicles and drivers based on selected dates
     */
    async function filterVehiclesAndDrivers() {
       const dateFrom = document.getElementById('date_from')?.value;
       const dateTo = document.getElementById('date_to')?.value;
       const vehicleSelect = document.getElementById('vehicle_id');
       const driverSelect = document.getElementById('driver_id');
        
       if (!dateFrom || !dateTo) {
           console.log('No dates selected, restoring all options');
           if (vehicleSelect) restoreAllOptions(vehicleSelect, originalVehicles);
           if (driverSelect) restoreAllOptions(driverSelect, originalDrivers);
           return;
        }
        
       console.log('Filtering for date range:', dateFrom, 'to', dateTo);
        
        // Filter vehicles
       if (vehicleSelect) {
            await filterVehicles(dateFrom, dateTo);
        }
        
        // Filter drivers
       if (driverSelect) {
            await filterDrivers(dateFrom, dateTo);
        }
    }

    /**
     * Filter vehicles based on maintenance and itinerary conflicts
     */
    async function filterVehicles(dateFrom, dateTo) {
       const vehicleSelect = document.getElementById('vehicle_id');
       const currentVehicleId = vehicleSelect?.value;
        
        try {
           console.log('Fetching vehicle conflicts...');
           const response = await fetch(`/api/vehicles/check-conflicts?date_from=${encodeURIComponent(dateFrom)}&date_to=${encodeURIComponent(dateTo)}`);
           const conflictData = await response.json();
            
           console.log('Vehicle conflict data:', conflictData);
            
           const conflictingVehicleIds = conflictData.conflictingVehicles || [];
           const underMaintenanceIds = conflictData.underMaintenance || [];
            
            // Filter out vehicles that are under maintenance or have conflicts
           const availableVehicles = originalVehicles.filter(v => {
               const vehicleId = parseInt(v.value);
               if (v.value === '') return true; // Keep empty option
               if (underMaintenanceIds.includes(vehicleId)) {
                   console.log('  Excluding vehicle', vehicleId, '- Under maintenance');
                   return false;
                }
               if (conflictingVehicleIds.includes(vehicleId)) {
                   console.log('  Excluding vehicle', vehicleId, '- Has itinerary conflict');
                   return false;
                }
               return true;
            });
            
           console.log('Available vehicles:', availableVehicles.length);
            
            // Update dropdown
            updateDropdown(vehicleSelect, availableVehicles, currentVehicleId);
            
            // If current vehicle was filtered out, clear selection
           if (currentVehicleId && !availableVehicles.find(v => v.value === currentVehicleId)) {
               console.log('Current vehicle was filtered out, clearing selection');
                vehicleSelect.value = '';
            }
            
        } catch (error) {
           console.error('Error filtering vehicles:', error);
           restoreAllOptions(vehicleSelect, originalVehicles);
        }
    }

    /**
     * Filter drivers based on itinerary conflicts
     */
    async function filterDrivers(dateFrom, dateTo) {
       const driverSelect = document.getElementById('driver_id');
       const currentDriverId = driverSelect?.value;
        
        try {
           console.log('Fetching driver conflicts...');
           const response = await fetch(`/api/drivers/check-conflicts?date_from=${encodeURIComponent(dateFrom)}&date_to=${encodeURIComponent(dateTo)}`);
           const conflictData = await response.json();
            
           console.log('Driver conflict data:', conflictData);
            
           const conflictingDriverIds = conflictData.conflictingDrivers || [];
            
            // Filter out drivers with conflicts (only keep active drivers without conflicts)
           const availableDrivers = originalDrivers.filter(d => {
               const driverId = parseInt(d.value);
               if (d.value === '') return true; // Keep empty option
               if (conflictingDriverIds.includes(driverId)) {
                   console.log('  Excluding driver', driverId, '- Has itinerary conflict');
                   return false;
                }
               return true;
            });
            
           console.log('Available drivers:', availableDrivers.length);
            
            // Update dropdown
            updateDropdown(driverSelect, availableDrivers, currentDriverId);
            
            // If current driver was filtered out, clear selection
           if (currentDriverId && !availableDrivers.find(d => d.value === currentDriverId)) {
               console.log('Current driver was filtered out, clearing selection');
                driverSelect.value = '';
            }
            
        } catch (error) {
           console.error('Error filtering drivers:', error);
           restoreAllOptions(driverSelect, originalDrivers);
        }
    }

    /**
     * Filter travel orders based on selected vehicle capacity
     */
    function filterTravelOrdersByCapacity() {
       const vehicleSelect = document.getElementById('vehicle_id');
       const travelOrderSelect = document.getElementById('travel_order_id');
        
       if (!vehicleSelect || !travelOrderSelect) return;
        
       const selectedVehicle = originalVehicles.find(v => v.value === vehicleSelect.value);
       const seatingCapacity = parseInt(selectedVehicle?.seatingCapacity) || 0;
        
       console.log('Vehicle seating capacity:', seatingCapacity);
        
       if (seatingCapacity === 0) {
            // No vehicle selected or no capacity limit, show all travel orders
           console.log('No capacity limit, showing all travel orders');
           restoreAllOptions(travelOrderSelect, originalTravelOrders);
           return;
        }
        
        // Get currently selected travel order IDs
       const selectedTravelOrderIds = Array.from(travelOrderSelect.selectedOptions)
            .map(opt => opt.value)
            .filter(id => id !== '');
        
       console.log('Currently selected travel orders:', selectedTravelOrderIds);
        
        // Count passengers from already selected travel orders
        // We need to fetch this data from the server
       const passengerCountPromises = selectedTravelOrderIds.map(toId => {
           return fetch(`/api/travel-orders/${toId}/passengers`)
                .then(response => response.json())
                .then(data => {
                   return data.passengers ? data.passengers.length : 1; // Default to 1 if no data
                })
                .catch(error => {
                   console.error('Error fetching passengers for TO', toId, ':', error);
                   return 1; // Default to 1 passenger on error
                });
        });
        
        Promise.all(passengerCountPromises)
            .then(counts => {
               const currentPassengerCount = counts.reduce((sum, count) => sum + count, 0);
               const remainingCapacity = seatingCapacity - currentPassengerCount;
                
               console.log('Current passengers:', currentPassengerCount);
               console.log('Remaining capacity:', remainingCapacity);
                
                // Filter travel orders: only show those that can fit in remaining capacity
               const availableTravelOrders = originalTravelOrders.filter(to => {
                   if (to.value === '') return true; // Keep empty option
                    
                    // If already selected, keep it
                   if (selectedTravelOrderIds.includes(to.value)) return true;
                    
                    // For unselected travel orders, assume 1 passenger minimum
                    // In a real implementation, you'd fetch passenger count for each
                   return remainingCapacity >= 1;
                });
                
               console.log('Available travel orders after filtering:', availableTravelOrders.length);
                updateDropdown(travelOrderSelect, availableTravelOrders, travelOrderSelect.value);
            })
            .catch(error => {
               console.error('Error calculating passenger count:', error);
                // On error, just show all travel orders
               restoreAllOptions(travelOrderSelect, originalTravelOrders);
            });
    }

    /**
     * Helper function to update dropdown options
     */
    function updateDropdown(selectElement, options, selectedValue = '') {
       if (!selectElement) return;
        
       const currentValue = selectElement.value;
        selectElement.innerHTML = '';
        
        options.forEach(opt => {
           const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.text;
           if (opt.seatingCapacity !== undefined) {
                option.setAttribute('data-seating-capacity', opt.seatingCapacity);
            }
           if (opt.value === selectedValue || opt.value === currentValue) {
                option.selected = true;
            }
            selectElement.appendChild(option);
        });
        
       console.log('Updated dropdown with', options.length, 'options');
    }

    /**
     * Helper function to restore all options
     */
    function restoreAllOptions(selectElement, originalOptions) {
       if (!selectElement) return;
        
        selectElement.innerHTML = '';
        
        originalOptions.forEach(opt => {
           const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.text;
           if (opt.seatingCapacity !== undefined) {
                option.setAttribute('data-seating-capacity', opt.seatingCapacity);
            }
            selectElement.appendChild(option);
        });
        
       console.log('Restored all', originalOptions.length, 'options');
    }

    /**
     * Prefill travel order data (existing function)
     */
    window.prefillTravelOrderData = function(selectElement) {
       const selectedOption = selectElement.options[selectElement.selectedIndex];
       const destinationField = document.getElementById('destination');
        
       if (selectedOption.value === '') {
            document.getElementById('date_from').value = '';
            document.getElementById('date_to').value = '';
            destinationField.value = '';
            destinationField.dispatchEvent(new Event('input'));
           return;
        }
        
       const dateFrom = selectedOption.getAttribute('data-date-from');
       const dateTo = selectedOption.getAttribute('data-date-to');
       const destination = selectedOption.getAttribute('data-destination');
        
       if (dateFrom) {
            document.getElementById('date_from').value = dateFrom;
        }
       if (dateTo) {
            document.getElementById('date_to').value = dateTo;
        }
       if (destination) {
            destinationField.value = destination;
            // Fires the pin-clearing listener on the destination search
            // widget, since this is a programmatic value change (typing
            // triggers 'input' natively, but setting .value doesn't).
            destinationField.dispatchEvent(new Event('input'));
        }
    };

    // Expose functions globally
    window.filterTravelOrdersByCapacity = filterTravelOrdersByCapacity;
    window.validatePassengerCapacity = validatePassengerCapacity;
    window.validateCapacityBeforeSubmit = validateCapacityBeforeSubmit;

})();

/**
 * Validate total passengers against vehicle capacity before form submission
 */
async function validatePassengerCapacity() {
  console.log('=== STARTING CAPACITY VALIDATION ===');
    
  const vehicleSelect = document.getElementById('vehicle_id');
  const travelOrderSelect = document.getElementById('travel_order_id');
    
  console.log('Vehicle select element:', vehicleSelect);
  console.log('Travel Order select element:', travelOrderSelect);
    
  if (!vehicleSelect || !travelOrderSelect) {
  console.log('Missing elements, returning true');
 return true;
    }
    
  const selectedVehicle = originalVehicles.find(v => v.value === vehicleSelect.value);
  const seatingCapacity = parseInt(selectedVehicle?.seatingCapacity) || 0;
    
  console.log('Selected vehicle:', selectedVehicle);
  console.log('Vehicle value:', vehicleSelect.value);
  console.log('Seating capacity:', seatingCapacity);
    
  if (seatingCapacity === 0) {
  console.log('⚠️ No vehicle selected or capacity is 0, skipping validation');
 return true; // No validation if no vehicle selected
    }
    
   // Get all selected travel order IDs (main + additional would be handled separately)
  const selectedTravelOrderIds = Array.from(travelOrderSelect.selectedOptions)
        .map(opt => opt.value)
        .filter(id => id !== '');
    
  console.log('Selected Travel Order IDs:', selectedTravelOrderIds);
    
  if (selectedTravelOrderIds.length === 0) {
  console.log('No travel orders selected, returning true');
 return true;
    }
    
   // Fetch passenger counts for all selected travel orders
  console.log('Fetching passenger counts for', selectedTravelOrderIds.length, 'travel orders...');
    
  const passengerCountPromises = selectedTravelOrderIds.map(toId => {
  console.log('  Fetching passengers for TO ID:', toId);
 return fetch(`/api/travel-orders/${toId}/passengers`)
           .then(response => {
          console.log('  Response status for TO', toId, ':', response.status);
          return response.json();
           })
           .then(data => {
          console.log('  Passenger data for TO', toId, ':', data);
          const count = data.passengers ? data.passengers.length : 1;
          console.log('  Passenger count for TO', toId, ':', count);
          return count;
           })
           .catch(error => {
          console.error('  ERROR fetching passengers for TO', toId, ':', error);
          return 1;
           });
   });
    
   try {
  const counts = await Promise.all(passengerCountPromises);
  const totalPassengers = counts.reduce((sum, count) => sum + count, 0);
        
  console.log('=== VALIDATION RESULTS ===');
  console.log('Vehicle capacity:', seatingCapacity);
  console.log('Total passengers:', totalPassengers);
  console.log('Individual counts:', counts);
        
  if (totalPassengers > seatingCapacity) {
   const message = `⚠️ Validation Error:\n\nTotal passengers (${totalPassengers}) exceeds vehicle seating capacity (${seatingCapacity}).\n\nPlease select a vehicle with higher capacity or reduce the number of travel orders.`;
     alert(message);
   console.log('❌ VALIDATION FAILED - Preventing submission');
   return false; // Prevent submission
    }
        
  console.log('✓ VALIDATION PASSED');
 return true; // Allow submission
    } catch (error) {
  console.error('❌ ERROR during validation:', error);
    // On error, allow submission to prevent blocking
 return true;
    }
}

/**
 * Form submit handler to validate capacity before submission
 */
window.validateCapacityBeforeSubmit = async function(event) {
  console.log('=== FORM SUBMIT HANDLER CALLED ===');
  console.log('Event type:', event.type);
  console.log('Form ID:', event.target.id);
    
  try {
   const isValid = await validatePassengerCapacity();
    
   console.log('Validation result:', isValid);
    
   if (!isValid) {
      event.preventDefault();
      event.stopPropagation();
     console.log('❌ Form submission PREVENTED');
     return false;
    }
    
   console.log('✓ Form submission ALLOWED');
   return true;
  } catch (error) {
   console.error('ERROR in validation handler:', error);
    // On error, allow submission to prevent blocking
   return true;
  }
};
