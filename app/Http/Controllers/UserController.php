<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('shop')->where('role', '!=', 'owner')->latest()->paginate(15);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $shops = Shop::where('status', 'active')->get();
        return view('users.create', compact('shops'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'role'     => 'required|in:shop_admin,seller',
            'shop_id'  => 'nullable|exists:shops,id',
            'password' => 'required|min:6|confirmed',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'role'     => $request->role,
            'shop_id'  => $request->shop_id,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Employee registered successfully.');
    }

    public function show(User $user)
    {
        $user->load('shop', 'sales', 'defects');
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $shops = Shop::where('status', 'active')->get();
        return view('users.edit', compact('user', 'shops'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'    => 'required|string|max:150',
            'email'   => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone'   => 'nullable|string|max:20',
            'role'    => 'required|in:shop_admin,seller',
            'shop_id' => 'nullable|exists:shops,id',
            'password'=> 'nullable|min:6|confirmed',
        ]);

        $data = $request->only('name', 'email', 'phone', 'role', 'shop_id');
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->isOwner()) {
            return back()->with('error', 'Cannot delete owner account.');
        }
        $user->delete();
        return redirect()->route('users.index')
            ->with('success', 'Employee deleted successfully.');
    }
}
