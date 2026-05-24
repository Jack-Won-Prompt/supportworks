<?php

namespace App\Models\Agent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deliverable extends Model
{
    protected $table = 'deliverables';

    protected $fillable = [
        'project_id',
        'type_id',
        'current_step',
        'status',
        'responsibility',
        'created_by',
        'share_token',
    ];

    protected $casts = [
        'current_step' => 'integer',
    ];

    public function stepData(): HasMany
    {
        return $this->hasMany(DeliverableStepData::class);
    }

    public function toolResults(): HasMany
    {
        return $this->hasMany(DeliverableToolResult::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DeliverableApproval::class)->latest();
    }

    public function viewerComments(): HasMany
    {
        return $this->hasMany(DeliverableComment::class);
    }

    public function stepVersions(): HasMany
    {
        return $this->hasMany(DeliverableStepVersion::class);
    }

    public function latestStepVersion(int $step): ?DeliverableStepVersion
    {
        return DeliverableStepVersion::where('deliverable_id', $this->id)
            ->where('step_order', $step)
            ->orderByDesc('version_no')
            ->first();
    }

    public function nextStepVersionNo(int $step): int
    {
        $max = DeliverableStepVersion::where('deliverable_id', $this->id)
            ->where('step_order', $step)
            ->max('version_no');
        return (int) ($max ?? 0) + 1;
    }

    public function getStepValue(int $step, string $fieldKey): mixed
    {
        return $this->stepData
            ->where('step_order', $step)
            ->where('field_key', $fieldKey)
            ->first()
            ?->value;
    }

    public function getStepEnData(int $step, string $fieldKey): array
    {
        $row     = $this->stepData->where('step_order', $step)->where('field_key', $fieldKey)->first();
        $value   = $row?->value   ?? '';
        $enValue = $row?->en_value ?? '';
        $enHash  = $row?->en_hash  ?? '';
        $valid   = $enHash !== '' && $enHash === md5($value);

        return ['en_value' => $enValue, 'valid' => $valid];
    }

    /**
     * 해당 STEP·필드의 이미지 토큰 → URL 매핑.
     */
    public function getStepImageMap(int $step, string $fieldKey): array
    {
        $row = $this->stepData->where('step_order', $step)->where('field_key', $fieldKey)->first();
        $m   = $row?->image_map;
        return is_array($m) ? $m : [];
    }

    /**
     * "[[img:N w=600]]" 토큰을 <img> 인라인 HTML 로 확장.
     * - $map 누락 항목은 빈 문자열로 치환 (텍스트 본문에서 흔적 제거)
     * - w 생략 시 600 기본
     */
    public static function expandImageTokensWithMap(?string $value, array $map): string
    {
        $value = (string) ($value ?? '');
        if ($value === '' || empty($map)) {
            return preg_replace('/\[\[img:\d+(?:\s+w=\d+)?\]\]/', '', $value);
        }
        return (string) preg_replace_callback('/\[\[img:(\d+)(?:\s+w=(\d+))?\]\]/', function ($m) use ($map) {
            $id  = $m[1];
            $w   = (int) ($m[2] ?? 600);
            $url = $map[$id] ?? $map[(int) $id] ?? null;
            if (!$url) return '';
            $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            return '<img src="' . $url . '" style="width:' . $w . 'px">';
        }, $value);
    }

    /**
     * "[[img:N w=600]]" 토큰을 "[이미지 N]" 텍스트 플레이스홀더로 치환 (AI 프롬프트용 — URL 비공개).
     */
    public static function stripImageTokensForAi(?string $value): string
    {
        return (string) preg_replace('/\[\[img:(\d+)(?:\s+w=\d+)?\]\]/', '[이미지 $1]', (string) $value);
    }

    public function getToolResult(int $step, string $toolId): mixed
    {
        $raw = $this->toolResults
            ->where('step_order', $step)
            ->where('tool_id', $toolId)
            ->first()
            ?->result;

        return $raw ? json_decode($raw, true) : null;
    }

    public function getProgressPercent(int $totalSteps): int
    {
        if ($totalSteps <= 0) return 0;
        return (int) round((($this->current_step - 1) / $totalSteps) * 100);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
