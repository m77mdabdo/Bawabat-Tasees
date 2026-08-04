<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-text-main leading-tight">
            {{ $title }}
        </h2>
    </x-slot>

    <x-dashboard.coming-soon :title="$title" :message="$message" />
</x-app-layout>
