@foreach ($users as $user)
    <tr onclick="window.location='/profile/{{ $user->id }}';" class="hoverable">
        <td class="rank"> <div class="lb_rank"> {{ $loop->iteration }} </div> </td>
        <td>
            <div class="lb_user">
                <div class="lb_picture">
                    <img src="{{ asset($user->icon ?? 'images/profile/default-profile.jpg') }}" alt="{{ $user->name }}'s Profile Picture" >
                </div>
                <div class="lb_username">{{ '@' . $user->tagname }}</div>
            </div> 
        </td>
        <td>  <div class="lb_likes"> {{ $user->total_likes }} </div> </td>
    </tr>
@endforeach