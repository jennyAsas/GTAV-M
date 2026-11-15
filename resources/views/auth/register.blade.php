@extends('layouts.app')
@section('title', 'Create account')
@section('content')
<div class="panel" style="max-width:520px;margin:30px auto">
    <h1>Create an account</h1>
    <form method="POST" action="{{ route('register') }}" class="stack">
        @csrf
        <label for="name">Full name</label>
        <input id="name" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name">
        <label for="company">Company</label>
        <input id="company" name="company" value="{{ old('company') }}" maxlength="160" autocomplete="organization">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
        <label for="role">I am joining as</label>
        <select id="role" name="role" required>
            <option value="attendee" @selected(old('role', 'attendee') === 'attendee')>Corporate attendee</option>
            <option value="speaker" @selected(old('role') === 'speaker')>Guest speaker</option>
        </select>
        <label for="password">Password (8+ characters, letters and numbers)</label>
        <input id="password" name="password" type="password" required autocomplete="new-password">
        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        <p><button class="btn" type="submit">Create account</button></p>
    </form>
    <p class="muted">Already registered? <a href="{{ route('login') }}">Log in</a>. Manager and gate-staff accounts are created by an administrator.</p>
</div>
@endsection
