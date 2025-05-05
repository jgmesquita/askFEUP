@extends('layouts.app')

@php
    $tabs = [
        [
            'url' => 'profile/load',
            'type' => 'question',
            'page' => 1,
            'label' => 'Questions',
            'selected' => true, 
        ],
        [
            'url' => 'profile/load',
            'type' => 'answer',
            'page' => 1,
            'label' => 'Answers',
            'selected' => false,
        ],
    ];
@endphp

@section('content')
    <section id="profile" data-item-id="{{ $user->id }}">
        <div class="flex-container ">
            <div class="profile-info">
                <div class="profile-picture">
                    <img src="{{ asset($user->icon ? $user->icon : 'images/profile/default-profile.jpg') }}" alt="{{ $user->name }}'s Profile Picture">
                </div>          
                <div class="profile-info-text">
                    <h1>{{ $user->name }}</h1>
                    <h2>{{ '@' . $user->tagname }}</h2>
                    <p class="degree-info"><strong>Degree:</strong> {{ $user->degree }}</p>
                </div>
            </div>

            @include('partials.admin-actions')
        </div>
        
        <div>     
            @if ($tags->isNotEmpty())
                <div class="interests">
                    <p><strong>My Interests</strong></p>
                    <div class="tags">
                        @foreach($tags as $tag)
                            <span class="action-item tag">{{ $tag->name }}</span>
                        @endforeach
                        <button class="show-more" onclick="toggleProfileTags()">▼ Show More</button>
                    </div>
                </div>
            @endif

            <div class='questions-badge'>
                @include('partials.badge-container', ['badges' => $user->badges])

                <div class='questions-nav'>
                    @include('partials.section-nav', ['tabs' => $tabs])

                    <div class="post-list">
                    </div>
                    <div class="loadMore">
                        <div class="loader hidden"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection