<?php

namespace App\Models\Agent;

use App\Enums\Agent\FrontendStack;
use Illuminate\Database\Eloquent\Model;

class AiAgentStackStandard extends Model
{
    protected $table = 'ai_agent_stack_standards';

    protected $fillable = [
        'stack',
        'category',
        'name',
        'description',
        'definition',
        'validation_rules',
        'examples',
        'is_active',
    ];

    protected $casts = [
        'stack'            => FrontendStack::class,
        'definition'       => 'array',
        'validation_rules' => 'array',
        'examples'         => 'array',
        'is_active'        => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForStack($query, FrontendStack $stack)
    {
        return $query->where('stack', $stack);
    }

    // 특정 스택의 전체 표준 조회 (카테고리별 그룹)
    public static function forStackGrouped(FrontendStack $stack): array
    {
        return static::active()
            ->forStack($stack)
            ->get()
            ->groupBy('category')
            ->toArray();
    }

    // 스택의 단일 카테고리 레코드 조회
    public static function getByStack(FrontendStack $stack, string $category): ?self
    {
        return static::active()
            ->forStack($stack)
            ->where('category', $category)
            ->first();
    }

    // 폴더 구조 definition 반환
    public static function getFolderStructure(FrontendStack $stack): array
    {
        return static::getByStack($stack, 'folder_structure')?->definition ?? [];
    }

    // 컴포넌트 패턴 definition 반환 (코드 템플릿 포함)
    public static function getCodeTemplate(FrontendStack $stack): array
    {
        return static::getByStack($stack, 'component')?->definition ?? [];
    }

    // 스택의 모든 카테고리 validation_rules 병합 반환
    public static function getValidationRules(FrontendStack $stack): array
    {
        $records = static::active()->forStack($stack)->get();

        $merged = ['required_patterns' => [], 'forbidden_patterns' => [], 'accessibility' => []];

        foreach ($records as $record) {
            if (empty($record->validation_rules)) {
                continue;
            }
            foreach (['required_patterns', 'forbidden_patterns', 'accessibility'] as $key) {
                if (!empty($record->validation_rules[$key])) {
                    $merged[$key] = array_unique(array_merge($merged[$key], $record->validation_rules[$key]));
                }
            }
        }

        return $merged;
    }
}
