@extends('doctor-portal.layouts.app')

@section('title', 'Editar cuenta')

@push('styles')
    @include('portals.partials.profile-edit-styles')
@endpush

@section('content')
    @include('portals.partials.profile-page-header', [
        'title' => 'Editar cuenta',
        'subtitle' => 'Actualice sus datos de contacto y acceso al portal médico.',
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
        <form method="POST" action="{{ route('doctor.profile.update') }}" class="profile-form">
            @csrf
            @method('PUT')

            <p class="form-section-title" style="margin-top:0;padding-top:0;border-top:none;">Datos personales</p>
            <div class="form-row">
                <div>
                    <label for="name">Nombre completo *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                </div>
                <div>
                    <label for="email">Correo electrónico *</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                </div>
                <div>
                    <label for="phone">Celular</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Ej: 70000000">
                </div>
                <div>
                    <label for="gender">Sexo</label>
                    <select id="gender" name="gender">
                        <option value="">— Seleccionar —</option>
                        @foreach(\App\Support\GenderOptions::labels() as $value => $label)
                            <option value="{{ $value }}" @selected(old('gender', $user->gender) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
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
                <a href="{{ route('doctor.profile') }}" class="btn btn-outline">Cancelar</a>
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
