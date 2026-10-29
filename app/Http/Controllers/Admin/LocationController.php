<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Location;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    private const PER_PAGE = [10, 25, 50];

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE, true) ? (int) $request->query('per_page') : self::PER_PAGE[0];

        $buildings = Building::query()
            ->select('buildings.*')
            ->withCount('locations')
            ->selectRaw('(select count(*) from reports where reports.deleted_at is null and reports.location_id in (select id from locations where locations.building_id = buildings.id)) as reports_count')
            ->with('locations:id,building_id,name')
            ->when($filters['q'], function ($q, $term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('code', 'like', $like));
            })
            ->when($filters['status'] === 'active', fn ($q) => $q->where(fn ($w) => $w->where('is_active', true)->orWhereNull('is_active')))
            ->when($filters['status'] === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.locations.index', [
            'buildings'  => $buildings,
            'filters'    => $filters,
            'perPage'    => $perPage,
            'perPageOptions' => self::PER_PAGE,
            'hasFilters' => collect($filters)->filter()->isNotEmpty(),
            'totals'     => ['buildings' => Building::count(), 'rooms' => Location::count()],
            'options'    => ['status' => ['' => 'All location statuses', 'active' => 'Active', 'inactive' => 'Inactive']],
            'openModal'  => old('_modal'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $building = DB::transaction(function () use ($data) {
            $building = Building::create(['name' => $data['name'], 'code' => $data['code'], 'is_active' => $data['is_active']]);
            $this->syncRooms($building, $data['rooms']);

            return $building;
        });

        ActivityLog::record('location.created', "Added building {$building->name} ({$building->code})", $building);

        return redirect()->route('admin.locations.index')->with('status', "{$building->name} added with {$building->locations()->count()} rooms.");
    }

    public function update(Request $request, Building $building)
    {
        $data = $this->validated($request, $building);

        $kept = DB::transaction(function () use ($building, $data) {
            $building->update(['name' => $data['name'], 'code' => $data['code'], 'is_active' => $data['is_active']]);

            return $this->syncRooms($building, $data['rooms']);
        });

        ActivityLog::record('location.updated', "Updated building {$building->name} ({$building->code})", $building);

        $message = "{$building->name} saved.";
        if ($kept->isNotEmpty()) {
            $message .= ' These rooms were kept because reports refer to them: ' . $kept->implode(', ') . '.';
        }

        return back()->with('status', $message);
    }

    /** Deactivate / reactivate. Reports and work orders keep their original location either way. */
    public function toggle(Building $building)
    {
        $building->update(['is_active' => ! ($building->is_active ?? true)]);

        $state = $building->is_active ? 'reactivated' : 'deactivated';
        ActivityLog::record('location.' . $state, ucfirst($state) . " building {$building->name}", $building);

        return back()->with('status', "{$building->name} {$state}." . ($building->is_active ? '' : ' It is hidden from new report forms.'));
    }

    private function validated(Request $request, ?Building $building = null): array
    {
        $request->merge(['code' => Str::upper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:120', Rule::unique('buildings', 'name')->ignore($building)],
            'code'  => ['required', 'string', 'max:12', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('buildings', 'code')->ignore($building)],
            'rooms' => ['nullable', 'string', 'max:6000'],
        ], ['code.regex' => 'Use letters, numbers, dashes or underscores only.']);

        // Room names: one per line, unique, at most 60 characters each
        $rooms = $this->parseRooms($data['rooms'] ?? '');
        if ($rooms->contains(fn ($r) => mb_strlen($r) > 60)) {
            abort(back()->withInput()->withErrors(['rooms' => 'Each room name must be 60 characters or fewer.']));
        }

        return [
            'name'      => $data['name'],
            'code'      => $data['code'],
            'rooms'     => $rooms,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function parseRooms(string $text): Collection
    {
        return collect(preg_split('/\R/', $text))->map(fn ($l) => trim($l))->filter()->unique()->values();
    }

    /**
     * Adds missing rooms and removes rooms that were deleted from the list, except rooms that reports use.
     * Returns the names that had to be kept.
     */
    private function syncRooms(Building $building, Collection $names): Collection
    {
        $existing = $building->locations()->get();

        foreach ($names as $name) {
            if (! $existing->contains('name', $name)) {
                $building->locations()->create(['name' => $name]);
            }
        }

        $kept = collect();
        foreach ($existing->whereNotIn('name', $names->all()) as $room) {
            if (Report::withTrashed()->where("location_id", $room->id)->exists()) {
                $kept->push($room->name);
            } else {
                $room->delete();
            }
        }

        return $kept;
    }

    private function filters(Request $request): array
    {
        return [
            'q'      => trim((string) $request->query('q', '')) ?: null,
            'status' => in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : null,
        ];
    }
}
