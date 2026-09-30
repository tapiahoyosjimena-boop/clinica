@php
    use App\Support\GenderOptions;
    use App\Support\ProfileDisplaySupport;

    $user = $this->getUser();
    $roleName = $user->roles->first()?->name ?? 'Personal del sistema';
    $genderLabel = GenderOptions::label($user->gender);
@endphp

<x-filament-panels::page>
    @php
        $profileCardCssVer = is_file(public_path('css/profile-card.css'))
            ? (string) filemtime(public_path('css/profile-card.css'))
            : '1';
    @endphp
    <link rel="stylesheet" href="{{ asset('css/profile-card.css') }}?v={{ $profileCardCssVer }}">

    @if (session('status'))
        <div class="profile-alert profile-alert-success mb-4">{{ session('status') }}</div>
    @endif

    <x-profile-card
        :display-name="$user->name"
        :context-line="$roleName.' — Clínica Norte S.R.L.'"
        :initials="ProfileDisplaySupport::initialsFromName($user->name)"
        :fields="[
            ['label' => 'Nombre completo', 'value' => $user->name],
            ['label' => 'Correo electrónico', 'value' => $user->email],
            ['label' => 'Celular', 'value' => $user->phone],
            ['label' => 'Sexo', 'value' => $genderLabel],
            ['label' => 'Rol', 'value' => $roleName],
        ]"
    />
</x-filament-panels::page>
