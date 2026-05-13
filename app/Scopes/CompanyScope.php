<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * 회사 단위 데이터 격리 스코프.
 *
 * - 회사 소속 사용자  → company_group_id 가 동일한 레코드만 조회
 * - 개인 사용자(null) → 본인(ownerColumn) 레코드 + company_group_id IS NULL 만 조회
 * - 비인증 컨텍스트  → 스코프 적용 안 함 (콘솔·어드민 등)
 */
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (!auth()->check()) {
            return;
        }

        $user  = auth()->user();
        $table = $model->getTable();

        // 모델에 getOwnerColumn() 이 정의돼 있으면 사용, 아니면 user_id 기본값
        $owner = method_exists($model, 'getOwnerColumn')
            ? $model->getOwnerColumn()
            : 'user_id';

        if ($user->company_group_id) {
            // ── 회사 소속 사용자: 같은 회사 데이터 전체 조회 ──────────────
            $builder->where("{$table}.company_group_id", $user->company_group_id);
        } else {
            // ── 개인 사용자: 본인 데이터만, 회사 없는 레코드만 ───────────
            $builder->whereNull("{$table}.company_group_id")
                    ->where("{$table}.{$owner}", $user->id);
        }
    }
}
