<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ReorderMenuRequest;
use App\Http\Requests\Dashboard\StoreMenuItemRequest;
use App\Http\Requests\Dashboard\UpdateMenuItemRequest;
use App\Models\MenuItem;
use App\Services\Cms\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    public function __construct(
        private readonly MenuService $menuService,
    ) {}

    public function index(): View
    {
        return view('dashboard.menu.index', [
            // The whole tree, including hidden items — this is the editor,
            // not the public navbar.
            'items' => MenuItem::topLevel()->ordered()->with('children')->get(),
        ]);
    }

    public function create(): View
    {
        return view('dashboard.menu.create', [
            'parents' => $this->parentOptions(),
            'routes' => $this->routeOptions(),
        ]);
    }

    public function store(StoreMenuItemRequest $request): RedirectResponse
    {
        $this->menuService->create($request->validated());

        return redirect()
            ->route('dashboard.menu.index')
            ->with('status', __('dashboard.flash.menu_item_created'));
    }

    public function edit(MenuItem $menu): View
    {
        return view('dashboard.menu.edit', [
            'item' => $menu,
            'parents' => $this->parentOptions($menu),
            'routes' => $this->routeOptions(),
        ]);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menu): RedirectResponse
    {
        $this->menuService->update($menu, $request->validated());

        return redirect()
            ->route('dashboard.menu.index')
            ->with('status', __('dashboard.flash.menu_item_updated'));
    }

    public function destroy(MenuItem $menu): RedirectResponse
    {
        $this->menuService->delete($menu);

        return redirect()
            ->route('dashboard.menu.index')
            ->with('status', __('dashboard.flash.menu_item_deleted'));
    }

    public function toggleVisibility(MenuItem $menu): RedirectResponse
    {
        $this->menuService->toggleVisibility($menu);

        return redirect()
            ->route('dashboard.menu.index')
            ->with('status', __('dashboard.flash.menu_item_visibility_updated'));
    }

    public function reorder(ReorderMenuRequest $request): RedirectResponse
    {
        $this->menuService->reorder($request->validated('items'));

        return redirect()
            ->route('dashboard.menu.index')
            ->with('status', __('dashboard.flash.menu_reordered'));
    }

    /**
     * Top-level items only — nesting is capped at one level, so a child
     * can never be offered as a parent. Editing an item also excludes
     * itself.
     *
     * @return Collection<int, MenuItem>
     */
    private function parentOptions(?MenuItem $except = null): Collection
    {
        return MenuItem::topLevel()
            ->ordered()
            ->when($except, fn ($q) => $q->whereKeyNot($except->getKey()))
            ->get();
    }

    /**
     * Friendly labels for the whitelist, for the route picker.
     *
     * @return array<string, string>
     */
    private function routeOptions(): array
    {
        return collect(MenuItem::ROUTE_WHITELIST)
            ->map(fn (string $key) => __($key))
            ->all();
    }
}
