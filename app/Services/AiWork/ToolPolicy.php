<?php

namespace App\Services\AiWork;

use Illuminate\Validation\ValidationException;

/**
 * allowed_tools 를 서버가 강제한다.
 *
 * 웹에서 들어온 값을 그대로 데몬에 넘기지 않는다. 이 목록은 사실상 "작업 PC 에서
 * 무엇을 실행해도 되는가"이므로, 클라이언트가 정하게 두면 통제가 없는 것과 같다.
 * 데몬도 같은 규칙을 다시 검사한다(이중 방어).
 */
class ToolPolicy
{
    /** 이 집합 밖의 값은 저장 시 거부한다. */
    public static function supported(): array
    {
        return (array) config('aiw.supported_tools', []);
    }

    /** permission_mode=acceptEdits 일 때 자동 승인되는 툴. config/aiw.php 가 단일 출처다. */
    public static function autoApprovable(): array
    {
        return (array) config('aiw.auto_approvable', []);
    }

    public static function defaults(): array
    {
        return (array) config('aiw.default_tools', []);
    }

    /**
     * 저장 전 검증·정규화.
     *
     * @param  array<int, string>  $tools
     * @return array<int, string>  중복 제거·순서 정규화된 목록
     *
     * @throws ValidationException 미지원 툴이 섞여 있으면 422
     */
    public function sanitize(array $tools): array
    {
        $tools = array_values(array_unique(array_map('strval', $tools)));

        if ($tools === []) {
            throw ValidationException::withMessages([
                'allowed_tools' => '허용 툴을 최소 하나 선택해야 합니다.',
            ]);
        }

        $unsupported = array_diff($tools, self::supported());

        if ($unsupported !== []) {
            throw ValidationException::withMessages([
                'allowed_tools' => '지원하지 않는 툴입니다: '.implode(', ', $unsupported),
            ]);
        }

        // 저장 순서를 supported() 기준으로 고정해 diff·비교를 안정시킨다.
        return array_values(array_intersect(self::supported(), $tools));
    }

    /**
     * 이 툴 호출이 사람의 승인 없이 진행돼도 되는가.
     *
     * permission_mode=default 는 목록과 무관하게 항상 false 다 — 매번 사람이 본다.
     * acceptEdits 의 허용 범위는 config('aiw.auto_approvable') 이 정하며, 현재
     * Bash 를 포함한다. 그 선택의 의미는 config/aiw.php 주석에 적어 두었다.
     */
    public function isAutoApprovable(string $tool, string $permissionMode): bool
    {
        if ($permissionMode !== 'acceptEdits') {
            return false;
        }

        return in_array($tool, self::autoApprovable(), true);
    }

    /** 화면에서 경고를 띄워야 하는가(Bash 가 선택됨). */
    public function requiresBashWarning(array $tools): bool
    {
        return in_array('Bash', $tools, true);
    }

    /** 기본 비활성 툴. 켜려면 사용자가 명시적으로 선택해야 한다. */
    public function optIn(): array
    {
        return array_values(array_diff(self::supported(), self::defaults()));
    }
}
