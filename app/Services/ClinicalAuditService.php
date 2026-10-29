<?php

namespace App\Services;

use App\Models\ClinicalAudit;
use Illuminate\Database\Eloquent\Model;

class ClinicalAuditService
{
    public function record(
        Model $auditable,
        int $programId,
        string $event,
        ?string $field = null,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?string $changeReason = null,
        ?int $approvedBy = null,
        array $metadata = [],
    ): ?ClinicalAudit {
        if ($field && $this->isSensitive($field)) {
            return null;
        }

        return ClinicalAudit::query()->create([
            'clinical_program_id' => $programId,
            'user_id' => auth('web')->id(),
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'event' => $event,
            'field' => $field,
            'old_value' => $this->stringify($oldValue),
            'new_value' => $this->stringify($newValue),
            'change_reason' => $changeReason,
            'approved_by' => $approvedBy,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            'metadata' => $metadata ?: null,
        ]);
    }

    private function isSensitive(string $field): bool
    {
        $field = mb_strtolower($field);

        return collect(ClinicalAudit::SENSITIVE_FIELDS)
            ->contains(fn (string $sensitive): bool => str_contains($field, $sensitive));
    }

    private function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_scalar($value)
            ? (string) $value
            : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
