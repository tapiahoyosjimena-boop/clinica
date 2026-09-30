@extends('patient-portal.layouts.app')

@section('title', 'Mi Perfil')

@section('content')
    @include('portals.partials.profile-page-header', [
        'title' => 'Mi Perfil',
        'subtitle' => 'Información personal registrada en Clínica Norte.',
        'editRoute' => route('patient.profile.edit'),
    ])

    @if (session('status'))
        <div class="profile-alert profile-alert-success">{{ session('status') }}</div>
    @endif

    @php
        use App\Support\GenderOptions;
        use App\Support\ProfileDisplaySupport;

        $genderLabel = GenderOptions::label($patient->gender);
        $addressValue = trim((string) $patient->address);

        $fields = [
            ['label' => 'Nombre completo', 'value' => $patient->full_name],
            ['label' => 'Cédula de identidad (CI)', 'value' => $patient->ci],
            [
                'label' => 'Fecha de nacimiento',
                'value' => $patient->birth_date?->format('d/m/Y'),
                'mutedSuffix' => $patient->birth_date ? '('.$patient->age.' años)' : null,
            ],
            ['label' => 'Sexo', 'value' => $genderLabel],
            ['label' => 'Teléfono', 'value' => $patient->phone],
            ['label' => 'Correo electrónico', 'value' => $patient->email ?: auth()->user()->email],
            ['label' => 'Dirección', 'value' => $addressValue !== '' ? $addressValue : null, 'fullWidth' => true],
        ];
    @endphp

    <x-profile-card
        :display-name="$patient->full_name"
        context-line="Paciente — Clínica Norte S.R.L."
        :initials="ProfileDisplaySupport::initialsFromPatient($patient)"
        :fields="$fields"
    />
@endsection
