@extends('layouts.app')

@php
    $tabs = [
        [
            'url' => 'home', 
            'type' => 'trending', 
            'page' => 1, 
            'label' => 'Trending', 
            'selected' => true
        ],
        [
            'url' => 'home', 
            'type' => 'new', 
            'page' => 1, 
            'label' => 'New', 
            'selected' => false
        ],
        [
            'url' => 'home', 
            'type' => 'foryou', 
            'page' => 1, 
            'label' => 'For You', 
            'selected' => false
        ],
    ];
@endphp

@section('content')        
    <section id="home">
        @if (Auth::check() && !request('search'))
            @include('partials.section-nav', ['tabs' => $tabs])
        @endif

        @if (count($questions) > 0 || request('question-search'))
                @if (request('question-search'))
                    <div class="flex-container">
                        <h1>Search results for "{{ request('question-search') }}"</h1>
                @endif

                @if (count($questions) > 0)
                    <div class="filters">
                        <div class="dropdown-timefilter dropdown" onclick="toggleDropdown(event)">
                            <button class="icon-text filter">
                                <i class="material-symbols-outlined">keyboard_arrow_down</i>
                                <span>Time filter</span>
                            </button>
                            <div class="dropdown-content hidden">
                                <ul>
                                    <li class="time" onclick="filterhandle(this)">Last 24 Hours</li>
                                    <li class="time" onclick="filterhandle(this)">Past Week</li>
                                    <li class="time" onclick="filterhandle(this)">Past Month</li>
                                    <li id="starttime" class="time-selected" onclick="filterhandle(this)">All Time</li>
                                </ul>
                            </div>
                        </div>
                        <div class="dropdown-timefilter dropdown" onclick="toggleDropdown(event)">
                            <button class="icon-text filter">
                                <i class="material-symbols-outlined">keyboard_arrow_down</i>
                                <span>Answered Filter</span>
                            </button>
                            <div class="dropdown-content hidden">
                                <ul>
                                    <li class="answerfil" onclick="filterhandleAnswer(this)">Answered</li>
                                    <li class="answerfil" onclick="filterhandleAnswer(this)">Unanswered</li>
                                    <li id="startanswerfil" class="answerfil-selected" onclick="filterhandleAnswer(this)">All</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif 
            @if (request('question-search'))
                </div>
            @endif
        @endif
            
        @if ($questions && $questions->isNotEmpty())
            @include('partials.question-list', ['type' => 'questions', 'posts' => $questions])
        @else
            @include('partials.empty-container', ['message' => $emptyMessage ?? "No results found for your search"])
        @endif
        <div class="loadMore">
            <div class="loader hidden"></div>
        </div>
    </section>
@endsection