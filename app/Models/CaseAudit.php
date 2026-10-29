<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['acv_case_id', 'user_id', 'event', 'field', 'old_value', 'new_value', 'ip_address', 'user_agent'])]
class CaseAudit extends Model
{
    public function case(): BelongsTo
    {
        return $this->belongsTo(AcvCase::class, 'acv_case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
