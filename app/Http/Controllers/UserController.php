<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /** Resolve the user scope: owner sees all, shop_admin sees own shop's sellers only. */
    private function scopedQuery()
    {
        $auth = Auth::user();
        $query = User::with('shop')->where('role', '!=', 'owner');

        if ($auth->isShopAdmin()) {
            $query->where('shop_id', $auth->shop_id)->where('role', 'seller');
        }

        return $query;
    }

    public function index()
    {
        $auth  = Auth::user();
        $users = $this->scopedQuery()->latest()->get();
        $shopName = $auth->isShopAdmin() ? $auth->shop?->shop_name : null;

        return view('users.index', compact('users', 'shopName'));
    }

    public function create()
    {
        $auth  = Auth::user();

        // Owner: can pick any active shop and any role
        // Shop Admin: shop is locked to their own, role locked to seller
        if ($auth->isOwner()) {
            $shops = Shop::where('status', 'active')->get();
        } else {
            $shops = Shop::where('id', $auth->shop_id)->get();
        }

        return view('users.create', compact('shops'));
    }

    public function store(Request $request)
    {
        $auth = Auth::user();

        $roleAllowed = $auth->isOwner() ? ['shop_admin', 'seller'] : ['seller'];

        $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'role'     => ['required', Rule::in($roleAllowed)],
            'shop_id'  => 'nullable|exists:shops,id',
            'password' => 'required|min:6|confirmed',
            'allow_stock_addition' => 'nullable|boolean',
        ]);

        // Shop admin always assigns to their own shop
        $shopId = $auth->isShopAdmin() ? $auth->shop_id : $request->shop_id;

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'role'     => $auth->isShopAdmin() ? 'seller' : $request->role,
            'shop_id'  => $shopId,
            'password' => Hash::make($request->password),
            'allow_stock_addition' => $auth->isOwner() ? $request->boolean('allow_stock_addition') : false,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Employee registered successfully.');
    }

    public function show(User $user)
    {
        $this->authorizeAccess($user);
        $user->load('shop', 'sales', 'defects');
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorizeAccess($user);
        $auth  = Auth::user();

        if ($auth->isOwner()) {
            $shops = Shop::where('status', 'active')->get();
        } else {
            $shops = Shop::where('id', $auth->shop_id)->get();
        }

        return view('users.edit', compact('user', 'shops'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAccess($user);
        $auth = Auth::user();

        $roleAllowed = $auth->isOwner() ? ['shop_admin', 'seller'] : ['seller'];

        $request->validate([
            'name'    => 'required|string|max:150',
            'email'   => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone'   => 'nullable|string|max:20',
            'role'    => ['required', Rule::in($roleAllowed)],
            'shop_id' => 'nullable|exists:shops,id',
            'password'=> 'nullable|min:6|confirmed',
            'allow_stock_addition' => 'nullable|boolean',
        ]);

        $data = $request->only('name', 'email', 'phone');
        $data['role']    = $auth->isShopAdmin() ? 'seller' : $request->role;
        $data['shop_id'] = $auth->isShopAdmin() ? $auth->shop_id : $request->shop_id;

        if ($auth->isOwner()) {
            $data['allow_stock_addition'] = $request->boolean('allow_stock_addition');
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorizeAccess($user);

        if ($user->isOwner()) {
            return back()->with('error', 'Cannot delete owner account.');
        }

        $user->delete();
        return redirect()->route('users.index')
            ->with('success', 'Employee deleted successfully.');
    }

    /**
     * Abort 403 if the authenticated shop_admin tries to access a user outside their shop
     * or a non-seller user. Owners can access anything.
     */
    private function authorizeAccess(User $user): void
    {
        $auth = Auth::user();
        if ($auth->isOwner()) return;

        if ($auth->isShopAdmin()) {
            if ($user->shop_id !== $auth->shop_id || $user->role !== 'seller') {
                abort(403, 'You can only manage sellers in your own shop.');
            }
        }
    }
}
