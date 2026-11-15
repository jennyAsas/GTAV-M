@extends('layouts.app')
@section('title', 'Events')
@section('content')
<h1>Upcoming events</h1>
@can('create', \App\Models\Event::class)
    <p><a class="btn" href="{{ route('events.create') }}">Create event</a></p>
@endcan

@if($events->isEmpty())
    <div class="panel empty">No upcoming events right now.</div>
@else
    <div class="grid">
        @foreach($events as $event)
            @php($left = max(0, $event->capacity - $event->taken_count))
            <article class="card">
                <span class="badge">{{ \App\Models\Event::TYPES[$event->type] ?? $event->type }}</span>
                <h2 style="margin-top:.4em"><a href="{{ route('events.show', $event) }}">{{ $event->name }}</a></h2>
                <div class="muted">{{ $event->chapter->name }} · {{ $event->venue }}</div>
                <div>{{ $event->starts_at->format('M j, Y g:i A') }}</div>
                <div>{{ $event->isFree() ? 'Free' : '₱'.number_format($event->ticket_price, 2) }} · {{ $left }} seat{{ $left === 1 ? '' : 's' }} left</div>
                @if(isset($mine[$event->id]))
                    <p><span class="badge {{ $mine[$event->id] }}">You: {{ $mine[$event->id] }}</span></p>
                @endif
            </article>
        @endforeach
    </div>
    {{ $events->links('pagination::default') }}
@endif
@endsection
