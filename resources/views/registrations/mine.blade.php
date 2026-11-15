@extends('layouts.app')
@section('title', 'My registrations')
@section('content')
<h1>My registrations</h1>
@if($registrations->isEmpty())
    <div class="panel empty">You have not registered for any event. <a href="{{ route('events.index') }}">Browse events</a>.</div>
@else
    <div class="grid">
        @foreach($registrations as $registration)
            <article class="card">
                <h2 style="margin-top:0"><a href="{{ route('events.show', $registration->event) }}">{{ $registration->event->name }}</a></h2>
                <div class="muted">{{ $registration->event->chapter->name }} · {{ $registration->event->venue }}</div>
                <div>{{ $registration->event->starts_at->format('M j, Y g:i A') }}</div>
                <p><span class="badge {{ $registration->payment_status->value }}">{{ ucfirst($registration->payment_status->value) }}</span> · {{ $registration->ticket_code }}</p>
                @if($registration->isVerified())
                    <a class="btn" href="{{ route('registrations.pass', $registration) }}">Entry pass</a>
                @endif
            </article>
        @endforeach
    </div>
@endif
@endsection
