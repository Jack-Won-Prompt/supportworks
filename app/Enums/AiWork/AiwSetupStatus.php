<?php

namespace App\Enums\AiWork;

/**
 * 매핑이 지시를 받을 수 있는 상태인가.
 *
 * 코드마다 **사람이 할 조치가 다르다.** 하나의 "준비 안 됨" 으로 뭉뚱그리면
 * 화면을 봐도 무엇을 해야 할지 알 수 없다 — 폴더를 만들 일과 브랜치를 고칠 일과
 * 커밋할 일은 전혀 다른 작업이다.
 */
enum AiwSetupStatus: string
{
    /** 지시를 받을 수 있다. */
    case Ok = 'ok';

    /** 경로가 없다. 매핑을 고치거나 그 폴더를 만들어야 한다. */
    case PathMissing = 'path_missing';

    /** git 저장소가 아니다. git init 이 필요하다. */
    case NotGitRepo = 'not_git_repo';

    /** 매핑에 적은 기본 브랜치가 그 저장소에 없다. */
    case BranchMissing = 'branch_missing';

    /** 커밋되지 않은 변경이 있다. 그대로 두면 작업 결과와 섞인다. */
    case DirtyTree = 'dirty_tree';

    /** 확인 중 예상 못 한 오류. 메시지를 봐야 한다. */
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Ok            => '준비됨',
            self::PathMissing   => '경로 없음',
            self::NotGitRepo    => 'git 저장소 아님',
            self::BranchMissing => '브랜치 없음',
            self::DirtyTree     => '미정리 변경',
            self::Unknown       => '확인 실패',
        };
    }

    /** 이 상태에서 지시를 시작할 수 있는가. */
    public function isReady(): bool
    {
        return $this === self::Ok;
    }

    /** 화면에서 쓸 색. 고칠 수 있는 것과 못 쓰는 것을 구분한다. */
    public function tone(): string
    {
        return match ($this) {
            self::Ok        => 'emerald',
            self::DirtyTree => 'amber',
            default         => 'red',
        };
    }
}
