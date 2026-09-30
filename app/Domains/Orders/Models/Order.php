<?php

namespace App\Domains\Orders\Models;

use App\Domains\Catalog\Models\Exam;
use App\Domains\Imaging\Models\ImagingEquipment;
use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Patients\Models\Patient;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Samples\Models\Sample;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'order_number',
        'status',
        'cancellation_reason',
        'cancelled_by_user_id',
        'cancelled_at',
        'type',
        'scheduled_date',
        'scheduled_time',
        'receptionist_id',
        'responsible_user_id',
        'equipment_id',
        'fecha_creada',       // <--- Agrega esto
        'fecha_actualizada',  // <--- Agrega esto
    ];

    protected $casts = [
        'type' => 'string',
        'scheduled_date' => 'date',
        'cancelled_at' => 'datetime',
        'fecha_creada' => 'datetime',       // <--- Agrega esto
        'fecha_actualizada' => 'datetime',  // <--- Agrega esto
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Médico derivante (opcional).
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function receptionist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receptionist_id');
    }

    /**
     * Profesional responsable: Bioquímico (órdenes laboratorio) o Tecnólogo de Imagen (órdenes imagen).
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'order_exam', 'order_id', 'exam_id');
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class, 'order_id');
    }

    /**
     * Estudios de imagen registrados para esta orden (tipo orden = imagen).
     */
    public function imagingStudies(): HasMany
    {
        return $this->hasMany(ImagingStudy::class, 'order_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'order_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(\App\Domains\Reactivos\Models\StockMovement::class, 'order_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(ImagingEquipment::class, 'equipment_id');
    }

    /**
     * Usuario que registró la cancelación operativa de la orden (panel).
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    /**
     * Indica si ya hay trabajo de laboratorio o imagen que impide cancelar la orden
     * (muestra en análisis o procesada; estudio fuera de solo «programado»).
     */
    public function hasIrreversibleLabOrImagingProgress(): bool
    {
        if ($this->samples()->whereIn('status', ['en_analisis', 'procesada'])->exists()) {
            return true;
        }

        return $this->imagingStudies()->whereIn('status', ['paciente_presente', 'en_proceso', 'completado'])->exists();
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    /**
     * Fecha y hora de cita según programación de la orden (scheduled_date + scheduled_time).
     *
     * Se interpretan como hora local de la clínica (config clinic_bank.timezone), no como UTC,
     * para alinear con TimePicker/DateTimePicker de Filament y el navegador.
     */
    public function getScheduledDateTime(): ?Carbon
    {
        if (! $this->scheduled_date || blank($this->scheduled_time)) {
            return null;
        }

        $timezone = config('clinic_bank.timezone', config('app.timezone'));

        $datePart = $this->scheduled_date instanceof Carbon
            ? $this->scheduled_date->format('Y-m-d')
            : Carbon::parse($this->scheduled_date)->format('Y-m-d');

        $timeStr = $this->scheduled_time;
        if ($timeStr instanceof \DateTimeInterface) {
            $timeStr = Carbon::instance($timeStr)->format('H:i:s');
        }

        if (is_string($timeStr) && strlen($timeStr) === 5) {
            $timeStr .= ':00';
        }

        $combined = "{$datePart} {$timeStr}";

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $combined, $timezone);
        } catch (\Throwable) {
            return Carbon::parse($combined, $timezone);
        }
    }

    public function getIsAcceptedAttribute(): bool
    {
        return $this->responsible_user_id !== null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pendiente' => 'Pendiente',
            'en_proceso' => 'En proceso',
            'completada' => 'Completada',
            'cancelada' => 'Cancelada',
            default => ucfirst($this->status),
        };
    }

    /**
     * Mensaje de validación cuando ya hay una orden de imagen activa con el mismo cupo y examen.
     */
    public static function imagingScheduleConflictValidationMessage(): string
    {
        return 'Ya existe una orden activa con la misma fecha, hora y examen, no podrá crear o guardar esta orden.';
    }

    /**
     * Normaliza un valor de hora (formulario o modelo) a `H:i:s` para comparar con la columna `scheduled_time`.
     */
    public static function normalizeScheduledTimeString(mixed $time): ?string
    {
        if (blank($time)) {
            return null;
        }
        if ($time instanceof \DateTimeInterface) {
            return Carbon::instance($time)->format('H:i:s');
        }
        $s = trim((string) $time);
        if ($s === '') {
            return null;
        }
        if (preg_match('/^\d{1,2}:\d{2}$/', $s)) {
            return $s.':00';
        }

        try {
            return Carbon::parse($s)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int|string>  $examIds
     */
    public static function hasActiveImagingScheduleEquipmentExamConflict(
        mixed $scheduledDate,
        mixed $scheduledTime,
        ?int $equipmentId,
        array $examIds,
        ?int $ignoreOrderId = null,
    ): bool {
        $equipmentId = $equipmentId !== null ? (int) $equipmentId : 0;
        if ($equipmentId <= 0) {
            return false;
        }

        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $examIds),
            static fn (int $id): bool => $id > 0,
        )));
        if ($ids === []) {
            return false;
        }

        $dateStr = match (true) {
            $scheduledDate instanceof Carbon => $scheduledDate->format('Y-m-d'),
            $scheduledDate instanceof \DateTimeInterface => Carbon::instance($scheduledDate)->format('Y-m-d'),
            is_string($scheduledDate) && $scheduledDate !== '' => Carbon::parse($scheduledDate)->format('Y-m-d'),
            default => null,
        };
        if ($dateStr === null) {
            return false;
        }

        $timeStr = static::normalizeScheduledTimeString($scheduledTime);
        if ($timeStr === null) {
            return false;
        }

        return static::query()
            ->where('type', 'imagen')
            ->whereIn('status', ['pendiente', 'en_proceso'])
            ->whereDate('scheduled_date', $dateStr)
            ->where('scheduled_time', $timeStr)
            ->where('equipment_id', $equipmentId)
            ->when($ignoreOrderId, fn (Builder $q): Builder => $q->where('id', '!=', (int) $ignoreOrderId))
            ->whereHas('exams', fn (Builder $q) => $q->whereIn('exams.id', $ids))
            ->exists();
    }
}
