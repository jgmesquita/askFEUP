@extends('layouts.app')

@section('content')
<div class="auth-container">
    <form method="POST" action="/new-password">
        @csrf
        <h2>Change your Password</h2> 
        <p>Enter a new password below to change your password</p>

        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="form-group">
            <div class="phrase">
                <label for="password">New Password</label>
                <p class="mandatory"> * </p>
            </div>
            <input id="password" type="password" name="password" required>
        </div>

        <div class="form-group">
            <div class="phrase">
                <label for="password-confirm">Confirm Password</label>
                <p class="mandatory"> * </p>
            </div>
            <input id="password-confirm" type="password" name="password_confirmation" required>
        </div>

        <div class="button-group">
            <button type="submit">
                Change Password
            </button>
        </div>
    </form>
</div>
@endsection