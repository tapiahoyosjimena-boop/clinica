<?php

namespace App\Domains\Patients\Observers;

use App\Domains\Patients\Jobs\CreatePatientUserJob;
use App\Domains\Patients\Models\Patient;

class PatientObserver
{
    /**
     * Al crear un nuevo paciente, crea de inmediato su cuenta de usuario
     * y envía las credenciales por correo (ejecución síncrona, sin cola).
     */
    public function created(Patient $patient): void
    {
        CreatePatientUserJob::dispatch($patient);
    }
}
