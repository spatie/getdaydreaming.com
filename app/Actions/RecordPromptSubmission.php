<?php

namespace App\Actions;

use App\Models\PromptSubmission;

class RecordPromptSubmission
{
    /** @param array{submission_id: string, prompt: string, name?: ?string, email?: ?string, app_version: string, app_build: string} $payload */
    public function execute(array $payload): ?PromptSubmission
    {
        $attributes = [
            'prompt' => $payload['prompt'],
            'name' => $payload['name'] ?? null,
            'email' => $payload['email'] ?? null,
            'app_version' => $payload['app_version'],
            'app_build' => $payload['app_build'],
        ];

        $submission = PromptSubmission::query()->createOrFirst(
            ['submission_id' => $payload['submission_id']],
            $attributes,
        );

        foreach ($attributes as $field => $value) {
            if ($submission->{$field} !== $value) {
                return null;
            }
        }

        return $submission;
    }
}
