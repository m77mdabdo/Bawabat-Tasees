@php
    $events = $lead->conversionEvents;
    $totalValue = $events->sum(fn ($event) => (float) $event->value);
    $currency = $events->firstWhere(fn ($event) => filled($event->value))?->currency ?? 'SAR';
@endphp

<div class="mt-6">
    <x-card>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-text-main">
                    {{ __('dashboard.conversions.section_heading') }}
                    @if ($lead->isConverted())
                        <span class="ms-2 rounded-full bg-primary-green/10 px-2 py-0.5 text-xs font-semibold text-primary-green">
                            {{ __('dashboard.conversions.converted_badge') }}
                        </span>
                    @endif
                </h3>
                <p class="mt-1 text-xs text-text-secondary">{{ __('dashboard.conversions.section_hint') }}</p>
            </div>
            @if ($totalValue > 0)
                <p class="text-sm text-text-secondary">
                    {{ __('dashboard.conversions.total_value') }}:
                    <span class="font-semibold text-text-main">{{ number_format($totalValue, 2) }} {{ $currency }}</span>
                </p>
            @endif
        </div>

        {{-- Existing events, newest first (ordered by the relation). --}}
        <div class="mt-4">
            @if ($events->isEmpty())
                <p class="rounded-md border border-dashed border-border-default px-4 py-6 text-center text-sm text-text-secondary">
                    {{ __('dashboard.conversions.empty') }}
                </p>
            @else
                <ul class="divide-y divide-border-default">
                    @foreach ($events as $event)
                        <li class="flex flex-wrap items-start justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-text-main">
                                    {{ __('dashboard.conversions.types.'.$event->event_type) }}
                                    @if (filled($event->value))
                                        <span class="text-text-secondary">— {{ number_format((float) $event->value, 2) }} {{ $event->currency }}</span>
                                    @endif
                                </p>
                                <p class="mt-0.5 text-xs text-text-secondary">{{ $event->occurred_at?->format('Y-m-d H:i') }}</p>
                                @if ($event->notes)
                                    <p class="mt-1 whitespace-pre-line text-sm text-text-secondary">{{ $event->notes }}</p>
                                @endif
                            </div>
                            <form action="{{ route('dashboard.leads.conversions.destroy', [$lead, $event]) }}" method="POST"
                                onsubmit="return confirm('{{ __('dashboard.conversions.delete_confirm') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">
                                    {{ __('dashboard.conversions.delete') }}
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Log a new conversion. --}}
        <form action="{{ route('dashboard.leads.conversions.store', $lead) }}" method="POST" class="mt-6 border-t border-border-default pt-4">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <x-input-label for="event_type" :value="__('dashboard.conversions.event_type')" />
                    <select id="event_type" name="event_type" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach (App\Models\ConversionEvent::TYPES as $type)
                            <option value="{{ $type }}" @selected(old('event_type') === $type)>
                                {{ __('dashboard.conversions.types.'.$type) }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('event_type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="value" :value="__('dashboard.conversions.value')" />
                    <x-text-input id="value" name="value" type="number" step="0.01" min="0" dir="ltr"
                        class="mt-1 block w-full" :value="old('value')" />
                    <x-input-error :messages="$errors->get('value')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="currency" :value="__('dashboard.conversions.currency')" />
                    <x-text-input id="currency" name="currency" type="text" maxlength="3" dir="ltr"
                        class="mt-1 block w-full" :value="old('currency', 'SAR')" />
                    <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="occurred_at" :value="__('dashboard.conversions.occurred_at')" />
                    <x-text-input id="occurred_at" name="occurred_at" type="datetime-local" class="mt-1 block w-full"
                        :value="old('occurred_at', now()->format('Y-m-d\TH:i'))" />
                    <x-input-error :messages="$errors->get('occurred_at')" class="mt-2" />
                </div>
            </div>

            <div class="mt-4">
                <x-input-label for="notes" :value="__('dashboard.conversions.notes')" />
                <textarea id="notes" name="notes" rows="2"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>

            <div class="mt-4 flex justify-end">
                <x-primary-button>{{ __('dashboard.conversions.log_button') }}</x-primary-button>
            </div>
        </form>
    </x-card>
</div>
