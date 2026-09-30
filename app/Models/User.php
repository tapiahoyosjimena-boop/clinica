<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domains\Auth\Support\SystemPermissions;
use App\Domains\Patients\Models\Patient;
use App\Support\SystemAdministratorGuard;
use App\Domains\Auth\Notifications\PortalResetPasswordNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Auth\ResetPassword as FilamentResetPasswordNotification;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Notifiable;

    /**
     * Acceso al panel Filament según permiso admin.panel.access (rol o extra directo).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (SystemAdministratorGuard::isPrimaryAdministrator($this)) {
            return true;
        }

        return $this->can(SystemPermissions::ADMIN_PANEL);
    }

    public function canAccessPatientPortal(): bool
    {
        return $this->can(SystemPermissions::PATIENT_PORTAL);
    }

    public function canAccessDoctorPortal(): bool
    {
        return $this->can(SystemPermissions::DOCTOR_PORTAL);
    }

    /**
     * Ruta preferida tras login si no usa el panel admin (portales web).
     */
    public function preferredPortalRoute(): ?string
    {
        if ($this->canAccessPatientPortal()) {
            return 'patient.dashboard';
        }

        if ($this->canAccessDoctorPortal()) {
            return 'doctor.dashboard';
        }

        return null;
    }

    /**
     * Paciente vinculado a este usuario (existe solo para usuarios de tipo paciente).
     */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class, 'user_id');
    }

    /**
     * Usado por Filament (/admin). Los portales web envían el correo desde
     * PortalPasswordResetController con la ruta del portal solicitado.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        if ($this->hasRole('Paciente')) {
            $this->notify(new PortalResetPasswordNotification($token, 'patient.password.reset'));

            return;
        }

        if ($this->hasRole('Médico')) {
            $this->notify(new PortalResetPasswordNotification($token, 'doctor.password.reset'));

            return;
        }

        $this->notify(new FilamentResetPasswordNotification($token));
    }

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'gender',
        'password',
        'hora_creacion',
        'fecha_creacion',
    ];

    /**
     * Atributos ocultos en la serialización.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Atributos que deben convertirse (cast) a tipos nativos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
