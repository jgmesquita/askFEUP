<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Styles -->
        <link href="{{ url('css/milligram.min.css') }}" rel="stylesheet">
        <link href="{{ url('css/app.css') }}" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Material+Icons" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Material+Symbols+Outlined" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">


        <script>
            // Fix for Firefox autofocus CSS bug
            // See: http://stackoverflow.com/questions/18943276/html-5-autofocus-messes-up-css-loading/18945951#18945951
        </script>
        <script src={{ url('js/app.js') }} defer>
        </script>
    </head>
    <body class='{{ Auth::user()?->is_dark_mode ? "dark-mode" : "" }}'>
        <main>
            <header>
                <div>
                    <button id="toggle-nav">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <button id="return-search" class="hidden" onclick='removeFullscreen()'>
                        <span class="material-symbols-outlined">arrow_back</span>
                    </button>
                    <a href="{{ route('home') }}">
                        <img class="logo" id="logo" src="{{ Auth::user()?->is_dark_mode ? asset('images/logo/logo_dark.png') : asset('images/logo/logo_light.png') }}" alt="askFEUP logo">
                        <img class="logo-small" id="logo-small" src="{{ Auth::user()?->is_dark_mode ? asset('images/logo/logo_small.png') : asset('images/logo/logo_small.png') }}" alt="askFEUP logo">
                    </a>
                </div>
                <form class="search input-btn-form" action="{{ route('home') }}" method="GET">
                    <button>
                        <span><i class="material-icons">search</i></span>
                    </button>
                    <input 
                        type="search" 
                        id="header-search"
                        name="question-search" 
                        value="{{ request()->get('question-search') }}" 
                        class="search-field" 
                        placeholder="Search askFEUP..." 
                        onfocus="onSearchActive()" 
                        autocomplete="off"
                    >
                    <div class="search-helper hidden">
                        <div class="exact">
                            <span>"words here"</span>
                            <span>exact search</span>
                        </div>                       
                        <div class="tagsea">
                            <span>(tag here)</span>
                            <span>search by tag</span>
                        </div>    
                    </div>
                </form>
                <div>
                    <span class="material-icons" id='search-btn-open' onclick='toggleFullscreen()'>search</span> 
                    @if (Auth::check())    
                    <a href="{{ url('notifications') }}">
                        <div class="notifications">
                            <i class="material-symbols-outlined">notifications</i>
                            <div class="number-notifications">
                                <span>{{ Auth::user()->unreadNotificationsCount() }}</span>
                            </div>
                        </div>
                    </a>
                    @can('create', App\Models\QuestionPost::class)
                    <a class="button" href="{{ url('/new-question') }}">+ Question</a>
                    @endcan
                    <div class="dropdown" onclick="toggleDropdown(event)">
                        <div class="head_button profile-picture">
                            <img src="{{ asset(Auth::user()->icon ? Auth::user()->icon : 'images/profile/default-profile.jpg') }}" alt="authenticated user icon">
                        </div>
                        <div class="dropdown-content hidden">
                            <ul>
                                <li class="icon-text">
                                    <a href="{{ url('profile') }}">
                                        <i class="material-icons">person</i>
                                        <span>View Profile</span>
                                    </a>
                                </li>
                                <li class="icon-text">
                                    <a href="{{ url('edit-profile') }}">
                                        <i class="material-icons">settings</i>
                                        <span>Edit Profile</span>
                                    </a>
                                </li>
                                <li>
                                    <div class='icon-text dark-mode-label' onclick="event.stopPropagation()">
                                        <i class="material-symbols-outlined">{{ !Auth::user()->is_dark_mode ? 'dark_mode' : 'light_mode' }}</i>
                                        <span>{{ !Auth::user()->is_dark_mode ? 'Dark' : 'Light' }} Mode</span>
                                    </div> 
                                    <label class="switch">
                                    <input type="checkbox" onchange="updateDarkMode(this)" {{ Auth::user()->is_dark_mode ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </li>
                                @can('showMod', Auth::user())
                                <li class="icon-text">
                                    <a href="{{ url('manage-reports') }}">
                                        <i class="material-icons">flag</i>
                                        <span>Mod Center</span>
                                    </a>
                                </li>
                                @endcan
                                @can('showAdmin', Auth::user())
                                <li class="icon-text">
                                    <a href="{{ url('admin-center') }}">
                                        <i class="material-icons">admin_panel_settings</i>
                                        <span>Admin Center</span>
                                    </a>
                                </li>
                                @endcan
                                <li class="icon-text">
                                    <a href="{{ url('/logout') }}">
                                        <i class="material-icons">logout</i>
                                        <span>Logout</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                        
                @else
                <a href="{{ route('login') }}"><button id='login'>Login</button></a>
                <a href="{{ route('register') }}"><button id='register'>Register</button></a>
                @endif
            </header>

            @include('partials.side-nav')
            
            <section id="content">
                @php
                    $messages = collect();

                    // Session messages
                    foreach (['success', 'error', 'info'] as $type) {
                        if (session($type)) {
                            $messages->push(['type' => $type, 'message' => session($type)]);
                            }
                        }

                        // Validation errors
                        if ($errors->any()) {
                            foreach ($errors->all() as $error) {
                                $messages->push(['type' => 'error', 'message' => $error]);
                            }
                        }
                    @endphp

                @foreach ($messages as $msg)
                    @include('partials.status-msg', ['type' => $msg['type'], 'message' => $msg['message']])
                @endforeach

                @yield('content')
            </section>
        </main>
    </body>
</html>