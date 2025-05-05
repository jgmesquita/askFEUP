@extends('layouts.app')

@section('content')
<div class="auth-container">
    <form method="POST" action="{{ route('login') }}" class="login-form">
        @csrf
        <h2>Login</h2>

        <div class="form-group">
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="jdoe@email.com">
            <label for="email">E-mail Address<span class="mandatory">*</span></label>
        </div>        

        <div class="form-group">
            <input id="password" type="password" name="password" required placeholder="">
            <label for="password">Password<span class="mandatory">*</span></label>
        </div>

        <label class="checkbox-container">
            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <span class="custom-checkbox"></span>
            Remember Me
        </label>

        <div class="button-group">
            <button type="submit">
                Login
            </button>
            <a class="button button-outline" href="{{ route('register') }}">Register</a>      
        </div> 
    </form>
    <a class="reset-password" href="{{ route('show.reset-password') }}">Forgot your Password?</a>
</div>
@endsection