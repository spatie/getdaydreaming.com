<?php

namespace App\Filament\Resources\PromptSubmissions\Pages;

use App\Filament\Resources\PromptSubmissions\PromptSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListPromptSubmissions extends ListRecords
{
    protected static string $resource = PromptSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
