<?php

namespace App\Filament\Resources\PromptSubmissions\Pages;

use App\Filament\Resources\PromptSubmissions\PromptSubmissionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPromptSubmission extends ViewRecord
{
    protected static string $resource = PromptSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
