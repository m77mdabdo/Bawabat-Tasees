{{-- One numbered formation step. The number comes from $itemIndex, the
     position in the ORIGINAL set — using the loop position would have
     the clones counting on past the real total (7, 8, 9 … of 6). --}}
<div class="flex h-full items-center gap-4 rounded-xl border border-border-default bg-white p-5">
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-green text-sm font-bold text-white" aria-hidden="{{ $isClone ? 'true' : 'false' }}">
        {{ $itemIndex + 1 }}
    </span>
    <h3 class="font-semibold text-text-main">{{ $item->title }}</h3>
</div>
