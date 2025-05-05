@forelse($users as $user)
    @include('partials.admin_users', ['user' => $user])  
@endforelse