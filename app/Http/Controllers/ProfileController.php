<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ImageCompressor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Show the user profile page.
     */
    public function edit()
    {
        $user = auth()->user()->load('shop');
        return view('profile.edit', compact('user'));
    }

    /**
     * Update user general profile info (name, email, phone).
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'   => 'required|string|max:150',
            'email'  => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone'  => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:1024', // max 1MB
        ]);

        $data = $request->only('name', 'email', 'phone');

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $data['avatar_path'] = ImageCompressor::compressAndStore($request->file('avatar'), 'avatars', 'public', 500, 85);
        }

        $user->update($data);

        return back()->with('success', 'Profile information updated successfully.');
    }

    /**
     * Update user account password.
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Your current password does not match our records.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password changed successfully.');
    }
}
