// Fix for edit draft travel order - properly format dates
window.editDraftTravelOrder = function(id) {
    console.log('✏️ [FIXED VERSION] Editing draft ID:', id);
    
    fetch(`/api/travel-orders/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const travelOrder = data.data;
                
                // Change modal title
                const modalTitle = document.getElementById('createTravelRequestModalLabel');
                if (modalTitle) {
                    modalTitle.textContent = 'Edit Travel Request';
                }
                
                // Fill fields with proper date formatting
                const destinationField = document.querySelector('[name="destination"]');
                const dateFromField = document.querySelector('[name="date_from"]');
                const dateToField = document.querySelector('[name="date_to"]');
                const departureTimeField = document.querySelector('[name="departure_time"]');
                const purposeField = document.querySelector('[name="purpose"]');
                const positionField = document.querySelector('[name="position_id"]');
                
                if (destinationField) destinationField.value = travelOrder.destination || '';
                if (dateFromField) {
                    dateFromField.value = travelOrder.date_from ? new Date(travelOrder.date_from).toISOString().split('T')[0] : '';
                }
                if (dateToField) {
                    dateToField.value = travelOrder.date_to ? new Date(travelOrder.date_to).toISOString().split('T')[0] : '';
                }
                if (departureTimeField) departureTimeField.value = travelOrder.departure_time || '';
                if (purposeField) purposeField.value = travelOrder.purpose || '';
                
                if (travelOrder.position_id && positionField) {
                    positionField.value = travelOrder.position_id;
                    const event = new Event('change');
                    positionField.dispatchEvent(event);
                }
                
                // Set form action and method
                const form = document.getElementById('createTravelRequestForm');
                form.action = `/travel-orders/${travelOrder.id}`;
                
                let methodField = document.getElementById('edit_method_field');
                if (!methodField) {
                    methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.id = 'edit_method_field';
                    form.appendChild(methodField);
                }
                methodField.value = 'PUT';
                
                // Open modal
                const modal = new bootstrap.Modal(document.getElementById('createTravelRequestModal'));
                modal.show();
                
                console.log('✅ Edit modal opened with pre-filled data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to load draft.'
            });
        });
};
