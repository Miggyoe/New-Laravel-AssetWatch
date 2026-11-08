<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Location;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    private const PER_PAGE = [10, 25, 50];

    /** Buildings. */
    public function index(Request $request)
    {
        $filters = ['q' => trim((string) $request->query('q', '')) ?: null, 'status' => in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : null];
        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE, true) ? (int) $request->query('per_page') : self::PER_PAGE[0];

        $buildings = Building::query()
            ->select('buildings.*')
            ->withCount('locations')
            ->selectRaw('(select count(*) from reports where reports.deleted_at is null and reports.location_id in (select id from locations where locations.building_id = buildings.id)) as reports_count')
            ->when($filters['q'], function ($q, $term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhereHas('locations', fn ($l) => $l->where('name', 'like', $like)->orWhere('code', 'like', $like)));
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
        $data = $request->validate(['name' => ['required', 'string', 'max:120', Rule::unique('buildings', 'name')]]);

        $building = Building::create(['name' => $data['name'], 'is_active' => $request->boolean('is_active')]);
        ActivityLog::record('location.created', "Added building {$building->name}", $building);

        return redirect()->route('admin.locations.show', $building)->with('status', "{$building->name} added. Add its rooms below.");
    }

    public function update(Request $request, Building $building)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', Rule::unique('buildings', 'name')->ignore($building)]]);

        $building->update(['name' => $data['name'], 'is_active' => $request->boolean('is_active')]);
        ActivityLog::record('location.updated', "Updated building {$building->name}", $building);

        return back()->with('status', "{$building->name} saved.");
    }

    /** Deactivate / reactivate. Reports and work orders keep their original location either way. */
    public function toggle(Building $building)
    {
        $building->update(['is_active' => ! ($building->is_active ?? true)]);

        $state = $building->is_active ? 'reactivated' : 'deactivated';
        ActivityLog::record('location.' . $state, ucfirst($state) . " building {$building->name}", $building);

        return back()->with('status', "{$building->name} {$state}." . ($building->is_active ? '' : ' It is hidden from new report forms.'));
    }

    /** Rooms of one building. */
    public function show(Request $request, Building $building)
    {
        $filters = [
            'q'     => trim((string) $request->query('q', '')) ?: null,
            'type'  => array_key_exists((string) $request->query('type'), Location::TYPES) ? (string) $request->query('type') : null,
            'floor' => trim((string) $request->query('floor', '')) ?: null,
        ];

        $all = $building->locations()->withCount('reports')->get();

        $rooms = $all
            ->when($filters['type'], fn ($c, $t) => $c->where('type', $t))
            ->when($filters['floor'], fn ($c, $f) => $c->where('floor', $f))
            ->when($filters['q'], fn ($c, $term) => $c->filter(fn ($r) => str_contains(mb_strtolower($r->code . ' ' . $r->name), mb_strtolower($term))))
            ->sort(fn ($a, $b) => strnatcasecmp(
                ($a->floor ?? '~') . ' ' . ($a->code ?? '~') . ' ' . $a->name,
                ($b->floor ?? '~') . ' ' . ($b->code ?? '~') . ' ' . $b->name
            ))
            ->values();

        $floors = $all->pluck('floor')->filter()->unique()->sort(SORT_NATURAL)->values();

        return view('admin.locations.show', [
            'building'  => $building,
            'rooms'     => $rooms,
            'floors'    => $floors,
            'filters'   => $filters,
            'hasFilters' => collect($filters)->filter()->isNotEmpty(),
            'totals'    => ['rooms' => $all->count(), 'floors' => $floors->count(), 'reports' => (int) $all->sum('reports_count')],
            'options'   => [
                'type'  => ['' => 'All room types'] + collect(array_keys(Location::TYPES))->mapWithKeys(fn ($t) => [$t => $t])->all(),
                'floor' => ['' => 'All floors'] + $floors->mapWithKeys(fn ($f) => [$f => $f])->all(),
                'types' => collect(array_keys(Location::TYPES))->mapWithKeys(fn ($t) => [$t => $t])->all(),
            ],
            'openModal' => old('_modal'),
        ]);
    }

    public function storeRoom(Request $request, Building $building)
    {
        $room = $building->locations()->create($this->roomData($request, $building));
        ActivityLog::record('location.room_added', "Added room {$room->fullLabel()} to {$building->name}", $building);

        return redirect()->route('admin.locations.show', $building)->with('status', "{$room->fullLabel()} added.");
    }

    public function updateRoom(Request $request, Building $building, Location $location)
    {
        abort_unless($location->building_id === $building->id, 404);

        $location->update($this->roomData($request, $building, $location));
        ActivityLog::record('location.room_updated', "Updated room {$location->fullLabel()} in {$building->name}", $building);

        return back()->with('status', "{$location->fullLabel()} saved.");
    }

    public function destroyRoom(Building $building, Location $location)
    {
        abort_unless($location->building_id === $building->id, 404);

        if (Report::withTrashed()->where('location_id', $location->id)->exists()) {
            return back()->with('error', "{$location->fullLabel()} cannot be deleted because reports refer to it.");
        }

        $label = $location->fullLabel();
        $location->delete();
        ActivityLog::record('location.room_deleted', "Deleted room {$label} from {$building->name}", $building);

        return back()->with('status', "{$label} deleted.");
    }

    private function roomData(Request $request, Building $building, ?Location $room = null): array
    {
        $data = $request->validate([
            'code'  => ['nullable', 'string', 'max:40', Rule::unique('locations', 'code')->where('building_id', $building->id)->ignore($room)],
            'name'  => ['required', 'string', 'max:120'],
            'type'  => ['required', Rule::in(array_keys(Location::TYPES))],
            'floor' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:200'],
        ], ['code.unique' => 'Another room in this building already uses this code.']);

        return [
            'code'  => filled($data['code'] ?? null) ? trim($data['code']) : null,
            'name'  => trim($data['name']),
            'type'  => $data['type'],
            'floor' => filled($data['floor'] ?? null) ? trim($data['floor']) : null,
            'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
        ];
    }
}
