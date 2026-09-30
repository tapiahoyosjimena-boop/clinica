@extends('patient-portal.layouts.app')

@section('title', 'Editar cuenta')

@push('styles')
    @include('portals.partials.profile-edit-styles')
@endpush

@section('content')
    @include('portals.partials.profile-page-header', [
        'title' => 'Editar cuenta',
        'subtitle' => 'Actualice su teléfono, correo y dirección. Los datos clínicos (CI, nacimiento, sexo) solo los modifica recepción.',
        'showEditButton' => false,
    ])

    @if($errors->any())
        <div class="profile-alert profile-alert-error">
            <ul style="margin:0;padding-left:1.1rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="pp-card">
        <form method="POST" action="{{ route('patient.profile.update') }}" class="profile-form">
            @csrf
            @method('PUT')

            <p class="form-section-title" style="margin-top:0;padding-top:0;border-top:none;">Datos de contacto</p>
            <div class="form-row">
                <div>
                    <label for="phone">Teléfono *</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $patient->phone) }}" required>
                </div>
                <div>
                    <label for="email">Correo electrónico *</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $patient->email ?: $user->email) }}" required>
                </div>
            </div>
            <div>
                <label for="address">Dirección</label>
                <textarea id="address" name="address" placeholder="Opcional">{{ old('address', $patient->address) }}</textarea>
            </div>

            <p class="form-section-title">Seguridad</p>
            <div class="checkbox-row">
                <input type="checkbox" id="change_password" name="change_password" value="1" @checked(old('change_password'))>
                <label for="change_password">Cambiar contraseña de acceso al portal</label>
            </div>
            <div id="password-fields" style="display:none;">
                <div class="form-row">
                    <div>
                        <label for="password">Nueva contraseña</label>
                        <input type="password" id="password" name="password" autocomplete="new-password">
                        <p class="form-hint">{{ \App\Support\PasswordPolicy::helperText() }}</p>
                    </div>
                    <div>
                        <label for="password_confirmation">Confirmar contraseña</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                <a href="{{ route('patient.profile') }}" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>

    <script>
        (function () {
            var cb = document.getElementById('change_password');
            var box = document.getElementById('password-fields');
            if (!cb || !box) return;
            function toggle() { box.style.display = cb.checked ? 'block' : 'none'; }
            cb.addEventListener('change', toggle);
            toggle();
        })();
    </script>
@endsection
