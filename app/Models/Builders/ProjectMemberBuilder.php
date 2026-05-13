<?php

namespace App\Models\Builders;

use App\Support\CollabContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProjectMemberBuilder extends Builder
{
    /**
     * 협업 참여자는 호스트가 멤버인 프로젝트에 접근 가능.
     * role 조건이 있는 쿼리(매니저 권한 체크)는 우회하지 않음.
     */
    public function exists(): bool
    {
        if (parent::exists()) {
            return true;
        }

        $hostId = CollabContext::hostId();
        if (!$hostId) {
            return false;
        }

        $wheres = $this->getQuery()->wheres;

        // role 조건이 있으면 매니저/역할 체크이므로 우회 금지
        foreach ($wheres as $where) {
            $col = $where['column'] ?? '';
            if ($col === 'role' || str_ends_with($col, '.role')) {
                return false;
            }
        }

        // project_id 추출
        $projectId = null;
        foreach ($wheres as $where) {
            if ($where['type'] !== 'Basic') continue;
            $col = $where['column'] ?? '';
            if ($col === 'project_id' || str_ends_with($col, '.project_id')) {
                $projectId = $where['value'];
                break;
            }
        }

        if (!$projectId) {
            return false;
        }

        // 호스트가 해당 프로젝트 멤버인지 확인 (재귀 방지를 위해 DB 파사드 직접 사용)
        return DB::table('project_members')
            ->where('project_id', $projectId)
            ->where('user_id', $hostId)
            ->exists();
    }
}
