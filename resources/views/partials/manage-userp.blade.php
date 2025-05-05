@foreach ($users as $user)
    <article data-item-id="{{ $user->id }}" onclick="window.location='/profile/{{ $user->id }}';">
        <div>
            <div class="profile-picture">
                <img src="{{ asset($user->icon ? $user->icon : 'images/profile/default-profile.jpg') }}" alt="{{ $user->name }}'s Profile Picture">
            </div> 
            <div>
                <p class='user-tagname'>{{ '@' . $user->tagname }}</p>
                <p class='user-name'>{{ $user->name }}</p>  
                <p class='user-email'>{{ $user->email }}</p>
            </div>
        </div>
        <div>
            <div class="action-item {{ $user->is_banned ? 'banned' : ($user->is_admin ? 'admin' : ($user->is_moderator ? 'moderator' : 'user')) }}">
                @if ($user->is_banned) 
                    Banned
                @elseif ($user->is_admin) 
                    Admin
                @elseif ($user->is_moderator) 
                    Moderator
                @else 
                    User
                @endif
            </div>
            @include('partials.admin-actions', ['user' => $user])
        </div>        
    </article>
    @endforeach