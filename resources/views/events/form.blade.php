@extends('layouts.app')
@php($editing = $event->exists)
@section('title', $editing ? 'Edit event' : 'Create event')
@section('content')
<h1>{{ $editing ? 'Edit event' : 'Create event' }}</h1>

@if($chapters->isEmpty())
    <div class="panel empty">You are not assigned to a chapter yet, so you cannot create events. Ask an administrator to assign you.</div>
@else
<form method="POST" action="{{ $editing ? route('events.update', $event) : route('events.store') }}" class="panel stack">
    @csrf
    @if($editing) @method('PUT') @endif
    <label for="name">Event name</label>
    <input id="name" name="name" value="{{ old('name', $event->name) }}" required maxlength="200">
    <div class="row">
        <div>
            <label for="chapter_id">Chapter</label>
            <select id="chapter_id" name="chapter_id" required>
                @foreach($chapters as $chapter)
                    <option value="{{ $chapter->id }}" @selected((int) old('chapter_id', $event->chapter_id) === $chapter->id)>{{ $chapter->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="type">Type</label>
            <select id="type" name="type" required>
                @foreach(\App\Models\Event::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $event->type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <label for="venue">Venue</label>
    <input id="venue" name="venue" value="{{ old('venue', $event->venue) }}" required maxlength="200">
    <div class="row">
        <div><label for="starts_at">Starts</label>
            <input id="starts_at" name="starts_at" type="datetime-local" required value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}"></div>
        <div><label for="ends_at">Ends</label>
            <input id="ends_at" name="ends_at" type="datetime-local" required value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}"></div>
        <div><label for="registration_cutoff">Registration closes</label>
            <input id="registration_cutoff" name="registration_cutoff" type="datetime-local" required value="{{ old('registration_cutoff', $event->registration_cutoff?->format('Y-m-d\TH:i')) }}"></div>
    </div>
    <div class="row">
        <div><label for="capacity">Capacity</label>
            <input id="capacity" name="capacity" type="number" min="1" max="100000" required value="{{ old('capacity', $event->capacity) }}"></div>
        <div><label for="ticket_price">Ticket price (PHP, 0 for free)</label>
            <input id="ticket_price" name="ticket_price" type="number" min="0" max="1000000" step="0.01" required value="{{ old('ticket_price', $event->ticket_price) }}"></div>
    </div>
    <label for="description">Description</label>
    <textarea id="description" name="description" rows="4" maxlength="5000">{{ old('description', $event->description) }}</textarea>
    <p><button class="btn" type="submit">{{ $editing ? 'Save changes' : 'Create event' }}</button></p>
</form>
@endif
@endsection
