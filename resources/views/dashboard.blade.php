@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<h1>Dashboard</h1>

@if($reports)
    <div class="grid">
        <div class="card"><div class="muted">Tickets registered</div><div class="stat">{{ number_format($totals['tickets']) }}</div></div>
        <div class="card"><div class="muted">Revenue (paid tickets)</div><div class="stat">₱{{ number_format($totals['revenue'], 2) }}</div></div>
        <div class="card"><div class="muted">Events</div><div class="stat">{{ $events->count() }}</div></div>
    </div>

    <h2>Chapter performance</h2>
    @if($chapters->isEmpty())
        <div class="panel empty">No events yet. <a href="{{ route('events.create') }}">Create the first event</a>.</div>
    @else
        <div class="table-wrap"><table>
            <thead><tr><th>Chapter</th><th>Events</th><th>Tickets</th><th>Paid</th><th>Revenue</th></tr></thead>
            <tbody>
            @foreach($chapters as $chapter)
                <tr><td>{{ $chapter['name'] }}</td><td>{{ $chapter['events'] }}</td><td>{{ $chapter['tickets'] }}</td><td>{{ $chapter['paid'] }}</td><td>₱{{ number_format($chapter['revenue'], 2) }}</td></tr>
            @endforeach
            </tbody>
        </table></div>
    @endif

    <h2>Events</h2>
    @if($events->isNotEmpty())
        <div class="table-wrap"><table>
            <thead><tr><th>Event</th><th>Chapter</th><th>Starts</th><th>Tickets</th><th>Seats left</th><th>Revenue</th></tr></thead>
            <tbody>
            @foreach($events as $event)
                <tr>
                    <td><a href="{{ route('events.show', $event) }}">{{ $event->name }}</a></td>
                    <td>{{ $event->chapter->name }}</td>
                    <td>{{ $event->starts_at->format('M j, Y g:i A') }}</td>
                    <td>{{ $event->taken_count }} / {{ $event->capacity }}</td>
                    <td>{{ max(0, $event->capacity - $event->taken_count) }}</td>
                    <td>₱{{ number_format($event->revenue, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
    <p><a class="btn" href="{{ route('events.create') }}">Create event</a></p>
@else
    @if($registrations->isNotEmpty())
        <h2>Your latest registrations</h2>
        <div class="grid">
            @foreach($registrations as $registration)
                <div class="card">
                    <strong>{{ $registration->event->name }}</strong>
                    <div class="muted">{{ $registration->event->starts_at->format('M j, Y g:i A') }}</div>
                    <span class="badge {{ $registration->payment_status->value }}">{{ ucfirst($registration->payment_status->value) }}</span>
                </div>
            @endforeach
        </div>
        <p><a href="{{ route('registrations.mine') }}">All registrations</a></p>
    @endif
    @if($assigned->isNotEmpty())
        <h2>Your assigned events</h2>
        <div class="grid">
            @foreach($assigned as $event)
                <div class="card"><a href="{{ route('events.show', $event) }}"><strong>{{ $event->name }}</strong></a>
                    <div class="muted">{{ $event->starts_at->format('M j, Y g:i A') }} · {{ $event->venue }}</div></div>
            @endforeach
        </div>
    @endif
    @if($registrations->isEmpty() && $assigned->isEmpty())
        <div class="panel empty">Nothing here yet. <a href="{{ route('events.index') }}">Browse upcoming events</a>.</div>
    @endif
@endif
@endsection
