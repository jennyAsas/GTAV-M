@extends('layouts.app')
@section('title', $event->name)
@section('content')
@php($left = max(0, $event->capacity - $event->taken_count))
<h1>{{ $event->name }}</h1>
<div class="panel">
    <p><span class="badge">{{ \App\Models\Event::TYPES[$event->type] ?? $event->type }}</span> {{ $event->chapter->name }}</p>
    <p>{{ $event->starts_at->format('M j, Y g:i A') }} to {{ $event->ends_at->format('M j, Y g:i A') }} · {{ $event->venue }}</p>
    <p>{{ $event->isFree() ? 'Free' : '₱'.number_format($event->ticket_price, 2) }} · {{ $left }} of {{ $event->capacity }} seats left · registration closes {{ $event->registration_cutoff->format('M j, Y g:i A') }}</p>
    @if($event->description)<p>{!! nl2br(e($event->description)) !!}</p>@endif
    @if($event->speakers->isNotEmpty())
        <p><strong>Speakers:</strong> {{ $event->speakers->pluck('name')->join(', ') }}</p>
    @endif
</div>

@if(auth()->user()->isAttendee())
    <div class="panel">
        @if($registration)
            <p>Your registration: <span class="badge {{ $registration->payment_status->value }}">{{ ucfirst($registration->payment_status->value) }}</span>
               · ticket <strong>{{ $registration->ticket_code }}</strong></p>
            @if($registration->isVerified())
                <a class="btn" href="{{ route('registrations.pass', $registration) }}">Open entry pass</a>
            @elseif($registration->payment_status->value === 'pending')
                <p class="muted">Pay the event team, then wait for them to verify your payment. Your pass unlocks after that.</p>
            @else
                <p class="muted">This registration was rejected. Contact the event team.</p>
            @endif
        @elseif(! $event->isRegistrationOpen())
            <p>Registration for this event has closed.</p>
        @elseif($left < 1)
            <p>This event is full.</p>
        @else
            <form method="POST" action="{{ route('events.register', $event) }}">
                @csrf
                <button class="btn" type="submit">Register{{ $event->isFree() ? '' : ' (₱'.number_format($event->ticket_price, 2).')' }}</button>
            </form>
        @endif
    </div>
@endif

@if($canManage)
    <div class="panel">
        <h2 style="margin-top:0">Manage event</h2>
        <p>
            <a class="btn line" href="{{ route('events.edit', $event) }}">Edit</a>
            <a class="btn line" href="{{ route('events.registrations', $event) }}">Registrations and attendance</a>
            <a class="btn line" href="{{ route('checkin.show', $event) }}">Open scanner</a>
        </p>
        <form method="POST" action="{{ route('events.assign', $event) }}" class="stack">
            @csrf @method('PUT')
            <div class="row">
                <div>
                    <label for="speakers">Assigned speakers</label>
                    <select id="speakers" name="speakers[]" multiple size="5">
                        @foreach($speakerOptions as $u)
                            <option value="{{ $u->id }}" @selected($event->speakers->contains($u))>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="gate_staff">Assigned gate staff</label>
                    <select id="gate_staff" name="gate_staff[]" multiple size="5">
                        @foreach($gateOptions as $u)
                            <option value="{{ $u->id }}" @selected($event->gateStaff->contains($u))>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="muted">Hold Ctrl or Cmd to select several. Only accounts with the matching role are listed.</p>
            <button class="btn" type="submit">Save assignments</button>
        </form>
    </div>
@elseif($event->gateStaff->contains(auth()->user()))
    <p><a class="btn" href="{{ route('checkin.show', $event) }}">Open scanner</a></p>
@endif
@endsection
