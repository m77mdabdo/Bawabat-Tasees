{{-- One why-invest highlight. $item is a PageSection; $icons is the
     keyword map passed through from the homepage. These cards carry no
     links, so there is nothing to take out of the tab order — the
     aria-hidden on the clone's wrapper is the whole job. --}}
<x-card class="h-full p-6">
    <span class="text-3xl" aria-hidden="true">{{ $icons[$item->icon] ?? '⭐' }}</span>
    <h3 class="mt-4 font-semibold text-text-main">{{ $item->title }}</h3>
    @if ($item->description)
        <p class="mt-2 text-sm text-text-secondary">
            {{ Illuminate\Support\Str::limit($item->description, 90) }}
        </p>
    @endif
</x-card>
