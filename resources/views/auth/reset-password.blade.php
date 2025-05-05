@extends('layouts.app')

@section('content')
<div class="auth-container">
    <form method="POST" action="/send">
        @csrf
        <h2>Reset your Password</h2>
        <p>Write your email address and we will send you a link for you to reset your password.</p>

        <input id="email" type="email" name="email" placeholder="Email" required autofocus>

        <div class="button-group">
            <button type="submit">
                Reset Password
            </button>
        </div>
    </form>
</div>
@endsection