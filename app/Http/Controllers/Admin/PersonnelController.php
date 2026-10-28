<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PersonnelAvailability;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Technician;
use App\Models\WorkOrder;
use App\Models\WorkOrderUpdate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonnelController extends Controller
{
    private const PER_PAGE = [10, 25, 50];

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE, true) ? (int) $request->query('per_page') : self::PER_PAGE[0];

        $people = Technician::query()->withActiveOrders()->filter($filters)->orderBy('staff_id')->paginate($perPage)->withQueryString();

        $active = Technician::where('is_active', true);

        // Profile dialog (opened by ?profile=ID, e.g. from the work order page)
        $profile = null;
        if ($id = $request->integer('profile')) {
            $profile = Technician::withActiveOrders()->find($id);
        }

        return view('admin.personnel.index', [
            'people'    => $people,
            'filters'   => $filters,
            'perPage'   => $perPage,
            'perPageOptions' => self::PER_PAGE,
            'total'     => Technician::count(),
            'stats'     => [
                'active'         => (clone $active)->count(),
                'specialties'    => (clone $active)->distinct()->count('specialty'),
                'available'      => Technician::whereAvailability('available')->count(),
                'active_orders'  => WorkOrder::whereIn('status', Technician::OPEN_STATUSES)->count(),
                'busy_threshold' => (int) config('mcc.busy_threshold', 7),
            ],
            'hasFilters' => collect($filters)->filter()->isNotEmpty(),
            'options'   => [
                'specialty'    => ['' => 'All specializations'] + Technician::orderBy('specialty')->distinct()->pluck('specialty', 'specialty')->all(),
                'availability' => ['' => 'All availability'] + collect(PersonnelAvailability::cases())->mapWithKeys(fn ($a) => [$a->value => $a->label()])->all(),
                'account'      => ['' => 'All accounts', 'active' => 'Active', 'inactive' => 'Inactive'],
            ],
            'profile'     => $profile,
            'profileData' => $profile ? $this->profileData($profile) : null,
            'openModal'   => old('_modal'),
        ]);
    }

    /** Linked from the work order page ("View personnel profile"). */
    public function show(Technician $technician)
    {
        return redirect()->route('admin.personnel.index', ['profile' => $technician->id]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $next = (int) Technician::all()->map(fn ($t) => (int) preg_replace('/\D/', '', $t->staff_id))->max() + 1;
        $technician = Technician::create($data + ['staff_id' => sprintf('MCC-M%03d', $next)]);

        ActivityLog::record('personnel.created', "Added maintenance personnel {$technician->name} ({$technician->staff_id})", $technician);

        return redirect()->route('admin.personnel.index')->with('status', "{$technician->name} added as {$technician->staff_id}.");
    }

    public function update(Request $request, Technician $technician)
    {
        $data = $this->validated($request, $technician);

        if (! $data['is_active'] && $technician->is_active && $this->openOrders($technician) > 0) {
            return back()->withInput()->with('error', 'Reassign this person\'s open work orders before deactivating the account.');
        }

        $technician->update($data);
        ActivityLog::record('personnel.updated', "Updated maintenance personnel {$technician->name}", $technician);

        return back()->with('status', 'Personnel details saved.');
    }

    /** Quick switches from the row menu: account (active/inactive) and availability. */
    public function toggle(Technician $technician, string $field)
    {
        abort_unless(in_array($field, ['active', 'available'], true), 404);

        if ($field === 'active') {
            if ($technician->is_active && $this->openOrders($technician) > 0) {
                return back()->with('error', 'Reassign this person\'s open work orders before deactivating the account.');
            }
            $technician->update(['is_active' => ! $technician->is_active]);
            $message = $technician->is_active ? 'Account activated.' : 'Account deactivated.';
        } else {
            $technician->update(['is_available' => ! $technician->is_available]);
            $message = $technician->is_available ? 'Marked as available.' : 'Marked as unavailable.';
        }

        ActivityLog::record('personnel.' . $field . '_toggled', "{$message} ({$technician->name})", $technician);

        return back()->with('status', $message);
    }

    private function validated(Request $request, ?Technician $technician = null): array
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'email'          => ['required', 'email', 'max:150', Rule::unique('technicians', 'email')->ignore($technician)],
            'phone'          => ['nullable', 'string', 'max:30'],
            'extension'      => ['nullable', 'string', 'max:20'],
            'specialty'      => ['required', 'string', 'max:60'],
            'specialization' => ['nullable', 'string', 'max:150'],
        ]);

        // Checkboxes are absent from the request when unticked
        return $data + [
            'is_active'    => $request->boolean('is_active'),
            'is_available' => $request->boolean('is_available'),
        ];
    }

    private function openOrders(Technician $technician): int
    {
        return $technician->workOrders()->whereIn('status', Technician::OPEN_STATUSES)->count();
    }

    /** Numbers and lists shown in the profile dialog. */
    private function profileData(Technician $t): array
    {
        $orders = $t->workOrders();

        $completed = (clone $orders)->where('status', 'completed')->count();
        $onTime    = (clone $orders)->where('status', 'completed')->whereColumn('completed_at', '<=', 'target_at')->count();

        return [
            'active'    => $t->active_orders_count ?? $this->openOrders($t),
            'completed' => $completed,
            'on_time'   => $completed ? (int) round($onTime / $completed * 100) : null,
            'orders'    => (clone $orders)->with('report.location.building')
                ->orderByRaw("case when status in ('assigned','accepted','in_progress') then 0 else 1 end")
                ->latest()->limit(5)->get(),
            'activity'  => WorkOrderUpdate::with('workOrder')
                ->whereIn('work_order_id', (clone $orders)->select('id'))->latest()->latest('id')->limit(3)->get(),
        ];
    }

    private function filters(Request $request): array
    {
        $in = fn (string $key, array $allowed) => in_array($request->query($key), $allowed, true) ? $request->query($key) : null;

        return [
            'q'            => trim((string) $request->query('q', '')) ?: null,
            'specialty'    => ($s = trim((string) $request->query('specialty', ''))) !== '' ? $s : null,
            'availability' => $in('availability', array_column(PersonnelAvailability::cases(), 'value')),
            'account'      => $in('account', ['active', 'inactive']),
        ];
    }
}
