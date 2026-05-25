<?php

namespace App\Services\Maint;

use App\Models\AiSetting;
use App\Models\Maint\MaintRequest;
use App\Services\AiOrchestrator;
use Illuminate\Support\Str;

/**
 * SR (maint_requests) 의 [웍스 요약 생성] 백엔드 로직.
 *
 * 1. 같은 company_group_id + 같은 category + 같은 menu_id 의 최근 SR 들을 유사 컨텍스트로 모은다.
 * 2. AI 에게 "SR 담당자가 빠르게 이해할 수 있도록 정리" 프롬프트로 호출.
 * 3. 응답 + 참고한 SR id 목록을 반환.
 */
class SrSummaryService
{
    private const SIMILAR_LIMIT     = 10;
    private const SIMILAR_TEXT_CAP  = 800;   // 컨텍스트 당 본문 글자수 제한
    private const NEW_CONTENT_CAP   = 4000;  // 새 SR 본문 글자수 제한

    /**
     * @return array{summary:string, context_ids:array<int>, provider:?string, classification:?string}
     */
    public function summarize(MaintRequest $req): array
    {
        $similar    = $this->findSimilar($req);
        $userPrompt = $this->buildUserPrompt($req, $similar);

        $settings = AiSetting::current();
        $orch     = new AiOrchestrator(
            $settings->anthropicKey(),
            $settings->openaiKey(),
            $settings->manusKey(),
            $settings->manusEndpoint(),
        );

        $res = $orch->chatRaw(
            [['role' => 'user', 'content' => $userPrompt]],
            $this->systemPrompt(),
        );

        // 분류는 별도 호출 — 실패해도 요약은 그대로 반환
        $classification = $this->classify($orch, $req);

        return [
            'summary'        => trim((string) ($res['text'] ?? '')),
            'context_ids'    => $similar->pluck('id')->all(),
            'provider'       => $res['provider'] ?? null,
            'classification' => $classification,
        ];
    }

