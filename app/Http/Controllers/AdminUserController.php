<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** FR-03: administrators create staff accounts and assign roles. */
class AdminUserController extends Controller
{
    public function index()
    {
        Gate::authorize('administer');

        return view('admin.users', [
            'users' => User::orderBy('name')->paginate(25),
            'roles' => Role::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('administer');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        User::create($data);

        return back()->with('status', 'Account created.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('administer');

        $data = $request->validate(['role' => ['required', Rule::enum(Role::class)]]);

        if ($user->is($request->user()) && $data['role'] !== Role::Admin->value) {
            return back()->withErrors(['role' => 'You cannot remove your own administrator role.']);
        }

        $user->update($data);

        return back()->with('status', 'Role updated.');
    }
}
