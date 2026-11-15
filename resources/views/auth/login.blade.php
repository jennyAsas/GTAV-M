@extends('layouts.app')
@section('title', 'Log in')
@section('content')
<div class="panel" style="max-width:420px;margin:30px auto">
    <h1>Log in</h1>
    <form method="POST" action="{{ route('login') }}" class="stack">
        @csrf
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
        <p><label style="font-weight:400"><input type="checkbox" name="remember" value="1" style="width:auto"> Keep me signed in</label></p>
        <button class="btn" type="submit">Log in</button>
    </form>
    <p class="muted">New attendee or speaker? <a href="{{ route('register') }}">Create an account</a>.</p>
</div>
@endsection
