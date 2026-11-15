@extends('layouts.app')
@section('title', 'Users')
@section('content')
<h1>Users and roles</h1>

<form method="POST" action="{{ route('admin.users.store') }}" class="panel stack">
    @csrf
    <h2 style="margin-top:0">Create an account</h2>
    <div class="row">
        <div><label for="u-name">Name</label><input id="u-name" name="name" value="{{ old('name') }}" required maxlength="120"></div>
        <div><label for="u-email">Email</label><input id="u-email" name="email" type="email" value="{{ old('email') }}" required></div>
        <div><label for="u-company">Company</label><input id="u-company" name="company" value="{{ old('company') }}" maxlength="160"></div>
    </div>
    <div class="row">
        <div><label for="u-role">Role</label>
            <select id="u-role" name="role" required>
                @foreach($roles as $role)<option value="{{ $role->value }}" @selected(old('role', 'gate_staff') === $role->value)>{{ $role->label() }}</option>@endforeach
            </select></div>
        <div><label for="u-password">Temporary password (8+ characters, letters and numbers)</label>
            <input id="u-password" name="password" type="password" required autocomplete="new-password"></div>
    </div>
    <p><button class="btn" type="submit">Create account</button></p>
</form>

<div class="table-wrap"><table>
    <thead><tr><th>Name</th><th>Email</th><th>Company</th><th>Role</th></tr></thead>
    <tbody>
    @foreach($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->company }}</td>
            <td>
                <form method="POST" action="{{ route('admin.users.role', $user) }}" style="display:flex;gap:6px">
                    @csrf @method('PUT')
                    <label class="skip" for="role-{{ $user->id }}">Role for {{ $user->name }}</label>
                    <select id="role-{{ $user->id }}" name="role">
                        @foreach($roles as $role)<option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>@endforeach
                    </select>
                    <button class="btn small" type="submit">Save</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table></div>
{{ $users->links('pagination::default') }}
@endsection
