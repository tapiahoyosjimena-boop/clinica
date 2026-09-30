<?php

namespace App\Domains\Catalog\Filament\Resources\ExamResource\Pages;

use App\Domains\Catalog\Filament\Resources\ExamResource;
use App\Domains\Catalog\Models\Exam;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExams extends ListRecords
{
    protected static string $resource = ExamResource::class;

    public function getSubheading(): ?string
    {
        $examenes = Exam::query()->count();

        return "Existen {$examenes} exámenes registrados.";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
