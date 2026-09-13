<?php

namespace App\Enums\AiWork;

/**
 * 데몬이 보고하는 실패 사유 코드.
 *
 * 문자열 메시지는 사람이 읽는 것이고, 코드는 화면이 읽는 것이다. 코드가 있어야
 * "무엇을 할 수 있는지"(브랜치 없이 재실행, 매핑 수정)를 버튼으로 제시할 수 있다.
 *
 * 데몬의 src/errors.ts 가 같은 문자열을 쓴다. 여기 없는 코드는 서버가 거부하므로,
 * 한쪽만 바꾸면 조용히 무시되는 대신 422 로 드러난다.
 */
enum AiwFailureCode: string
{
    /** 브랜치 분리를 켰는데 작업 폴더에 커밋되지 않은 변경이 있다. */
    case DirtyTree = 'dirty_tree';

    /** 매핑에 적힌 기본 브랜치가 저장소에 없다. */
    case MissingBranch = 'missing_branch';

    /** 이 담당자에 해당 프로젝트의 로컬 경로가 매핑되어 있지 않다. */
    case NoMapping = 'no_mapping';

    /** 매핑된 경로가 그 PC 에 없다. */
    case PathMissing = 'path_missing';

    /** 데몬 재시작으로 세션을 이어갈 수 없다. */
    case DaemonRestarted = 'daemon_restarted';

    /**
     * 담당자 PC 가 응답하지 않아 서버가 끊었다(aiw:reap-stale-jobs).
     *
     * 코드나 지시가 잘못된 것이 아니라 환경이 사라진 것이다. 그래서 같은 지시를
     * 그대로 다시 보내면 되는 몇 안 되는 실패에 속한다.
     */
    case AgentUnreachable = 'agent_unreachable';

    /**
     * 누적 비용이 상한에 닿아 서버가 멈췄다.
     *
     * 이 코드만 데몬이 아니라 **서버**(CostGuard)가 붙인다. 실패가 아니라
     * 설계된 정지라, 사람에게는 "무엇이 잘못됐나" 가 아니라 "어떻게 이어가나"
     * 를 보여 줘야 한다.
     */
    case CostLimit = 'cost_limit';

    public function label(): string
    {
        return match ($this) {
            self::DirtyTree       => '작업 폴더 정리 필요',
            self::MissingBranch   => '기본 브랜치 없음',
            self::NoMapping       => '폴더 매핑 없음',
            self::PathMissing     => '경로 없음',
            self::DaemonRestarted => '담당자 재시작',
            self::CostLimit       => '비용 상한 도달',
            self::AgentUnreachable => '담당자 응답 없음',
        };
    }

    /** 설정을 고쳐야 풀리는가(관리자 화면으로 보내야 하는가). */
    public function needsMappingFix(): bool
    {
        return in_array($this, [self::MissingBranch, self::NoMapping, self::PathMissing], true);
    }

    /** 브랜치 분리를 끄고 다시 시도하면 풀리는가. */
    public function retryableWithoutBranch(): bool
    {
        return $this === self::DirtyTree;
    }

    /**
     * 같은 설정으로 다시 보내면 되는가.
     *
     * dirty_tree 가 여기 있는 이유: 폴더를 정리하고 나면 원래 설정 그대로 다시
     * 보내는 것이 맞다. 예전에는 '브랜치 없이 다시 지시' 만 제시했는데, 그것은
     * 정리하기 전에만 맞는 조언이다 — 브랜치를 끄면 결과 반영·배포 경로가 통째로
     * 사라져서, 나중에 버튼으로는 올릴 수 없는 상태가 된다.
     */
    public function retryableAsIs(): bool
    {
        return in_array($this, [self::DaemonRestarted, self::DirtyTree, self::AgentUnreachable], true);
    }

    /**
     * 사람에게 묻지 않고 서버가 다시 보내도 되는가.
     *
     * 환경이 사라진 경우만이다. 코드가 틀렸거나 상한에 걸린 실패를 그대로 다시
     * 보내면 같은 자리에서 또 멈추고 비용만 든다 — 그건 사람이 판단할 일이다.
     */
    public function autoRetryable(): bool
    {
        return in_array($this, [self::DaemonRestarted, self::AgentUnreachable], true);
    }

    /** 상한을 올리거나 꺼야 이어갈 수 있는가. */
    public function needsHigherCostLimit(): bool
    {
        return $this === self::CostLimit;
    }
}
