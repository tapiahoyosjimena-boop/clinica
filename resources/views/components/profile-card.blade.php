@props([
    'displayName',
    'contextLine',
    'initials',
    'fields' => [],
    'cardClass' => '',
])

@php
    $profileCardCssVer = is_file(public_path('css/profile-card.css'))
        ? (string) filemtime(public_path('css/profile-card.css'))
        : '1';
@endphp

@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/profile-card.css') }}?v={{ $profileCardCssVer }}">
    @endpush
@endonce

<div {{ $attributes->merge(['class' => 'profile-card-wrap '.$cardClass]) }}>
    <div class="profile-card-header">
        <div class="profile-grid">
            <div class="profile-avatar" aria-hidden="true">{{ $initials }}</div>
            <div class="profile-info">
                <h2>{{ $displayName }}</h2>
                <span class="profile-role">{{ $contextLine }}</span>
            </div>
        </div>
    </div>

    <div class="profile-fields">
        @foreach ($fields as $field)
            @php
                $fullWidth = ! empty($field['fullWidth']);
                $label = $field['label'] ?? '';
                $value = $field['value'] ?? null;
                $html = $field['html'] ?? null;
                $mutedSuffix = $field['mutedSuffix'] ?? null;
            @endphp
            <div @class(['profile-field', 'is-full-width' => $fullWidth])>
                <label>{{ $label }}</label>
                @if ($html !== null)
                    <div class="profile-field-value">{!! $html !!}</div>
                @elseif (filled($value))
                    <span>
                        {{ $value }}
                        @if ($mutedSuffix)
                            <span class="profile-field-muted"> {{ $mutedSuffix }}</span>
                        @endif
                    </span>
                @else
                    <span class="empty-val">—</span>
                @endif
            </div>
        @endforeach
    </div>
</div>
