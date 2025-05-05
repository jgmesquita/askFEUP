@extends('layouts.app')

@section('content')
    <section id="explore-tags">
        <h2>Tags</h2>
        <div class="description">
            <p>A tag is a keyword that groups your question with other questions with similar content.</p>
            <p>In this page, you can search for tags and follow them! Take a look!</p>
        </div>
        <form class="search input-btn-form" action="{{ route('show-tags') }}" method="GET">
            <button>
                <span><i class="material-icons">search</i></span>
            </button>
            <input 
                type="search" 
                name="tag-search" 
                value="{{ request()->get('tag-search') }}" 
                class="search-field" 
                placeholder="Search tag..."
                autocomplete="off"
            />
        </form>
        @if (Auth::user())
        <div class="filters">
            <div class="dropdown-tagfilter dropdown" onclick="toggleDropdown(event)">
                <button id="taggers" class="icon-text filter">
                    <i class="material-symbols-outlined">keyboard_arrow_down</i>
                    <span>Tag Filter</span>
                </button>
                <div class="dropdown-content hidden">
                    <ul>
                        <li class="tagfil" onclick="filterhandleTag(this)">Following</li>
                        <li class="tagfil" onclick="filterhandleTag(this)">Not Following</li>
                        <li id="startfiltag" class="tagfil-sel" onclick="filterhandleTag(this)">All</li>
                    </ul>
                </div>
            </div>
        </div>
        @endif
        <div class="tags">
        @foreach ($tags as $tag)
            <article class="each_tag">
                <div class="action-item tag"  onclick="navigateToTagsQuestions('{{ $tag->name }}')" style="background-color:<?= $tag->color; ?>; color:<?= $tag->color_text ?>;">
                    <span class="name_tag">{{ $tag->name }}</span>
                </div>
                <p>{{ $tag->questions_count ?? 0 }} questions</p>
                <p>{{ $tag->users_count ?? 0 }} users following</p>

                @can('follow', $tag)
                <form method="POST" action="{{ route('follow-tag', ['id' => $tag->id]) }}" enctype="multipart/form-data" class="form-group" id="follow-tag-{{ $tag->id }}">
                    @csrf
                    <button type="submit">
                        <div class="icon-text">
                            <i class="material-symbols-outlined">add</i>
                            <span>Follow</span>
                        </div>
                    </button>
                </form>
                @endcan
                @can('unfollow', $tag)
                <form method="POST" action="{{ route('unfollow-tag', ['id' => $tag->id]) }}" enctype="multipart/form-data" class="form-group" id="unfollow-tag-{{ $tag->id }}">
                    @csrf
                    <button type="submit" class="active">
                        <div class="icon-text">
                            <i class="material-symbols-outlined">add</i>
                            <span>Unfollow</span>
                        </div>
                    </button>
                </form>
                @endcan
            </article>         
        @endforeach
        </div>
    </section>
    <div class="arrow-btn-container">
        <div class="pag"></div>
        <div class="leftside">
            <button class="arrow-btn prev" onclick="prevPageTags()" disabled>
                <i class="material-icons">chevron_left</i>
            </button>
            <div class="paginator" id="paginator">

            </div>
            <button class="arrow-btn next" onclick="nextPageTags()" {{ $tags->count() < 10 ? 'disabled' : '' }}>
                <i class="material-icons">chevron_right</i>
            </button>
        </div>
    </div>
@endsection