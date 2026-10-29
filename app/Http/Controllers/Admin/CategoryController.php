<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    private const PER_PAGE = [7, 14, 28]; // the design shows "7 / page"

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE, true) ? (int) $request->query('per_page') : self::PER_PAGE[0];

        $categories = Category::query()
            ->select('categories.*')
            ->selectRaw("(select count(*) from reports where reports.deleted_at is null and reports.category_id = categories.id and reports.status in ('pending','verified','in_progress')) as active_reports_count")
            ->when($filters['q'], function ($q, $term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('description', 'like', $like));
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.categories.index', [
            'categories' => $categories,
            'filters'    => $filters,
            'perPage'    => $perPage,
            'perPageOptions' => self::PER_PAGE,
            'hasFilters' => collect($filters)->filter()->isNotEmpty(),
            'total'      => Category::count(),
            'activeReports' => Report::whereIn('status', ['pending', 'verified', 'in_progress'])->count(),
            'openModal'  => old('_modal'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $category = Category::create($data);

        ActivityLog::record('category.created', "Added asset category {$category->name}", $category);

        return redirect()->route('admin.categories.index')->with('status', "{$category->name} added.");
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request, $category));
        ActivityLog::record('category.updated', "Updated asset category {$category->name}", $category);

        return back()->with('status', "{$category->name} saved.");
    }

    /** Deactivate / reactivate. Existing reports keep their category either way. */
    public function toggle(Category $category)
    {
        $category->update(['is_active' => ! ($category->is_active ?? true)]);

        $state = $category->is_active ? 'reactivated' : 'deactivated';
        ActivityLog::record('category.' . $state, ucfirst($state) . " asset category {$category->name}", $category);

        return back()->with('status', "{$category->name} {$state}." . ($category->is_active ? '' : ' It is hidden from new report forms.'));
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:80', Rule::unique('categories', 'name')->ignore($category)],
            'description' => ['nullable', 'string', 'max:200'],
        ]);

        return $data + ['is_active' => $request->boolean('is_active')];
    }

    private function filters(Request $request): array
    {
        return ['q' => trim((string) $request->query('q', '')) ?: null];
    }
}
