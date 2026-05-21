<?php

namespace App\Services\WorksBuilder\Ai\PromptBuilders;

use App\Models\PlanningDoc;
use App\Models\WorksBuilder\ChecklistItem;
use App\Models\WorksBuilder\InternalPrompt;
use App\Models\WorksBuilder\Task;
use App\Models\WorksBuilder\TaskOption;
use App\Services\WorksBuilder\Theme\ThemeRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * 명세 v11 §1.6, §1.7 — 공통 system 프롬프트 + 세션 빌더.
 *
 * HTML 출력 규칙은 모든 호출에서 동일. 자식 클래스는 user 프롬프트만 구성한다.
 */
abstract class BasePromptBuilder
{
    protected const PLAN_SUMMARY_LIMIT = 800;
    protected const PLAN_CONTENT_LIMIT = 2800;

    abstract protected function purpose(): string;

    abstract protected function userPrompt(Task $task): string;

    /**
     * Task 정보 + 자식 클래스의 user 프롬프트를 합쳐 InternalPrompt 레코드를 만든다.
     */
    public function build(Task $task, ?int $reviewRound = null): InternalPrompt
    {
        $task->loadMissing('currentOption', 'project');

        $userPrompt = $this->userPrompt($task);

        return InternalPrompt::create([
            'task_id'       => $task->id,
            'purpose'       => $this->purpose(),
            'review_round'  => $reviewRound,
            'system_prompt' => $this->systemPrompt(),
            'user_prompt'   => $userPrompt,
            'payload_metadata' => [
                'task_uuid'      => $task->task_uuid,
                'mode'           => $task->mode,
                'options_version'=> $task->currentOption?->version,
                'project_id'     => $task->project_id,
            ],
            'created_by'    => Auth::id(),
        ]);
    }

