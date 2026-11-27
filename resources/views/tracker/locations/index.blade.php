@extends('layouts.app')

@section('title', 'GPS Location Activity')
@section('page-title', 'Location Activity')
@section('page-subtitle', 'Review device positions, signal quality, and the latest location updates.')

@push('styles')
    @include('tracker.locations.partials.map-styles')
@endpush

@section('content')
    @include('tracker.locations.partials.summary-cards')
    @include('tracker.locations.partials.records-table')
@endsection

@push('modals')
    @include('tracker.locations.partials.map-modal')
@endpush

@push('scripts')
    @include('tracker.locations.partials.map-script')
@endpush
