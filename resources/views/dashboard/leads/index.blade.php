<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-text-main leading-tight">
            {{ __('العملاء المحتملون') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-md bg-primary-green/5 border border-primary-green/30 p-4 text-primary-green">
                {{ session('status') }}
            </div>
        @endif

        <x-card class="p-4">
            <form method="GET" action="{{ route('dashboard.leads.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label for="type" class="block text-xs font-medium text-text-secondary">{{ __('النوع') }}</label>
                    <select name="type" id="type" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                        <option value="">{{ __('الكل') }}</option>
                        <option value="consultation" @selected(request('type') === 'consultation')>{{ __('طلب استشارة') }}</option>
                        <option value="contact" @selected(request('type') === 'contact')>{{ __('تواصل معنا') }}</option>
                    </select>
                </div>

                <div>
                    <label for="source_platform" class="block text-xs font-medium text-text-secondary">{{ __('المصدر') }}</label>
                    <select name="source_platform" id="source_platform" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                        <option value="">{{ __('الكل') }}</option>
                        @foreach ($sourcePlatforms as $platform)
                            <option value="{{ $platform }}" @selected(request('source_platform') === $platform)>{{ $platform }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="requested_service_id" class="block text-xs font-medium text-text-secondary">{{ __('الخدمة المطلوبة') }}</label>
                    <select name="requested_service_id" id="requested_service_id" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                        <option value="">{{ __('الكل') }}</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) request('requested_service_id') === (string) $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="date_from" class="block text-xs font-medium text-text-secondary">{{ __('من تاريخ') }}</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                </div>

                <div>
                    <label for="date_to" class="block text-xs font-medium text-text-secondary">{{ __('إلى تاريخ') }}</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                </div>

                <div class="lg:col-span-5 flex items-center gap-3">
                    <button type="submit" class="rounded-md bg-primary-green px-4 py-2 text-sm font-semibold text-white hover:bg-primary-green/90">
                        {{ __('تصفية') }}
                    </button>
                    <a href="{{ route('dashboard.leads.index') }}" class="text-sm text-text-secondary hover:text-text-main">
                        {{ __('إعادة تعيين') }}
                    </a>
                </div>
            </form>
        </x-card>

        <x-card class="overflow-hidden">
            <table class="min-w-full divide-y divide-border-default">
                <thead class="bg-bg-soft">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">{{ __('الاسم') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">{{ __('الهاتف') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">{{ __('الخدمة المطلوبة') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">{{ __('المصدر') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">{{ __('الحملة') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">{{ __('تاريخ الاستلام') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">{{ __('إجراءات') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default bg-white">
                    @forelse ($leads as $lead)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-main">{{ $lead->full_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $lead->phone }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $lead->requestedService?->name ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $lead->source_platform ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $lead->campaign_name ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $lead->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2 space-x-reverse">
                                <a href="{{ route('dashboard.leads.show', $lead) }}" class="text-primary-green hover:text-primary-green/70">{{ __('عرض') }}</a>
                                <form action="{{ route('dashboard.leads.destroy', $lead) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('أرشفة هذا العميل المحتمل؟') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">{{ __('أرشفة') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-text-secondary">{{ __('لا يوجد عملاء محتملون بعد.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        <div>
            {{ $leads->links() }}
        </div>
    </div>
</x-app-layout>