    protected function systemPrompt(): string
    {
        return <<<SYSTEM
당신은 "Works Builder 표준 HTML 생성 엔진"입니다.
당신은 **사용자의 프로젝트(예: SK에코플랜트, 다른 고객사)의 화면을 생성**합니다.
당신이 동작하는 호스트 도구(Works Builder · SupportWorks)는 결과물에 절대 등장하면 안 됩니다.

## 절대 금지
- 결과 HTML 안에 **"SupportWorks", "Works Builder", "supportworks", "works builder"** 같은 호스트 도구 브랜드명·로고·카피라이트·풋터를 절대 포함하지 않는다.
- "Claude", "OpenAI", "AI가 생성한" 같은 생성 엔진 자체 언급도 금지.
- 로그인 페이지, "다시 돌아오신 걸 환영합니다" 같은 일반 SaaS welcome 화면을 사용자 요청 없이 임의로 만들지 않는다. 명확한 화면 지시가 없으면 **프로젝트 도메인(예: SK에코플랜트라면 에너지·인프라 운영)에 맞는 업무 대시보드**를 생성한다.

## 표준 Layout (반드시 포함)
- 모든 결과 HTML은 **표준 페이지 헤더**(상단 또는 측면 GNB·프로젝트명/로고·검색·사용자 영역)를 반드시 포함한다.
- GNB 위치 옵션이 'left' 또는 'right'여도 페이지 콘텐츠 상단에 별도의 **페이지 제목 바**(타이틀 + 액션 버튼/메타 라인)를 둔다.
- 헤더 없는 단일 폼·단일 메시지 화면은 만들지 않는다.

## 출력 규칙 (절대 준수)
- 응답은 **단일 HTML 파일** (`<!DOCTYPE html>` ~ `</html>`) 하나로만 끝낸다.
- React, Vue, Svelte 등 다른 프레임워크 출력 금지.
- 검수용 속성(`data-highlight`, `id="hover-target"`, `class="selected"` 등) 절대 주입 금지.
- 검수용 JS/CSS 절대 주입 금지.
- 모든 클래스명은 의미 기반(BEM 권장) + Tailwind 유틸리티 혼용 허용.
- HTML 외 어떤 설명 텍스트도 출력하지 않는다. (필요 시 ```html 코드블록 안에 HTML만)

## 일관성
- 같은 Task의 재호출에서는 이전 결정·용어·클래스명을 유지한다.
- 한국어 텍스트는 자연스러운 한국어를 사용한다.
- 브랜드명·로고·카피라이트는 **사용자가 지정한 프로젝트명**(컨텍스트에 주어진)만 사용한다.
SYSTEM;
    }

    protected function optionsBlock(?TaskOption $opt): string
    {
        if (!$opt) {
            return "## 레이아웃 옵션\n(없음 — 기본 레이아웃 사용)";
        }
        $d = $opt->options_data ?? [];

        $lines = ["## 레이아웃 옵션 (v{$opt->version})"];
        $lines[] = '- GNB 위치: '   . ($d['gnb_position']    ?? 'top');
        $lines[] = '- 탭 구조: '    . ($d['tab_structure']   ?? 'single');
        $lines[] = '- 화면 전환: '  . ($d['transition_type'] ?? 'page');
        $lines[] = '- 메인 색상: '  . ($d['main_color']      ?? '#3b82f6');
        if (!empty($d['theme_key'])) {
            $lines[] = '- 테마: '   . $d['theme_key'];
        }

        $extra = $d['extra'] ?? null;
        if (!empty($extra)) {
            $lines[] = '- 기타: ' . json_encode($extra, JSON_UNESCAPED_UNICODE);
        }
        return implode("\n", $lines);
    }

    protected function planBlock(Task $task): string
    {
        if ($task->mode !== 'enhance'
            || $task->spec_reference_type !== 'planning_doc'
            || !$task->spec_reference_id) {
            return '';
        }

        $plan = PlanningDoc::find($task->spec_reference_id);
        if (!$plan) return '';

        $lines = ['## 참조 기획서'];
        $lines[] = "- 제목: {$plan->title}";
        $lines[] = "- 버전: v{$plan->version}";
        $lines[] = '- 상태: ' . ($plan->status_label ?? $plan->status);

        $summary = (string) ($plan->ai_summary ?? $plan->description ?? '');
        if ($summary !== '') {
            $lines[] = '';
            $lines[] = '### 요약';
            $lines[] = Str::limit($summary, self::PLAN_SUMMARY_LIMIT);
        }
        $content = (string) ($plan->content ?? '');
        if ($content !== '') {
            $lines[] = '';
            $lines[] = '### 본문 발췌';
            $lines[] = Str::limit($content, self::PLAN_CONTENT_LIMIT);
        }
        return implode("\n", $lines);
    }

    /**
     * 선택된 테마의 prompt.md 본문을 통째로 user prompt 에 주입.
     *
     * 표준/검증 규칙을 추가할 때는 theme 디렉터리의 prompt.md 를 직접 편집한다.
     * 옵션에 theme_key 가 없거나 레지스트리에 없는 경우 기본 테마로 폴백.
     */
    protected function themeBlock(Task $task): string
    {
        /** @var ThemeRegistry $registry */
        $registry = app(ThemeRegistry::class);

        $key = $task->currentOption?->options_data['theme_key'] ?? null;
        if (!$key || !$registry->exists($key)) {
            $key = $registry->defaultKey();
        }
        if (!$key) {
            return '';
        }

        $manifest = $registry->get($key);
        $body = $registry->promptText($key);

        $header  = "## 적용 테마: {$manifest['name']} (key: {$key}, v" . ($manifest['version'] ?? '?') . ')';
        $header .= "\n다음 규약을 **반드시 준수**하여 HTML 을 생성합니다. 에셋은 출력 패키지의 `assets/theme/` 하위에 자동 포함됩니다.";

        return $body !== ''
            ? $header . "\n\n" . trim($body)
            : $header;
    }

    protected function checklistBlock(Task $task): string
    {
        $items = ChecklistItem::active()
            ->forProject($task->project_id)
            ->orderBy('category')
            ->get();

        if ($items->isEmpty()) {
            return "## 표준 체크리스트\n(이 프로젝트에 등록된 체크 항목 없음)";
        }

        $lines = ['## 표준 체크리스트 (반드시 준수)'];
        foreach ($items as $i) {
            $lines[] = sprintf('- [%s] %s: %s', $i->category, $i->title, $i->check_prompt_text);
        }
        return implode("\n", $lines);
    }

    protected function contextBlock(Task $task): string
    {
        $lines = ['## 컨텍스트'];
        $lines[] = '- 프로젝트: ' . ($task->project?->name ?? '(N/A)');
        $lines[] = "- Task UUID: {$task->task_uuid}";
        $lines[] = '- 모드: ' . ($task->mode === 'enhance' ? '고도화 (기획서 기반)' : '신규 화면 생성');

        if ($task->parent_task_id) {
            $reason = match ($task->reopen_reason) {
                'reopen' => '재실행 — 동일 화면 재생성',
                'clone'  => '복제 — 옵션 기반 새 화면',
                default  => '분기',
            };
            $lines[] = "- 부모 Task: #{$task->parent_task_id} ({$reason})";
        }
        return implode("\n", $lines);
    }
}
