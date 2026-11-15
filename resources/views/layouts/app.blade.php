<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GTAV-M') · Global Trade Association Events</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="topbar">
    <a class="brand" href="{{ route('dashboard') }}">GTAV-M</a>
    @auth
        <nav aria-label="Main">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('events.index') }}">Events</a>
            @if(auth()->user()->isAttendee())
                <a href="{{ route('registrations.mine') }}">My registrations</a>
            @endif
            @can('use-scanner')
                <a href="{{ route('checkin.index') }}">Check-in</a>
            @endcan
            @can('administer')
                <a href="{{ route('chapters.index') }}">Chapters</a>
                <a href="{{ route('admin.users') }}">Users</a>
            @endcan
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="logout">
            @csrf
            <span class="who">{{ auth()->user()->name }} · {{ auth()->user()->role->label() }}</span>
            <button type="submit" class="btn small">Log out</button>
        </form>
    @endauth
</header>
<main id="main" class="container">
    @if(session('status'))
        <div class="alert ok" role="status">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert err" role="alert">
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>
