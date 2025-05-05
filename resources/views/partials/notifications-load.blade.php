
@foreach ($notificationsbanger as $notification)
    @include('partials.notification', ['notification' => $notification])
@endforeach