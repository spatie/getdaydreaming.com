<?php

namespace App\Http\Controllers\Api;

use App\Actions\RecordPromptSubmission;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePromptSubmissionRequest;
use Illuminate\Http\JsonResponse;

class PromptSubmissionsController extends Controller
{
    public function store(StorePromptSubmissionRequest $request, RecordPromptSubmission $recordPromptSubmission): JsonResponse
    {
        $submission = $recordPromptSubmission->execute($request->validated());

        if ($submission === null) {
            return response()->json(['message' => 'This submission ID already belongs to a different request.'], 409);
        }

        return response()->json(
            ['reference' => $submission->reference],
            $submission->wasRecentlyCreated ? 201 : 200,
        );
    }
}
