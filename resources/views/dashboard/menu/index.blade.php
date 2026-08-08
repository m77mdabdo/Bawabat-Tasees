@php
    // Flattened depth-first so the ordered list submitted by the reorder
    // form matches what the admin sees, parents immediately followed by
    // their children.
    $flat = [];
    foreach ($items as $top) {
        $flat[] = ['item' => $top, 'depth' => 0];
        foreach ($top->children as $child) {
            $flat[] = ['item' => $child, 'depth' => 1];
        }
    }
    $topLevel = $items;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('dashboard.menu.title') }}</h2>
                <p class="mt-1 text-sm text-text-secondary">{{ __('dashboard.menu.subtitle') }}</p>
            </div>
            <a href="{{ route('dashboard.menu.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('dashboard.menu.add') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-red-100 text-red-800 rounded-md">
                    <ul class="list-disc space-y-1 ps-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-xs text-text-secondary">{{ __('dashboard.menu.order_hint') }}</p>
                <p class="mt-1 text-xs text-text-secondary">{{ __('dashboard.menu.reparent_note') }}</p>

                @if ($flat === [])
                    <p class="mt-6 rounded-md border border-dashed border-border-default px-4 py-10 text-center text-sm text-text-secondary">
                        {{ __('dashboard.menu.empty') }}
                    </p>
                @else
                    {{--
                        Reordering is dependency-light: an Alpine list where
                        each row can move up/down and be nested/un-nested,
                        submitted as one ordered payload. No sortable
                        library, no drag polyfills.
                    --}}
                    <form method="POST" action="{{ route('dashboard.menu.reorder') }}" class="mt-6"
                        x-data="menuOrder(@js(collect($flat)->map(fn ($row) => [
                            'id' => $row['item']->id,
                            'parent_id' => $row['item']->parent_id,
                            'depth' => $row['depth'],
                            'label' => $row['item']->label,
                            'system' => (bool) $row['item']->is_system,
                            'visible' => (bool) $row['item']->is_visible,
                            'children' => $row['depth'] === 0 ? $row['item']->children->count() : 0,
                            'edit' => route('dashboard.menu.edit', $row['item']),
                        ])->values()))">
                        @csrf

                        <ul class="divide-y divide-border-default rounded-md border border-border-default">
                            <template x-for="(row, index) in rows" :key="row.id">
                                <li class="flex flex-wrap items-center gap-3 px-4 py-3" :class="row.parent_id ? 'bg-bg-soft/60' : ''">
                                    <span class="text-text-secondary" x-show="row.parent_id" x-cloak aria-hidden="true">↳</span>

                                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-text-main" x-text="row.label"></span>

                                    <template x-if="row.system">
                                        <span class="rounded-full bg-luxury-gold/10 px-2 py-0.5 text-xs font-semibold text-luxury-gold">
                                            {{ __('dashboard.menu.system_badge') }}
                                        </span>
                                    </template>

                                    <template x-if="! row.visible">
                                        <span class="rounded-full bg-border-default px-2 py-0.5 text-xs text-text-secondary">
                                            {{ __('dashboard.menu.hide') }}
                                        </span>
                                    </template>

                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="moveUp(index)" :disabled="index === 0"
                                            class="rounded p-1 text-text-secondary hover:bg-bg-soft disabled:opacity-30"
                                            title="{{ __('dashboard.menu.move_up') }}" aria-label="{{ __('dashboard.menu.move_up') }}">▲</button>
                                        <button type="button" @click="moveDown(index)" :disabled="index === rows.length - 1"
                                            class="rounded p-1 text-text-secondary hover:bg-bg-soft disabled:opacity-30"
                                            title="{{ __('dashboard.menu.move_down') }}" aria-label="{{ __('dashboard.menu.move_down') }}">▼</button>
                                        <button type="button" @click="indent(index)" :disabled="! canIndent(index)"
                                            class="rounded p-1 text-text-secondary hover:bg-bg-soft disabled:opacity-30"
                                            title="{{ __('dashboard.menu.parent') }}">⇥</button>
                                        <button type="button" @click="outdent(index)" :disabled="! row.parent_id"
                                            class="rounded p-1 text-text-secondary hover:bg-bg-soft disabled:opacity-30"
                                            title="{{ __('dashboard.menu.parent_none') }}">⇤</button>
                                    </div>

                                    <a :href="row.edit" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('dashboard.common.edit') }}</a>

                                    <input type="hidden" :name="`items[${index}][id]`" :value="row.id">
                                    <input type="hidden" :name="`items[${index}][parent_id]`" :value="row.parent_id ?? ''">
                                </li>
                            </template>
                        </ul>

                        <div class="mt-4 flex justify-end">
                            <x-primary-button>{{ __('dashboard.menu.save_order') }}</x-primary-button>
                        </div>
                    </form>

                    {{-- Visibility + delete are separate forms: nesting a form
                         inside the reorder form would be invalid HTML. --}}
                    <div class="mt-8 space-y-2 border-t border-border-default pt-6">
                        @foreach ($flat as $row)
                            @php $menuItem = $row['item']; @endphp
                            <div class="flex flex-wrap items-center gap-3 text-sm {{ $row['depth'] ? 'ps-8' : '' }}">
                                <span class="min-w-0 flex-1 truncate text-text-main">{{ $menuItem->label }}</span>

                                <form method="POST" action="{{ route('dashboard.menu.visibility', $menuItem) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-md border border-border-default px-3 py-1 text-xs font-medium text-text-main hover:border-primary-green hover:text-primary-green">
                                        {{ $menuItem->is_visible ? __('dashboard.menu.hide') : __('dashboard.menu.show') }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('dashboard.menu.destroy', $menuItem) }}"
                                    onsubmit="return confirm('{{ $menuItem->is_system ? __('dashboard.menu.confirm_delete_system') : __('dashboard.menu.confirm_delete') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">
                                        {{ __('dashboard.common.delete') }}
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function menuOrder(initial) {
                return {
                    rows: initial,
                    moveUp(i) { if (i > 0) this.swap(i, i - 1); },
                    moveDown(i) { if (i < this.rows.length - 1) this.swap(i, i + 1); },
                    swap(a, b) {
                        const rows = this.rows;
                        [rows[a], rows[b]] = [rows[b], rows[a]];
                        this.rows = [...rows];
                        this.normalise();
                    },
                    // An item can only nest under the nearest top-level item
                    // above it, which keeps nesting capped at one level.
                    canIndent(i) {
                        if (i === 0) return false;
                        const above = this.rows[i - 1];
                        return ! this.rows[i].parent_id && (! above.parent_id || above.parent_id);
                    },
                    indent(i) {
                        const parent = this.nearestTopLevelAbove(i);
                        if (parent) { this.rows[i].parent_id = parent.id; this.rows = [...this.rows]; }
                    },
                    outdent(i) { this.rows[i].parent_id = null; this.rows = [...this.rows]; },
                    nearestTopLevelAbove(i) {
                        for (let j = i - 1; j >= 0; j--) {
                            if (! this.rows[j].parent_id) return this.rows[j];
                        }
                        return null;
                    },
                    // A child that has drifted above every top-level item, or
                    // sits directly under another child, is pulled back up.
                    normalise() {
                        this.rows.forEach((row, i) => {
                            if (! row.parent_id) return;
                            const parent = this.nearestTopLevelAbove(i);
                            row.parent_id = parent ? parent.id : null;
                        });
                        this.rows = [...this.rows];
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
