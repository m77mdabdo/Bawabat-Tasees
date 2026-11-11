<x-filament-widgets::widget>
    <div class="bt-hero">
        <p style="position:relative;z-index:1;margin:0;font-size:.85rem;color:rgba(255,255,255,.72)">{{ $date }}</p>
        <h2 style="position:relative;z-index:1">{{ $greeting }}</h2>
        <p style="position:relative;z-index:1">{{ __('Here is what is waiting for you on Bawabat Taasees today.') }}</p>
        <div class="bt-chips">
            @if (! is_null($unhandled))
                <a href="{{ $leadsUrl }}" class="bt-chip {{ $unhandled ? 'bt-chip--alert' : '' }}"><strong>{{ $unhandled }}</strong> {{ __('enquiries not handled') }}</a>
                <span class="bt-chip"><strong>{{ $today }}</strong> {{ __('new today') }}</span>
            @endif
            @if (! is_null($pendingComments))
                <span class="bt-chip"><strong>{{ $pendingComments }}</strong> {{ __('comments to review') }}</span>
            @endif
        </div>
    </div>

    @if (count($actions))
        <div class="bt-actions">
            @foreach ($actions as $action)
                <a href="{{ $action['url'] }}" class="bt-action">
                    <span class="bt-action__icon"><x-filament::icon :icon="$action['icon']" class="h-5 w-5" /></span>
                    <span>
                        <span class="bt-action__title" style="display:block">{{ $action['label'] }}</span>
                        <span class="bt-action__hint">{{ $action['hint'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</x-filament-widgets::widget>
