@extends('layouts.employee')

@section('content')
@include('itineraries.partials.itinerary-form')
@endsection

@section('scripts')
<script>
function prefillTravelOrderData(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const destinationField = document.getElementById('destination');
    
    if (selectedOption.value === '') {
        // Clear date and destination fields if no travel order is selected
        document.getElementById('date_from').value = '';
        document.getElementById('date_to').value = '';
        destinationField.value = '';
        destinationField.dispatchEvent(new Event('input'));
        // Purpose should remain unchanged as it's independent
        return;
    }
    
    // Get data from the selected option
    const dateFrom = selectedOption.getAttribute('data-date-from');
    const dateTo = selectedOption.getAttribute('data-date-to');
    const destination = selectedOption.getAttribute('data-destination');
    
    // Prefill only date and destination fields (purpose is independent)
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
    // Purpose field is left unchanged as it should be entered independently
    
}
</script>
@endsection