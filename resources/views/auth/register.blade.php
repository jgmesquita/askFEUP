@extends('layouts.app')

@section('content')
<div class="auth-container">
    
    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="register-form">
        @csrf
        <h2>Register</h2>
        <div class="form-group">
            <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="John Doe" required autofocus>
            <label for="name">Name<span class="mandatory">*</span></label>
        </div>

        <div class="form-group">
            <input id="tagname" type="text" name="tagname" value="{{ old('tagname') }}" placeholder="john.doe" required>
            <label for="tagname">Tagname<span class="mandatory">*</span></label>
        </div>

        <div class="form-group">
            <input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="jdoe@email.com">
            <label for="email">E-Mail Address<span class="mandatory">*</span></label>
        </div>

        <div class="form-container">
            <div class="helper-form-group">
                <div class="form-group">
                    <input id="password" type="password" name="password" required placeholder="">
                    <label for="password">Password<span class="mandatory">*</span></label>
                </div>
                <span class="material-symbols-outlined help-icon" data-tooltip="Your password must be at least 8 characters">help</span>
            </div>
            <div class="form-group">
                <input id="password-confirm" type="password" name="password_confirmation" required placeholder="">
                <label for="password-confirm">Confirm Password<span class="mandatory"> * </span></label>
            </div>
        </div>

        <div class="form-container">
            <div class="helper-form-group">
                <div class="form-group">
                    <input id="age" type="number" name="age" value="{{ old('age') }}" min="18" required placeholder="18">
                    <label for="age">Age<span class="mandatory">*</span></label>
                </div>
                <span class="material-symbols-outlined help-icon" data-tooltip="Age must be greater than 18">help</span>
            </div>
            <div class="form-group">
                <input id="country" type="text" name="country" value="{{ old('country') }}" placeholder="Portugal">
                <label for="country">Country</label>
            </div>
        </div>

        <div class="form-group">
            <input id="degree" type="text" name="degree" value="{{ old('degree') }}" placeholder="Bachelor">
            <label for="degree">Degree</label>
        </div>

        <div class="button-group">
            <button type="submit" class="button">
                Register
            </button>
            <a class="button button-outline" href="{{ route('login') }}">Login</a>
        </div>
    </form>
</div>
@endsection