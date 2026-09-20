@extends('layouts.motorpool-admin')

@section('content')
@include('itineraries.partials.itinerary-form')
@endsection

@section('scripts')
<script src="{{ asset('js/itinerary-filter.js') }}"></script>
@endsection
