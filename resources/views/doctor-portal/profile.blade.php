@extends('doctor-portal.layouts.app')

@section('title', 'Mi Perfil')

@section('content')
    @include('portals.partials.profile-page-header', [
        'title' => 'Mi Perfil',
        'subtitle' => 'Datos de su cuenta registrada en Clínica Norte.',
        'editRoute' => route('doctor.profile.edit'),
    ])

    @if (session('status'))
        <div class="profile-alert profile-alert-success">{{ session('status') }}</div>
    @endif

    @php
        use App\Support\GenderOptions;
        use App\Support\ProfileDisplaySupport;

        $genderLabel = GenderOptions::label($user->gender);
        $roles = $user->getRoleNames();
        $rolesHtml = $roles->isNotEmpty()
            ? $roles->map(fn (string $role) => '<span class="badge badge-green" style="margin-right:.35rem;">'.e($role).'</span>')->implode('')
            : null;

        $fields = [
            ['label' => 'Nombre completo', 'value' => $user->name],
            ['label' => 'Correo electrónico', 'value' => $user->email],
            ['label' => 'Celular', 'value' => $user->phone],
            ['label' => 'Sexo', 'value' => $genderLabel],
            [
                'label' => 'Roles asignados',
                'html' => $rolesHtml,
                'fullWidth' => true,
            ],
        ];
    @endphp

    <x-profile-card
        :display-name="'Dr. '.$user->name"
        context-line="Médico derivante — Clínica Norte S.R.L."
        :initials="ProfileDisplaySupport::initialsFromName($user->name)"
        :fields="$fields"
    />
@endsection
