@if (
    Gate::allows('ban', $user) || 
    Gate::allows('revokeBan', $user) || 
    Gate::allows('makeModerator', $user) || 
    Gate::allows('removeModerator', $user) ||
    Gate::allows('delete', $user)
)
    <div class="dropdown options admin-options" onclick="toggleDropdown(event)">
        <button><i class="material-icons">more_horiz</i></button>
        <div class="dropdown-content hidden">
            <ul>
                @can('ban', $user)
                <li>     
                    <form method="POST" action="{{ route('ban-user', ['id' => $user->id]) }}">
                        @csrf                                  
                        <button 
                            class="icon-text" 
                            type="submit" 
                            @if (request()->is('*admin-center*')) onclick="toggleBanUser(event, 'ban')" @endif
                        >
                            <i class="material-symbols-outlined">person_remove</i>
                            <span>Ban User</span>
                        </button>
                    </form>                        
                </li>
                @endcan

                @can('revokeBan', $user)
                <li>     
                    <form method="POST" action="{{ route('revoke-ban', ['id' => $user->id]) }}">
                        @csrf                                  
                        <button 
                            class="icon-text" 
                            type="submit" 
                            @if (request()->is('*admin-center*')) onclick="toggleBanUser(event, 'revoke-ban')" @endif
                        >
                            <i class="material-symbols-outlined">person_add</i>
                            <span>Revoke Ban</span>
                        </button>
                    </form>                        
                </li> 
                @endcan

                @can('delete', $user)
                <li class="icon-text" onclick="deleteUser(this)">     
                    <i class="material-symbols-outlined">delete</i>
                    <span>Delete User</span>                  
                </li> 
                @endcan

                @can('makeModerator', $user)
                <li>
                    <form method="POST" action="{{ route('make-moderator', ['id' => $user->id]) }}">
                        @csrf
                        <button 
                            class="icon-text" 
                            type="submit" 
                            @if (request()->is('*admin-center*')) onclick="toggleBanUser(event, 'moderator')" @endif
                        >
                            <i class="material-symbols-outlined">add_moderator</i>
                            <span>Make Moderator</span>
                        </button>
                    </form>
                </li>
                @endcan

                @can('removeModerator', $user)
                <li>
                    <form method="POST" action="{{ route('remove-moderator', ['id' => $user->id]) }}">
                        @csrf
                        <button 
                            class="icon-text" 
                            type="submit" 
                            @if (request()->is('*admin-center*')) onclick="toggleBanUser(event, 'remove-moderator')" @endif
                        >
                            <i class="material-symbols-outlined">remove_moderator</i>
                            <span>Remove Moderator</span>
                        </button>
                    </form>
                </li> 
                @endcan  
            </ul>
        </div>
    </div> 
@endif