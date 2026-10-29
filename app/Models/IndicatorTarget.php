<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'name', 'unit', 'warning_value', 'target_value', 'comparison', 'description', 'is_active'])]
class IndicatorTarget extends Model
{
    protected function casts(): array
    {
        return [
            'warning_value' => 'decimal:2',
            'target_value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
