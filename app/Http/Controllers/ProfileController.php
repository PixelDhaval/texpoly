<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\Permission;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Display the user's permissions.
     */
    public function permissions(Request $request)
    {
        $user = $request->user();
        $userPermissions = $user->permissions()->pluck('id')->toArray();
        $allPermissions = Permission::orderBy('group')->get();
        $unassignedPermissions = Permission::whereNotIn('id', $userPermissions)->get();
        
        return view('profile.permissions', compact('user', 'allPermissions', 'unassignedPermissions'));
    }

    /**
     * Add a permission to the user.
     */
    public function addPermission(Request $request)
    {
        $validated = $request->validate([
            'permission_id' => 'required|exists:permissions,id'
        ]);

        $request->user()->permissions()->attach($validated['permission_id']);
        
        return response()->json(['success' => true]);
    }

    /**
     * Remove a permission from the user.
     */
    public function removePermission(Request $request, Permission $permission)
    {
        $request->user()->permissions()->detach($permission->id);
        return response()->json(['success' => true]);
    }
}
