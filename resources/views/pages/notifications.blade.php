@extends('layouts.app')

@section('content')
    <h2>Notifications</h2>
    <section id="notifications">
        @forelse ($notifications as $notification)
            @include('partials.notification', ['notification' => $notification])
        @empty
            <p>No notifications found.</p>
        @endforelse
    </section>
    <div class="loadMore">
                <div class="loader hidden"></div>
    </div>
 
@endsection