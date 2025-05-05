@extends('layouts.app')

@php
    $tabs = [
        [
            'url' => 'manager/load',
            'type' => 'reports',
            'page' => 1,
            'label' => 'Reports', 
            'selected' => true
        ],
    ];
@endphp

@section('content')    
    <section id="mod-center">     
        @include('partials.section-nav', ['tabs' => $tabs])
        @include('partials.manage-reports', $reportedPosts)
    </section>
@endsection