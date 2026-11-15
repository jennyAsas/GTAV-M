@extends('layouts.app')
@section('title', 'Chapters')
@section('content')
<h1>Chapters</h1>

<form method="POST" action="{{ route('chapters.store') }}" class="panel stack">
    @csrf
    <h2 style="margin-top:0">New chapter</h2>
    <div class="row">
        <div><label for="new-name">Name</label><input id="new-name" name="name" required maxlength="160"></div>
        <div><label for="new-location">Location</label><input id="new-location" name="location" maxlength="160"></div>
        <div><label for="new-manager">Manager</label>
            <select id="new-manager" name="manager_id">
                <option value="">None yet</option>
                @foreach($managers as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
            </select></div>
    </div>
    <p><button class="btn" type="submit">Create chapter</button></p>
</form>

@if($chapters->isEmpty())
    <div class="panel empty">No chapters yet.</div>
@else
    @foreach($chapters as $chapter)
        <form method="POST" action="{{ route('chapters.update', $chapter) }}" class="panel stack">
            @csrf @method('PUT')
            <div class="row">
                <div><label for="name-{{ $chapter->id }}">Name</label><input id="name-{{ $chapter->id }}" name="name" value="{{ $chapter->name }}" required maxlength="160"></div>
                <div><label for="loc-{{ $chapter->id }}">Location</label><input id="loc-{{ $chapter->id }}" name="location" value="{{ $chapter->location }}" maxlength="160"></div>
                <div><label for="mgr-{{ $chapter->id }}">Manager</label>
                    <select id="mgr-{{ $chapter->id }}" name="manager_id">
                        <option value="">None</option>
                        @foreach($managers as $m)<option value="{{ $m->id }}" @selected($chapter->manager_id === $m->id)>{{ $m->name }}</option>@endforeach
                    </select></div>
            </div>
            <p><button class="btn line" type="submit">Save</button></p>
        </form>
    @endforeach
@endif
@if($managers->isEmpty())
    <p class="muted">No manager accounts exist yet. Create one under <a href="{{ route('admin.users') }}">Users</a>.</p>
@endif
@endsection
