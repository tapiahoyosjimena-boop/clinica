<?php

namespace App\Domains\Catalog\Filament\Resources\ExamCategoryResource\Pages;

use App\Domains\Catalog\Filament\Resources\ExamCategoryResource;
use App\Domains\Catalog\Models\ExamCategory;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExamCategories extends ListRecords
{
    protected static string $resource = ExamCategoryResource::class;

    public function getSubheading(): ?string
    {
        $catalogos = ExamCategory::query()->count();

        return "Existen {$catalogos} catálogos registrados.";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
