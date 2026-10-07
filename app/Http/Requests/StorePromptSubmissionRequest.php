<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePromptSubmissionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->isJson()) {
            abort(415, 'JSON is required.');
        }

        $normalized = [];

        if (is_string($this->input('submission_id'))) {
            $normalized['submission_id'] = strtolower($this->input('submission_id'));
        }

        foreach (['prompt', 'name', 'email'] as $field) {
            if (is_string($this->input($field))) {
                $normalized[$field] = trim($this->input($field));
            }
        }

        if (isset($normalized['name'])) {
            $normalized['name'] = trim(ltrim($normalized['name'], '@'));
        }

        foreach (['name', 'email'] as $field) {
            if (($normalized[$field] ?? null) === '') {
                $normalized[$field] = null;
            }
        }

        $this->merge($normalized);
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'submission_id' => ['required', 'uuid:4'],
            'prompt' => ['required', 'string', 'min:1', 'max:5000', 'regex:/\A[^\x00]*\z/u'],
            'name' => ['sometimes', 'nullable', 'string', 'max:60', "regex:/^[\p{L}\p{N} ._'-]+$/u"],
            'email' => ['sometimes', 'nullable', 'string', 'email:rfc', 'max:254'],
            'app_version' => ['required', 'string', 'max:32', 'regex:/^[0-9]+(?:\.[0-9]+){0,3}(?:[-+][A-Za-z0-9.-]+)?$/'],
            'app_build' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9][A-Za-z0-9.+-]*$/'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (array_diff(array_keys($this->all()), array_keys($this->rules())) !== []) {
                    $validator->errors()->add('payload', 'Unexpected fields are not accepted.');
                }
            },
        ];
    }
}
