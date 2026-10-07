<?php

namespace App\Models;

use Database\Factories\PromptSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PromptSubmission extends Model
{
    /** @use HasFactory<PromptSubmissionFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (PromptSubmission $submission): void {
            $submission->reference ??= (string) Str::ulid();
        });
    }
}
