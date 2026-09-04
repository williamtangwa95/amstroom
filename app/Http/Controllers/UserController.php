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
        $shopName = $auth->isShopAdmin() ? $auth->shop?->shop_name : null;

        return view('users.index', compact('shopName'));
    }

    public function data(Request $request)
    {
        $auth = Auth::user();
        $query = $this->scopedQuery();

        $recordsTotal = (clone $query)->count();

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->orWhere('name', 'like', "%{$searchValue}%")
                  ->orWhere('email', 'like', "%{$searchValue}%")
                  ->orWhere('phone', 'like', "%{$searchValue}%")
                  ->orWhere('role', 'like', "%{$searchValue}%")
                  ->orWhere('status', 'like', "%{$searchValue}%")
                  ->orWhereHas('shop', function ($sq) use ($searchValue) {
                      $sq->where('shop_name', 'like', "%{$searchValue}%");
                  });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [10, 25, 50, 100];
        $requestedLength = (int) $request->input('length', 10);
        $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

        $users = $query->orderBy('id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $isOwner = $auth->isOwner();

        $data = [];
        foreach ($users as $index => $u) {
            $iteration = $start + $index + 1;
            
            $nameHtml = '<span style="font-weight:600;font-size:.85rem;">' . e($u->name) . '</span>';
            $emailHtml = e($u->email);
            $phoneHtml = e($u->phone ?: '—');
            
            $roleLabel = str_replace('_', ' ', ucfirst($u->role));
            $roleHtml = '<span class="role-pill role-' . e($u->role) . '">' . e($roleLabel) . '</span>';

            $shopHtml = '';
            if ($isOwner) {
                if ($u->shop) {
                    $shopHtml = '<span style="background:rgba(88,166,255,.12);color:#58a6ff;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;"><i class="bi bi-shop me-1"></i>' . e($u->shop->shop_name) . '</span>';
                } else {
                    $shopHtml = '<span style="font-size:.75rem;color:var(--text-secondary);">Unassigned</span>';
                }
            }

            $statusClass = $u->status === 'active' ? 'badge-active' : 'badge-inactive';
            $statusLabel = $u->status === 'active' ? 'Active' : 'Disabled';
            $statusHtml = '<span class="status-badge ' . $statusClass . '"><span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;"></span> ' . e($statusLabel) . '</span>';

            $actions = '<div class="d-flex gap-1">
                <a href="' . route('users.show', $u) . '" class="btn btn-xs btn-outline-custom" title="View"><i class="bi bi-eye"></i></a>
                <a href="' . route('users.edit', $u) . '" class="btn btn-xs btn-outline-custom" title="Edit"><i class="bi bi-pencil"></i></a>';

            if (!$u->isOwner() && $u->id !== $auth->id) {
                $toggleConfirm = $u->status === 'active' ? 'Disable employee account?' : 'Enable employee account?';
                $toggleText = $u->status === 'active' ? 'They will not be able to log in or reset password.' : 'They will be able to log in and use the application.';
                $toggleTitle = $u->status === 'active' ? 'Disable' : 'Enable';
                $toggleIcon = $u->status === 'active' ? '<i class="bi bi-person-dash" style="color:var(--text-secondary);"></i>' : '<i class="bi bi-person-check" style="color:#3fb950;"></i>';

                $actions .= '<form method="POST" action="' . route('users.toggle-status', $u) . '" id="toggle-user-' . $u->id . '" class="d-inline">
                    ' . csrf_field() . method_field('PATCH') . '
                    <button type="button" class="btn btn-xs btn-outline-custom"
                        data-confirm="' . e($toggleConfirm) . '"
                        data-text="' . e($toggleText) . '"
                        data-form="toggle-user-' . $u->id . '"
                        title="' . e($toggleTitle) . '">
                        ' . $toggleIcon . '
                    </button>
                </form>';
            }

            if (!$u->hasDependencies()) {
                $actions .= '<form method="POST" action="' . route('users.destroy', $u) . '" id="del-user-' . $u->id . '" class="d-inline">
                    ' . csrf_field() . method_field('DELETE') . '
                    <button type="button" class="btn btn-xs btn-outline-custom"
                        data-confirm="Delete employee account?"
                        data-form="del-user-' . $u->id . '"
                        title="Delete">
                        <i class="bi bi-trash" style="color:#e94560;"></i>
                    </button>
                </form>';
            } else {
                $actions .= '<button type="button" class="btn btn-xs btn-outline-custom" disabled style="opacity:0.5; cursor:not-allowed;"
                    title="Cannot delete user with associated sales, stock requests, or other records.">
                    <i class="bi bi-trash" style="color:var(--text-secondary);"></i>
                </button>';
            }

            $actions .= '</div>';

            $row = [
                'no' => $iteration,
                'name' => $nameHtml,
                'email' => $emailHtml,
                'phone' => $phoneHtml,
                'role' => $roleHtml,
            ];

            if ($isOwner) {
                $row['shop'] = $shopHtml;
            }

            $row['status'] = $statusHtml;
            $row['actions'] = $actions;

            $data[] = $row;
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
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
            'status'  => 'required|in:active,inactive',
        ]);

        $data = $request->only('name', 'email', 'phone');
        $data['role']    = $auth->isShopAdmin() ? 'seller' : $request->role;
        $data['shop_id'] = $auth->isShopAdmin() ? $auth->shop_id : $request->shop_id;

        if ($auth->isOwner()) {
            $data['allow_stock_addition'] = $request->boolean('allow_stock_addition');
        }

        if ($user->id !== $auth->id) {
            $data['status'] = $request->status;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function toggleStatus(User $user)
    {
        $this->authorizeAccess($user);

        if ($user->isOwner()) {
            return back()->with('error', 'Cannot disable owner account.');
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Cannot change your own status.');
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        $message = $newStatus === 'active' ? 'Employee account has been enabled.' : 'Employee account has been disabled.';

        return back()->with('success', $message);
    }

    public function destroy(User $user)
    {
        $this->authorizeAccess($user);

        if ($user->isOwner()) {
            return back()->with('error', 'Cannot delete owner account.');
        }

        if ($user->hasDependencies()) {
            return back()->with('error', 'Cannot delete employee account because they have associated records (sales, stock actions, or other dependencies).');
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
