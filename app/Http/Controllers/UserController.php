<?php

namespace App\Http\Controllers;

use App\Enums\PermissionNameEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize(PermissionNameEnum::員工設定);

        $attributes = $request->validate([
            'page'      => 'nullable|integer',
            'per_page'  => 'nullable|max:100|integer|multiple_of:5',
        ]);

        return Inertia::render('User/Index', [
            'users' => User::paginate($attributes['per_page'] ?? null),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        Gate::authorize(PermissionNameEnum::員工設定);

        return Inertia::render('User/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize(PermissionNameEnum::員工設定);

        $attributes = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::transaction(function () use ($attributes) {
            $user = User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => bcrypt($attributes['password']),
            ]);
        });

        return redirect()->route('users.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::員工設定);

        return Inertia::render('User/Edit', [
            'user' => User::findOrFail($id),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::員工設定);

        $attributes = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class . ',email,' . $id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::transaction(function () use ($attributes, $id) {
            $user = User::findOrFail($id);

            $user->update([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'] ? bcrypt($attributes['password']) : $user->password,
            ]);
        });

        return redirect()->route('users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::員工設定);

        DB::transaction(function () use ($id) {
            User::findOrFail($id)->delete();
        });

        return redirect()->route('users.index');
    }
}
