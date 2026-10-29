<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['acs_case_id', 'performed_at', 'interpreted_at', 'result', 'st_elevation', 'transmitted_prehospital', 'interpretation'])]
class AcsEcgRecord extends Model
{
    protected function casts(): array
    {
        return ['performed_at' => 'datetime', 'interpreted_at' => 'datetime', 'st_elevation' => 'boolean', 'transmitted_prehospital' => 'boolean'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(AcsCase::class, 'acs_case_id');
    }
}
