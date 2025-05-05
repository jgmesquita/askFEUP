@extends('layouts.app')

@php
    $tabs = [
        [
            'url' => 'manager/load',
            'type' => 'users',
            'page' => 1,
            'label' => 'Users', 
            'selected' => true
        ],
        [
            'url' => 'manager/load',
            'type' => 'tags',
            'page' => 1,
            'label' => 'Manage Tags', 
            'selected' => false
        ],
        [
            'url' => 'manager/load',
            'type' => 'reports',
            'page' => 1,
            'label' => 'Reports', 
            'selected' => false
        ],
        [
            'url' => 'manager/load',
            'type' => 'statistics',
            'page' => 1,
            'label' => 'Statistics', 
            'selected' => false
        ],
    ];
@endphp

@section('content')
    <section id="admin-center">     
        @include('partials.section-nav', ['tabs' => $tabs])
        @include('partials.manage-users', $users)
    </section>
@endsection