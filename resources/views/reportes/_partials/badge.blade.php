@php
    $badgeClass = match ($variant ?? 'neutral') {
        'success' => 'badge-success',
        'info' => 'badge-info',
        'warning' => 'badge-warning',
        'danger' => 'badge-danger',
        default => 'badge-neutral',
    };
@endphp
<span class="badge {{ $badgeClass }}">{{ $label }}</span>
