@extends('layouts.app')
@section('title', 'Registrations')
@section('content')
<h1>{{ $event->name }}: registrations</h1>
<p><a href="{{ route('events.show', $event) }}">Back to event</a></p>

@if($registrations->isEmpty())
    <div class="panel empty">Nobody has registered yet.</div>
@else
    <div class="table-wrap"><table>
        <thead><tr><th>Attendee</th><th>Company</th><th>Ticket</th><th>Payment</th><th>Checked in</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($registrations as $registration)
            <tr>
                <td>{{ $registration->user->name }}<div class="muted">{{ $registration->user->email }}</div></td>
                <td>{{ $registration->user->company }}</td>
                <td>{{ $registration->ticket_code }}</td>
                <td><span class="badge {{ $registration->payment_status->value }}">{{ ucfirst($registration->payment_status->value) }}</span></td>
                <td>{{ $registration->attendance?->checked_in_at->format('M j, g:i A') ?? 'No' }}</td>
                <td>
                    @if($registration->payment_status->value !== 'paid')
                        <form class="inline" method="POST" action="{{ route('registrations.paid', $registration) }}">@csrf
                            <button class="btn small ok" style="color:#fff" type="submit">Mark paid</button></form>
                    @endif
                    @if($registration->payment_status->value !== 'rejected' && ! $registration->attendance)
                        <form class="inline" method="POST" action="{{ route('registrations.reject', $registration) }}">@csrf
                            <button class="btn small danger" style="color:#fff" type="submit">Reject</button></form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    {{ $registrations->links('pagination::default') }}
@endif
@endsection
