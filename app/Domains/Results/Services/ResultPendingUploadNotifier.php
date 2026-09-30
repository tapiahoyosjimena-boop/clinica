<?php

namespace App\Domains\Results\Services;

use App\Domains\Imaging\Models\ImagingStudy;
use App\Domains\Notifications\Notifications\ResultadoPendienteCargaResponsableNotification;
use App\Domains\Results\Models\Result;
use App\Domains\Samples\Models\Sample;
use App\Models\User;

class ResultPendingUploadNotifier
{
    public function notifyForSample(Sample $sample, Result $result): void
    {
        $sample->loadMissing('order');

        $userId = $sample->bioquimico_asignado_id
            ?? $sample->order?->responsible_user_id
            ?? $result->bioquimico_id;

        $this->notifyResponsibleUser($userId, $result);
    }

    public function notifyForImagingStudy(ImagingStudy $study, Result $result): void
    {
        $study->loadMissing('order');

        $userId = $study->responsible_user_id
            ?? $study->order?->responsible_user_id
            ?? $result->bioquimico_id;

        $this->notifyResponsibleUser($userId, $result);
    }

    private function notifyResponsibleUser(?int $userId, Result $result): void
    {
        if (! $userId) {
            return;
        }

        $user = User::find($userId);
        if (! $user || ! $user->can('results.access')) {
            return;
        }

        if ($result->validated_at !== null) {
            return;
        }

        $result->loadMissing(['order.patient', 'exam']);
        $user->notify(new ResultadoPendienteCargaResponsableNotification($result));
    }
}
