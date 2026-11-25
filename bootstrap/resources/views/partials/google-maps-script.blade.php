@php
    $googleMapsApiKey = config('services.google.maps.api_key');
@endphp
@if($googleMapsApiKey)
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&libraries=places,geometry"></script>
@else
    {{-- No key configured yet - pages check this flag and show a friendly
         placeholder instead of a broken map. Add GOOGLE_MAPS_API_KEY to
         .env once you have one and this will start working automatically. --}}
    <script>window.GOOGLE_MAPS_KEY_MISSING = true;</script>
@endif