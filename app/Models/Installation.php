<?php

namespace App\Models;

use Database\Factories\InstallationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installation extends Model
{
    /** @use HasFactory<InstallationFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_reported_at' => 'datetime',
        ];
    }

    /** @return HasMany<InstallReport, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(InstallReport::class, 'token_hash', 'token_hash');
    }
}