    /**
     * SR 내용을 보고 free / paid / discuss 중 하나로 분류.
     * 실패하거나 응답이 enum 외 값이면 null.
     */
    private function classify(AiOrchestrator $orch, MaintRequest $req): ?string
    {
        try {
            $res = $orch->generateDraft(
                $this->classifySystemPrompt(),
                $this->classifyUserPrompt($req),
                $this->classifySchema(),
            );
            $cls = $res['fields']['classification'] ?? null;
            if (in_array($cls, ['free', 'paid', 'discuss'], true)) {
                return $cls;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[SrSummaryService] 분류 실패 SR #' . ($req->id ?? '?') . ': ' . $e->getMessage());
        }
        return null;
    }

    private function classifySystemPrompt(): string
    {
        return <<<SYS
당신은 SR(유지보수 요청) 분류 보조입니다.

다음 SR 내용을 분석해 정확히 3가지 중 하나로 분류하세요:

- "free" (무상): 에러 수정, 데이터 확인/정정, 버그 보고 등 **기존 기능의 결함 해소**.
- "paid" (유상 추가 개발): 신규 기능 추가, 프로세스 변경, 화면/기능 확장 등 **추가 개발이 필요한** 작업.
- "discuss" (논의 필요): 요건이 모호하거나 영향 범위가 커서 위 두 분류로 단정하기 어려운 경우.

판단 기준이 애매하면 "discuss" 를 선택하세요. 반드시 셋 중 하나의 값만 응답.
SYS;
    }

    private function classifyUserPrompt(MaintRequest $req): string
    {
        return implode("\n", [
            '## 분류 대상 SR',
            '- 메뉴: ' . ($req->menu?->name ?? '—'),
            '- 구분: ' . ($req->category ?? '—'),
            '- 우선순위: ' . ($req->priority ?? '—'),
            '- 요약: ' . trim((string) $req->summary),
            '',
            '### 상세 내용',
            \Illuminate\Support\Str::limit((string) $req->content, 3000),
        ]);
    }

    private function classifySchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['classification'],
            'properties' => [
                'classification' => [
                    'type'        => 'string',
                    'enum'        => ['free', 'paid', 'discuss'],
                    'description' => '무상(free) / 유상 추가 개발(paid) / 논의 필요(discuss) 중 하나.',
                ],
            ],
        ];
    }

    private function systemPrompt(): string
    {
        return <<<SYS
당신은 SupportWorks 의 SR(유지보수 요청) 정리 보조입니다.

사용자가 작성한 요청 원본을, **SR 담당자가 빠르게 이해하고 작업에 착수할 수 있도록** 정리합니다.

## 정리 원칙
- 한국어, 평이한 업무체 문장.
- 핵심을 위로: 무엇을(목적) → 어디서(메뉴/화면) → 왜(배경) → 기대 결과(어떻게 되어야 하는지).
- 모호한 표현은 명확히 ("그거", "거기" → 구체 메뉴/필드).
- 누락된 정보는 추측하지 말고 "확인 필요: ..." 로 분리.
- 같은 회사의 비슷한 과거 요청이 제공되면, **용어·메뉴 명칭·해결 패턴을 일관되게** 맞춰 작성한다.

## 출력 형식 (Markdown, 짧고 스캔 가능하게)
**요청 요약**
- 1~2줄 한 줄 요약.

**핵심 항목**
- 목적:
- 대상 화면/메뉴:
- 현재 상태:
- 기대 결과:

**참고 (선택)**
- 과거 유사 요청에서의 처리 방식 / 용어 일치 노트.

**확인 필요 (선택)**
- 사용자에게 다시 물어봐야 하는 항목 (있을 때만).

설명 텍스트나 인사말 없이 위 형식만 출력.
SYS;
    }

    private function buildUserPrompt(MaintRequest $req, $similar): string
    {
        $lines = ['# 신규 SR 요약 요청', ''];

        $lines[] = '## 새 SR (정리 대상)';
        $lines[] = '- ID: ' . ($req->id ? '#'.$req->id : '(미저장)');
        $lines[] = '- 메뉴: ' . ($req->menu?->name ?? '—');
        $lines[] = '- 구분: ' . ($req->category ?? '—');
        $lines[] = '- 우선순위: ' . ($req->priority ?? '—');
        $lines[] = '- 요약 (사용자 입력): ' . trim((string) $req->summary);
        $lines[] = '';
        $lines[] = '### 상세 내용 (원본)';
        $lines[] = Str::limit((string) $req->content, self::NEW_CONTENT_CAP);

        if ($similar->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '## 같은 회사의 비슷한 유형 SR ('.$similar->count().'건, 최근 순)';
            foreach ($similar as $i => $s) {
                $lines[] = '';
                $lines[] = '### #'.($i+1).' — SR #'.$s->id.'  ['.($s->status ?? '?').']  '.($s->category ?? '');
                $lines[] = '- 메뉴: '.($s->menu?->name ?? '—');
                $lines[] = '- 요약: '.trim((string) $s->summary);
                $content = trim((string) $s->content);
                if ($content !== '') {
                    $lines[] = '- 본문 발췌:';
                    $lines[] = Str::limit($content, self::SIMILAR_TEXT_CAP);
                }
            }
        }

        $lines[] = '';
        $lines[] = '## 작업';
        $lines[] = '위 새 SR 을 시스템 프롬프트 형식대로 정리해 출력하세요.';

        return implode("\n", $lines);
    }

    /**
     * 같은 company_group_id + 같은 category + 같은 menu_id 의 최근 SR 들.
     * 자기 자신은 제외.
     */
    private function findSimilar(MaintRequest $req)
    {
        $q = MaintRequest::query()
            ->where('id', '!=', $req->id ?? 0)
            ->where('company_group_id', $req->company_group_id);

        if ($req->category) {
            $q->where('category', $req->category);
        }
        if ($req->menu_id) {
            $q->where('menu_id', $req->menu_id);
        }

        return $q->with('menu')
            ->latest('updated_at')
            ->limit(self::SIMILAR_LIMIT)
            ->get();
    }
}
