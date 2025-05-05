<nav class="side-nav">
    <ul>
        <li class="{{ Route::is('home') ? 'selected' : '' }}">
            <a href="{{ url('home') }}">
                <i class="material-symbols-outlined">home</i>
                Home
            </a>
        </li>
        <li class="{{ Route::is('show-tags') ? 'selected' : '' }}">
            <a href="{{ url('show-tags') }}">
                <i class="material-symbols-outlined">sell</i>
                Tags
            </a>
        </li>
    </ul>
    @if(
        Gate::allows('showFollowedQuestions', Auth::user()) 
    )
    <ul>
        @can('showFollowedQuestions', Auth::user())
        <li class="{{ Route::is('questions.followed') ? 'selected' : '' }}">
            <a href="{{ url('questions-followed') }}">   
                <i class="material-symbols-outlined">bookmark</i>
                Followed Questions
            </a>           
        </li>
        @endcan
    </ul>
    @endif
    <ul>
        <li class="{{ Route::is('leaderboard') ? 'selected' : '' }}">
            <a href="{{ url('leaderboard') }}">
                <i class="material-symbols-outlined">social_leaderboard</i>
                Leaderboard
            </a>
        </li>
    </ul>
    <ul>
        <li class="{{ Route::is('about') ? 'selected' : '' }}">
            <a href="{{ url('about') }}">
                <i class="material-symbols-outlined">groups</i>
                About Us
            </a>
        </li>
        <li class="{{ Route::is('contacts') ? 'selected' : '' }}">
            <a href="{{ url('contacts') }}">
                <i class="material-symbols-outlined">contact_support</i>
                Contacts
            </a>
        </li>
    </ul>
</nav>