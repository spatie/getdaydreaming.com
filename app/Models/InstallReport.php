<?php

namespace App\Models;

use Database\Factories\InstallReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallReport extends Model
{
    /** @use HasFactory<InstallReportFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Installation, $this> */
    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class, 'token_hash', 'token_hash');
    }
}
