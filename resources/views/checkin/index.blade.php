@extends('layouts.app')
@section('title', 'Check-in')
@section('content')
<h1>Choose the event you are working at</h1>
@if($events->isEmpty())
    <div class="panel empty">You have no current events to scan. Gate staff must be assigned to an event by a manager.</div>
@else
    <div class="grid">
        @foreach($events as $event)
            <a class="card" href="{{ route('checkin.show', $event) }}" style="text-decoration:none;color:inherit">
                <strong>{{ $event->name }}</strong>
                <div class="muted">{{ $event->chapter->name }} · {{ $event->venue }}</div>
                <div>{{ $event->starts_at->format('M j, Y g:i A') }}</div>
            </a>
        @endforeach
    </div>
@endif
@endsection
