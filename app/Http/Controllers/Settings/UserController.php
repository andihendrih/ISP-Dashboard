<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengaturan → Pengguna (Staff & customer accounts).
 *
 * Hanya superadmin/admin yang bisa CRUD user staff.
 * Akun customer (role=customer) read-only di sini — dibuat otomatis dari
 * CustomerController saat pelanggan baru didaftarkan.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $q = User::query()->with(['role', 'customerProfile'])->orderBy('name');

        if ($s = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $roleFilter = $request->query('role');
        if ($roleFilter) {
            $q->whereHas('role', fn($w) => $w->where('name', $roleFilter));
        }

        $scope = $request->query('scope', 'staff'); // staff | customer | all
        if ($scope === 'staff') {
            $q->whereHas('role', fn($w) => $w->whereIn('name', Role::STAFF_ROLES));
        } elseif ($scope === 'customer') {
            $q->whereHas('role', fn($w) => $w->where('name', Role::CUSTOMER));
        }

        return view('settings.users.index', [
            'rows'       => $q->paginate(25)->withQueryString(),
            'roles'      => Role::orderBy('id')->get(),
            'scope'      => $scope,
            'roleFilter' => $roleFilter,
            'qs'         => $s,
            'stats'      => [
                'total'    => User::count(),
                'staff'    => User::whereHas('role', fn($w) => $w->whereIn('name', Role::STAFF_ROLES))->count(),
                'customer' => User::whereHas('role', fn($w) => $w->where('name', Role::CUSTOMER))->count(),
                'inactive' => User::where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('settings.users.create', [
            'roles' => Role::whereIn('name', Role::STAFF_ROLES)->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:120', 'unique:users,email'],
            'role_id'  => ['required', 'integer', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:6', 'max:64'],
            'is_active'=> ['sometimes', 'boolean'],
        ]);

        $role = Role::find($data['role_id']);
        if (!$role || !in_array($role->name, Role::STAFF_ROLES, true)) {
            return back()->with('error', 'Role tidak valid untuk staff.')->withInput();
        }

        $plain = $data['password'] ?: Str::password(10, true, true, false, false);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($plain),
            'role_id'   => $role->id,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return redirect()->route('settings.users.index')->with(
            'success',
            "User {$user->name} ({$role->label}) dibuat. Password: {$plain}"
        );
    }

    public function edit(User $user): View
    {
        $isCustomer = $user->role && $user->role->name === Role::CUSTOMER;
        return view('settings.users.edit', [
            'user'       => $user->load(['role', 'customerProfile']),
            'roles'      => Role::orderBy('id')->get(),
            'isCustomer' => $isCustomer,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_active'=> ['sometimes', 'boolean'],
        ]);

        $role = Role::find($data['role_id']);
        // Customer accounts: role tidak boleh diubah dari sini (otomatis dari customer).
        if ($user->role && $user->role->name === Role::CUSTOMER && $role && $role->name !== Role::CUSTOMER) {
            return back()->with('error', 'Role akun pelanggan tidak boleh diubah ke staff. Buat user staff baru.')->withInput();
        }

        // Cegah superadmin terakhir di-demote / di-nonaktifkan.
        if ($user->isSuperAdmin()) {
            $stillSuper = User::whereHas('role', fn($w) => $w->where('name', Role::SUPERADMIN))
                ->where('id', '!=', $user->id)
                ->where('is_active', true)
                ->exists();
            $newRoleIsSuper = $role && $role->name === Role::SUPERADMIN;
            $newActive = (bool) ($data['is_active'] ?? false);
            if (!$stillSuper && (!$newRoleIsSuper || !$newActive)) {
                return back()->with('error', 'Tidak bisa mengubah/menonaktifkan superadmin terakhir.')->withInput();
            }
        }

        $user->update([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'role_id'   => $role->id,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('settings.users.index')->with('success', "User {$user->name} di-update.");
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $plain = Str::password(10, true, true, false, false);
        $user->update(['password' => Hash::make($plain)]);
        return back()->with('success', "Password {$user->name} di-reset. Password baru: {$plain}");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        if ($user->isSuperAdmin() && $user->is_active) {
            $stillSuper = User::whereHas('role', fn($w) => $w->where('name', Role::SUPERADMIN))
                ->where('id', '!=', $user->id)
                ->where('is_active', true)
                ->exists();
            if (!$stillSuper) {
                return back()->with('error', 'Tidak bisa menonaktifkan superadmin terakhir.');
            }
        }
        $user->update(['is_active' => !$user->is_active]);
        $state = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "User {$user->name} {$state}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }
        if ($user->isSuperAdmin()) {
            $stillSuper = User::whereHas('role', fn($w) => $w->where('name', Role::SUPERADMIN))
                ->where('id', '!=', $user->id)
                ->where('is_active', true)
                ->exists();
            if (!$stillSuper) {
                return back()->with('error', 'Tidak bisa menghapus superadmin terakhir.');
            }
        }
        $name = $user->name;
        $user->delete();
        return back()->with('success', "User {$name} dihapus.");
    }
}
