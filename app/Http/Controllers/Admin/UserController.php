<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const PER_PAGE = [10, 25, 50];
    private const ROLES    = ['user' => 'User', 'admin' => 'Administrator'];

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE, true) ? (int) $request->query('per_page') : self::PER_PAGE[0];

        $users = User::query()
            ->select('users.*')
            ->selectSub(Report::query()->selectRaw('count(*)')->whereColumn('reporter_id', 'users.id'), 'reports_count')
            ->when($filters['q'], function ($q, $term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('department', 'like', $like));
            })
            ->when($filters['role'], fn ($q, $v) => $q->where('role', $v))
            ->when($filters['status'] === 'active', fn ($q) => $q->where(fn ($w) => $w->where('is_active', true)->orWhereNull('is_active')))
            ->when($filters['status'] === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByRaw("case when role = 'admin' then 0 else 1 end")
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users.index', [
            'users'      => $users,
            'filters'    => $filters,
            'perPage'    => $perPage,
            'perPageOptions' => self::PER_PAGE,
            'hasFilters' => collect($filters)->filter()->isNotEmpty(),
            'stats'      => [
                'total'  => User::count(),
                'admins' => User::where('role', 'admin')->count(),
                'active' => User::where(fn ($w) => $w->where('is_active', true)->orWhereNull('is_active'))->count(),
                'recent' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
            ],
            'options'    => [
                'role'   => ['' => 'All roles'] + self::ROLES,
                'status' => ['' => 'All statuses', 'active' => 'Active', 'inactive' => 'Inactive'],
                'roles'  => self::ROLES,
            ],
            'domains'    => config('mcc.allowed_domains'),
            'openModal'  => old('_modal'),
        ]);
    }

    /** Pre-register an MCC account so it has the right role on its first Microsoft sign-in. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:120'],
            'email'      => ['required', 'email', 'max:150', Rule::unique('users', 'email'),
                function ($attribute, $value, $fail) {
                    if (! in_array(Str::after(strtolower($value), '@'), config('mcc.allowed_domains'), true)) {
                        $fail('Use an official MCC address (@' . implode(', @', config('mcc.allowed_domains')) . ').');
                    }
                }],
            'role'       => ['required', Rule::in(array_keys(self::ROLES))],
            'department' => ['nullable', 'string', 'max:80'],
        ]);

        $user = new User();
        $user->name       = $data['name'];
        $user->email      = strtolower($data['email']);
        $user->role       = $data['role'];
        $user->department = $data['department'] ?? null;
        $user->password   = Hash::make(Str::random(40)); // never used: sign-in is Microsoft only
        $user->save();

        ActivityLog::record('user.created', "Added {$user->email} as {$data['role']}", $user);

        return redirect()->route('admin.users.index')->with('status', "{$user->name} can now sign in with their Microsoft account.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role'       => ['required', Rule::in(array_keys(self::ROLES))],
            'department' => ['nullable', 'string', 'max:80'],
        ]);

        if ($data['role'] !== $user->role && ($problem = $this->roleChangeProblem($user))) {
            return back()->with('error', $problem);
        }

        $from = $user->role;
        $user->role       = $data['role'];
        $user->department = $data['department'] ?? null;
        $user->save();

        if ($from !== $user->role) {
            ActivityLog::record('user.role_changed', "Changed {$user->email} from {$from} to {$user->role}", $user);
        }

        return back()->with('status', "{$user->name} updated.");
    }

    public function toggle(User $user)
    {
        $isActive = $user->is_active ?? true;

        if ($isActive && ($problem = $this->deactivationProblem($user))) {
            return back()->with('error', $problem);
        }

        $user->is_active = ! $isActive;
        $user->save();

        ActivityLog::record('user.' . ($user->is_active ? 'activated' : 'deactivated'), ($user->is_active ? 'Activated ' : 'Deactivated ') . $user->email, $user);

        return back()->with('status', $user->is_active ? "{$user->name} can sign in again." : "{$user->name} was deactivated and can no longer sign in.");
    }

    private function roleChangeProblem(User $user): ?string
    {
        if ($user->is(auth()->user())) {
            return 'You cannot change your own role.';
        }

        return ($user->role === 'admin' && $this->activeAdmins() <= 1) ? 'At least one active administrator is required.' : null;
    }

    private function deactivationProblem(User $user): ?string
    {
        if ($user->is(auth()->user())) {
            return 'You cannot deactivate your own account.';
        }

        return ($user->role === 'admin' && $this->activeAdmins() <= 1) ? 'At least one active administrator is required.' : null;
    }

    private function activeAdmins(): int
    {
        return User::where('role', 'admin')->where(fn ($w) => $w->where('is_active', true)->orWhereNull('is_active'))->count();
    }

    private function filters(Request $request): array
    {
        $in = fn (string $key, array $allowed) => in_array($request->query($key), $allowed, true) ? $request->query($key) : null;

        return [
            'q'      => trim((string) $request->query('q', '')) ?: null,
            'role'   => $in('role', array_keys(self::ROLES)),
            'status' => $in('status', ['active', 'inactive']),
        ];
    }
}
