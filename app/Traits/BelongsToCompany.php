<?php

namespace App\Traits;

use App\Models\CompanyGroup;
use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 회사 격리 Trait.
 *
 * 사용법:
 *   class CommunityPost extends Model {
 *       use BelongsToCompany;
 *       // 소유자 컬럼이 user_id 가 아닐 때만 오버라이드
 *       protected string $ownerColumn = 'created_by';
 *   }
 *
 * 기능:
 *   - creating 이벤트: company_group_id 자동 설정
 *   - 글로벌 스코프:   CompanyScope 자동 적용
 *   - 헬퍼 메서드:    withoutCompanyScope(), scopeCompanyOf()
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        // ── 1. 생성 시 company_group_id 자동 주입 ────────────────────────
        static::creating(function ($model) {
            if (auth()->check() && is_null($model->company_group_id)) {
                $model->company_group_id = auth()->user()->company_group_id;
            }
        });

        // ── 2. 글로벌 스코프 등록 ────────────────────────────────────────
        static::addGlobalScope(new CompanyScope());
    }

    // ── 소유자 컬럼 반환 (모델에서 오버라이드 가능) ──────────────────────
    public function getOwnerColumn(): string
    {
        return property_exists($this, 'ownerColumn') ? $this->ownerColumn : 'user_id';
    }

    // ── 글로벌 스코프 해제 (관리자 쿼리 등 내부 용도) ───────────────────
    public static function withoutCompanyScope(): Builder
    {
        return static::withoutGlobalScope(CompanyScope::class);
    }

    // ── 로컬 스코프: 특정 회사 데이터만 조회 ────────────────────────────
    public function scopeCompanyOf(Builder $query, \App\Models\User $user): Builder
    {
        $table = $this->getTable();
        if ($user->company_group_id) {
            return $query->where("{$table}.company_group_id", $user->company_group_id);
        }
        return $query->whereNull("{$table}.company_group_id")
                     ->where("{$table}.{$this->getOwnerColumn()}", $user->id);
    }

    // ── 관계 ─────────────────────────────────────────────────────────────
    public function companyGroup(): BelongsTo
    {
        return $this->belongsTo(CompanyGroup::class);
    }
}
