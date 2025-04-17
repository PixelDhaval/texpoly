<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount('permissions')->get();
        return view('users.index', compact('users'));
    }

    public function show(User $user)
    {
        $userPermissions = $user->permissions;
        $availablePermissions = Permission::whereNotIn('id', $userPermissions->pluck('id'))->get();
        
        return view('users.show', compact('user', 'userPermissions', 'availablePermissions'));
    }

    public function addPermission(Request $request, User $user)
    {
        $validated = $request->validate([
            'permission_id' => 'required|exists:permissions,id'
        ]);

        $user->permissions()->attach($validated['permission_id']);
        
        return redirect()->back()->with('success', 'Permission added successfully');
    }

    public function removePermission(User $user, Permission $permission)
    {
        $user->permissions()->detach($permission->id);
        return redirect()->back()->with('success', 'Permission removed successfully');
    }
}
