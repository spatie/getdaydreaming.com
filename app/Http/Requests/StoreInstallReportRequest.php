<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInstallReportRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->isJson()) {
            abort(415, 'JSON is required.');
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'token' => ['required', 'uuid:4'],
            'app_version' => ['required', 'string', 'max:32', 'regex:/^[0-9]+(?:\.[0-9]+){0,3}(?:[-+][A-Za-z0-9.-]+)?$/'],
            'app_build' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9][A-Za-z0-9.+-]*$/'],
            'macos_version' => ['required', 'string', 'regex:/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/'],
            'architecture' => ['required', Rule::in(['arm64', 'x86_64', 'unknown'])],
            'reported_at' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?Z$/', 'date'],
            'schema_version' => ['required', 'integer', Rule::in([1])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (array_diff(array_keys($this->all()), array_keys($this->rules())) !== []) {
                    $validator->errors()->add('payload', 'Unexpected fields are not accepted.');
                }

                if ($validator->errors()->has('reported_at')) {
                    return;
                }

                $reportedAt = CarbonImmutable::parse($this->input('reported_at'));

                if ($reportedAt->lessThan(now()->subDays(30)) || $reportedAt->greaterThan(now()->addDay())) {
                    $validator->errors()->add('reported_at', 'The report time is outside the accepted range.');
                }
            },
        ];
    }
}
