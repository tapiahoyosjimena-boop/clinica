@props([
    'variant' => 'portal',
    'placeholder' => null,
    'includeAssets' => true,
])

@php
    $placeholders = [
        'welcome' => 'Buscar exámenes del catálogo…',
        'portal' => 'Buscar órdenes, resultados o pacientes…',
        'filament' => 'Buscar pacientes, órdenes o exámenes…',
    ];
    $placeholder = $placeholder ?? ($placeholders[$variant] ?? $placeholders['portal']);
    $cssVer = is_file(public_path('css/home-search.css')) ? (string) filemtime(public_path('css/home-search.css')) : '1';
    $jsVer = is_file(public_path('js/home-search.js')) ? (string) filemtime(public_path('js/home-search.js')) : '1';
@endphp

@if ($includeAssets)
    @once
        @push('styles')
            <link rel="stylesheet" href="{{ asset('css/home-search.css') }}?v={{ $cssVer }}">
        @endpush
        @push('scripts')
            <script src="{{ asset('js/home-search.js') }}?v={{ $jsVer }}" defer></script>
        @endpush
    @endonce
@endif

<div
    class="home-search home-search--{{ $variant }}"
    data-home-search
    data-endpoint="{{ route('home.search') }}"
    {{ $attributes }}
>
    <div class="home-search__wrap">
        <svg class="home-search__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
        </svg>
        <input
            type="search"
            class="home-search__input"
            data-home-search-input
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            spellcheck="false"
            aria-label="Búsqueda rápida"
            aria-autocomplete="list"
            aria-controls="home-search-panel-{{ $variant }}"
        >
    </div>
    <div
        id="home-search-panel-{{ $variant }}"
        class="home-search__panel"
        data-home-search-panel
        role="listbox"
        aria-live="polite"
    ></div>
</div>
