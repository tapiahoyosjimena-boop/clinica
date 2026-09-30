<?php

namespace App\Providers;

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Policies\PermissionPolicy;
use App\Domains\Auth\Policies\RolePolicy;
use App\Domains\Auth\Policies\UserPolicy;
use App\Domains\Catalog\Models\Exam;
use App\Domains\Catalog\Models\ExamCategory;
use App\Domains\Catalog\Models\ExamParameter;
use App\Domains\Catalog\Models\ExamRequirement as CatalogExamRequirement;
use App\Domains\Catalog\Observers\ExamCategoryObserver;
use App\Domains\Catalog\Observers\ExamObserver;
use App\Domains\Catalog\Policies\ExamCategoryPolicy;
use App\Domains\Catalog\Policies\ExamParameterPolicy;
use App\Domains\Catalog\Policies\ExamPolicy;
use App\Domains\Catalog\Policies\ExamRequirementPolicy;
use App\Domains\Imaging\Models\ImagingEquipment;
use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Imaging\Observers\ImagingEquipmentObserver;
use App\Domains\Imaging\Observers\ImagingStudyObserver;
use App\Domains\Imaging\Policies\ImagingEquipmentPolicy;
use App\Domains\Imaging\Policies\ImagingStudyPolicy;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Observers\OrderObserver;
use App\Domains\Orders\Policies\OrderPolicy;
use App\Domains\Patients\Models\Patient;
use App\Domains\Patients\Observers\PatientObserver;
use App\Domains\Patients\Policies\PatientPolicy;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Observers\InvoiceObserver;
use App\Domains\Payments\Policies\InvoicePolicy;
use App\Domains\Payments\Policies\PaymentPolicy;
use App\Domains\Reactivos\Models\Provider as ReactivosProvider;
use App\Domains\Reactivos\Models\Reagent;
use App\Domains\Reactivos\Observers\ReagentObserver;
use App\Domains\Reactivos\Policies\ProviderPolicy as ReactivosProviderPolicy;
use App\Domains\Reactivos\Policies\ReagentPolicy;
use App\Domains\Results\Models\Result;
use App\Domains\Results\Policies\ResultPolicy;
use App\Domains\Samples\Models\Sample;
use App\Domains\Samples\Observers\SampleObserver;
use App\Domains\Samples\Policies\SamplePolicy;
use App\Filament\Livewire\DatabaseNotifications as ClinicDatabaseNotifications;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\Mechanisms\ComponentRegistry;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
         * Livewire solo resuelve nombres tipo «app.filament.*» bajo config('livewire.class_namespace') (p. ej. App\Livewire\…).
         * Nuestro componente vive en App\Filament\Livewire: sin alias, las peticiones AJAX devuelven ComponentNotFoundException.
         */
        $this->app->booted(function (): void {
            $name = app(ComponentRegistry::class)->getName(ClinicDatabaseNotifications::class);
            Livewire::component($name, ClinicDatabaseNotifications::class);
        });

        // Filament DateTimePicker interpreta strings con config('app.timezone') y luego aplica la TZ del campo.
        // Si APP_TIMEZONE=UTC y el picker usa America/La_Paz, cada request Livewire (p. ej. al cambiar examen en muestras)
        // rehidrata mal la hora. Una sola TZ de aplicación alineada con la clínica evita el desfase.
        $clinicTimezone = config('clinic_bank.timezone');
        if (is_string($clinicTimezone) && $clinicTimezone !== '') {
            config(['app.timezone' => $clinicTimezone]);
            date_default_timezone_set($clinicTimezone);
        }

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Exam::class, ExamPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Sample::class, SamplePolicy::class);
        Gate::policy(ImagingEquipment::class, ImagingEquipmentPolicy::class);
        Gate::policy(ImagingStudy::class, ImagingStudyPolicy::class);
        Gate::policy(ExamCategory::class, ExamCategoryPolicy::class);
        Gate::policy(ExamParameter::class, ExamParameterPolicy::class);
        Gate::policy(CatalogExamRequirement::class, ExamRequirementPolicy::class);
        Gate::policy(Result::class, ResultPolicy::class);
        Gate::policy(ReactivosProvider::class, ReactivosProviderPolicy::class);
        Gate::policy(Reagent::class, ReagentPolicy::class);

        Order::observe(OrderObserver::class);
        Sample::observe(SampleObserver::class);
        ImagingEquipment::observe(ImagingEquipmentObserver::class);
        ImagingStudy::observe(ImagingStudyObserver::class);
        Patient::observe(PatientObserver::class);
        ExamCategory::observe(ExamCategoryObserver::class);
        Exam::observe(ExamObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Reagent::observe(ReagentObserver::class);
    }
}
