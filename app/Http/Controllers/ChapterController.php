<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** FR-04, FR-05 */
class ChapterController extends Controller
{
    public function index()
    {
        Gate::authorize('manage', Chapter::class);

        return view('chapters.index', [
            'chapters' => Chapter::with('manager')->orderBy('name')->get(),
            'managers' => User::where('role', Role::Manager->value)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage', Chapter::class);

        Chapter::create($this->validated($request));

        return back()->with('status', 'Chapter created.');
    }

    public function update(Request $request, Chapter $chapter): RedirectResponse
    {
        Gate::authorize('manage', Chapter::class);

        $chapter->update($this->validated($request, $chapter));

        return back()->with('status', 'Chapter updated.');
    }

    private function validated(Request $request, ?Chapter $chapter = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160', Rule::unique('chapters', 'name')->ignore($chapter?->id)],
            'location' => ['nullable', 'string', 'max:160'],
            'manager_id' => ['nullable', Rule::exists('users', 'id')->where('role', Role::Manager->value)],
        ]);
    }
}
