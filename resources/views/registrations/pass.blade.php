@extends('layouts.app')
@section('title', 'Entry pass')
@section('content')
<div class="panel pass">
    <h1>{{ $registration->event->name }}</h1>
    <p class="muted">{{ $registration->event->chapter->name }} · {{ $registration->event->venue }}<br>
        {{ $registration->event->starts_at->format('M j, Y g:i A') }}</p>
    <div role="img" aria-label="QR code for ticket {{ $registration->ticket_code }}">{!! $qrSvg !!}</div>
    <p><strong>{{ $registration->user->name }}</strong><br>{{ $registration->user->company }}</p>
    <p><strong>{{ $registration->ticket_code }}</strong></p>
    <p class="noprint"><button class="btn" type="button" onclick="window.print()">Print or save as PDF</button></p>
</div>
@endsection
